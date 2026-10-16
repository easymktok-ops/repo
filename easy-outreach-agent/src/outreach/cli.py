"""CLI de Easy Outreach Agent. Los comandos de cada fase se agregan aquí."""

from __future__ import annotations

from concurrent.futures import ThreadPoolExecutor, as_completed

import typer
from alembic import command
from alembic.config import Config
from sqlalchemy import select
from sqlalchemy.orm import Session, sessionmaker

from outreach.config import (
    PROJECT_ROOT,
    get_secrets,
    get_segment,
    load_scoring,
    load_segments,
    load_settings,
)
from outreach.db.base import make_engine, make_session_factory
from outreach.db.models import Company, Segment
from outreach.enrich.pipeline import apply_result, enrich_site
from outreach.http.client import PoliteClient
from outreach.http.dns import domain_resolves
from outreach.jobs.runs import track_run
from outreach.logging import configure_logging, get_logger
from outreach.sources.base import Source, SourceError
from outreach.sources.ingest import ingest_many, sync_segments
from outreach.stats import discovery_stats

app = typer.Typer(help="Easy Outreach Agent", no_args_is_help=True)

# Claves que cada fase necesita. Solo se informa si existen, nunca su valor.
REQUIRED_KEYS = {
    "Fase 1 (descubrimiento)": ["denue_token"],
    "Fase 2 (auditoría)": ["pagespeed_key", "anthropic_api_key"],
    "Fase 3 (demos)": ["cloudflare_api_token", "cloudflare_account_id"],
    "Fase 4 (envío)": ["smtp_host", "smtp_user", "smtp_password", "imap_host", "imap_user",
                       "imap_password"],
}


def _alembic_config() -> Config:
    cfg = Config(str(PROJECT_ROOT / "alembic.ini"))
    cfg.set_main_option("script_location", str(PROJECT_ROOT / "migrations"))
    return cfg


def _session_factory() -> sessionmaker[Session]:
    """Aplica migraciones pendientes (idempotente) y devuelve la fábrica de sesiones."""
    command.upgrade(_alembic_config(), "head")
    return make_session_factory(make_engine(get_secrets().database_url))


def _client() -> PoliteClient:
    crawler = load_settings().crawler
    return PoliteClient(
        user_agent=crawler.user_agent,
        timeout=crawler.timeout_seconds,
        max_per_second=crawler.max_requests_per_second_per_domain,
        retries=crawler.retries,
        max_bytes=crawler.max_page_bytes,
    )


def _source(name: str, client: PoliteClient) -> Source:
    secrets = get_secrets()
    if name == "denue":
        from outreach.sources.denue import DenueSource

        return DenueSource(client, secrets.denue_token, load_settings().sources.denue.page_size)
    from outreach.sources.places import PlacesSource

    return PlacesSource(client, secrets.google_places_key)


def _pct(part: int, total: int) -> str:
    return f"{part}/{total} ({part / total:.0%})" if total else "0/0"


@app.callback()
def main(pretty: bool = typer.Option(True, help="Logs legibles en consola")) -> None:
    configure_logging(pretty=pretty)


@app.command("init-db")
def init_db() -> None:
    """Crea o actualiza la base de datos con las migraciones."""
    command.upgrade(_alembic_config(), "head")
    get_logger("cli").info("db_lista", url=get_secrets().database_url)


@app.command("config-check")
def config_check() -> None:
    """Valida config/*.yaml y muestra qué claves faltan en .env (sin mostrar valores)."""
    settings = load_settings()
    load_scoring()
    segments = load_segments()
    secrets = get_secrets()

    typer.echo(f"settings.yaml OK · remitente {settings.sending.from_address}")
    typer.echo(f"segmentos: {len(segments)} ({sum(s.active for s in segments)} activos)")
    typer.echo(f"DRY_RUN={'true' if secrets.dry_run else 'false'}")
    for phase, keys in REQUIRED_KEYS.items():
        missing = [k.upper() for k in keys if not getattr(secrets, k)]
        state = "lista" if not missing else "faltan " + ", ".join(missing)
        typer.echo(f"{phase}: {state}")


