"""CLI de Easy Outreach Agent. Los comandos de cada fase se agregan aquí."""

from __future__ import annotations

import typer
from alembic import command
from alembic.config import Config

from outreach.config import PROJECT_ROOT, get_secrets, load_scoring, load_segments, load_settings
from outreach.logging import configure_logging, get_logger

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


if __name__ == "__main__":
    app()