@app.command()
def discover(
    segment: str = typer.Option(..., "--segment", "-s", help="Nombre en segments.yaml"),
    limit: int = typer.Option(200, help="Empresas nuevas a guardar"),
    source: str = typer.Option("denue", help="denue o places"),
    include_no_site: bool = typer.Option(
        False, help="Guardar también empresas sin sitio propio (no se pueden auditar)"
    ),
    max_scan: int = typer.Option(20000, help="Tope de registros a leer de la fuente"),
) -> None:
    """Descubre empresas del segmento en la fuente oficial y las guarda sin duplicados."""
    seg_cfg = get_segment(segment)
    if source not in ("denue", "places"):
        raise typer.BadParameter("usa denue o places", param_hint="--source")
    if not getattr(load_settings().sources, source).enabled:
        typer.echo(f"La fuente {source} está apagada en config/settings.yaml (sources).", err=True)
        raise typer.Exit(1)

    factory = _session_factory()
    params = {"segment": segment, "source": source, "limit": limit,
              "include_no_site": include_no_site}
    with _client() as client, factory() as session:
        segments = sync_segments(session, load_segments())
        session.commit()
        try:
            src = _source(source, client)
            with track_run(session, "discover", params) as run:
                stats = ingest_many(
                    session, src.search(seg_cfg, limit=max_scan), segments[segment], run,
                    limit, require_site=not include_no_site, site_exists=domain_resolves,
                )
                run.stats = dict(stats)
        except SourceError as exc:
            typer.echo(f"Error de la fuente {source}: {exc}", err=True)
            raise typer.Exit(1) from exc

    typer.echo(
        f"{segment} · leídas {stats['leidas']} · nuevas {stats['nueva']} · "
        f"duplicadas {stats['duplicada']} · sin sitio {stats['sin_sitio']} · "
        f"dominio inexistente {stats['dominio_inexistente']} · suprimidas {stats['suprimida']}"
    )


@app.command()
def enrich(
    segment: str | None = typer.Option(None, "--segment", "-s"),
    limit: int = typer.Option(200, help="Empresas a visitar"),
    workers: int | None = typer.Option(None, help="Sitios en paralelo (default: settings)"),
    retry: bool = typer.Option(False, help="Revisitar las que fallaron (sitio caído, etc.)"),
) -> None:
    """Visita el sitio de cada empresa nueva: contactos publicados, stack e idioma."""
    crawler = load_settings().crawler
    factory = _session_factory()
    log = get_logger("enrich")

    with _client() as client, factory() as session:
        query = select(Company.id, Company.website_url).where(Company.website_url.is_not(None))
        if retry:
            query = query.where(Company.status == "discarded",
                                Company.site_status.in_(("unreachable", "http_error")))
        else:
            query = query.where(Company.status == "new")
        if segment:
            query = query.join(Segment).where(Segment.name == segment)
        targets = session.execute(query.order_by(Company.id).limit(limit)).all()
        if not targets:
            typer.echo("No hay empresas por enriquecer.")
            return

        params = {"segment": segment, "limit": limit, "retry": retry}
        with track_run(session, "enrich", params) as run, ThreadPoolExecutor(
            max_workers=workers or crawler.workers
        ) as pool:
            futures = {
                pool.submit(enrich_site, client, cid, url, crawler.max_pages_per_site): cid
                for cid, url in targets
            }
            counts: dict[str, int] = {}
            for done, future in enumerate(as_completed(futures), start=1):
                cid = futures[future]
                try:
                    result = future.result()
                    status = apply_result(session, session.get(Company, cid), result)
                    session.commit()
                except Exception as exc:  # un sitio raro no detiene la corrida
                    session.rollback()
                    log.warning("enrich_error", company_id=cid, error=str(exc)[:200])
                    status = "error"
                counts[status] = counts.get(status, 0) + 1
                if done % 10 == 0 or done == len(futures):
                    log.info("enrich_avance", hechas=done, total=len(futures))
            run.stats = counts

    typer.echo("enrich · " + " · ".join(f"{k} {v}" for k, v in sorted(counts.items())))


@app.command()
def stats(segment: str | None = typer.Option(None, "--segment", "-s")) -> None:
    """Resumen del descubrimiento: sitio válido y canal de contacto (criterio de Fase 1)."""
    factory = _session_factory()
    with factory() as session:
        s = discovery_stats(session, segment)
    typer.echo(f"Empresas: {s.total} · visitadas: {s.enriched} · sitio válido: "
               f"{_pct(s.site_ok, s.enriched)}")
    typer.echo(f"Sitio válido + correo o formulario sin CAPTCHA: {_pct(s.reachable, s.enriched)}"
               "  <- criterio Fase 1 (>= 60%)")
    typer.echo(f"Sitio válido + cualquier canal (incluye WhatsApp/teléfono): "
               f"{_pct(s.any_channel, s.enriched)}")
    for title, counter in (("Estado", s.by_status), ("Sitio", s.by_site_status),
                           ("Canales (con sitio válido)", s.by_channel)):
        if counter:
            typer.echo(f"{title}: " + " · ".join(f"{k} {v}" for k, v in counter.most_common()))


if __name__ == "__main__":
    app()
