"""Ingesta de lo que entregan las fuentes: normaliza, deduplica por dominio y guarda."""

from __future__ import annotations

from collections import Counter
from collections.abc import Iterable

from sqlalchemy import select
from sqlalchemy.orm import Session

from outreach.config import SegmentConfig
from outreach.db.models import Company, Contact, Run, Segment
from outreach.domains import FREE_MAIL_DOMAINS, is_platform_domain, normalize_domain
from outreach.enrich.contacts import accept_email, normalize_phone
from outreach.sending.suppression import is_suppressed
from outreach.sources.base import RawCompany

# Campos que se completan en una empresa existente si llegan vacíos.
MERGE_FIELDS = (
    "legal_name", "phone", "address", "city", "state", "scian_code", "giro", "size_stratum",
)


def sync_segments(session: Session, configs: Iterable[SegmentConfig]) -> dict[str, Segment]:
    """Refleja segments.yaml en la tabla segments (alta o actualización por nombre)."""
    result: dict[str, Segment] = {}
    for cfg in configs:
        seg = session.scalar(select(Segment).where(Segment.name == cfg.name))
        if seg is None:
            seg = Segment(name=cfg.name)
            session.add(seg)
        seg.country, seg.city, seg.giro = cfg.country, cfg.city, cfg.giro
        seg.auto_send, seg.daily_cap, seg.active = cfg.auto_send, cfg.daily_cap, cfg.active
        result[cfg.name] = seg
    session.flush()
    return result


def site_of(raw: RawCompany) -> tuple[str | None, str | None]:
    """Dominio y URL del sitio propio. Si no hay sitio, se deduce del correo corporativo."""
    domain = normalize_domain(raw.website_url)
    if domain and not is_platform_domain(domain):
        return domain, raw.website_url
    email_domain = normalize_domain(raw.email) if raw.email else None
    if email_domain and email_domain not in FREE_MAIL_DOMAINS and not is_platform_domain(
        email_domain
    ):
        return email_domain, f"https://{email_domain}"
    return None, None


def ingest(
    session: Session, raw: RawCompany, segment: Segment, run: Run, *, require_site: bool = True
) -> str:
    """Guarda una empresa. Devuelve: nueva / duplicada / sin_sitio / suprimida."""
    domain, website = site_of(raw)
    if domain is None and require_site:
        return "sin_sitio"
    if domain and is_suppressed(session, domain):
        return "suprimida"

    if domain:
        existing = session.scalar(select(Company).where(Company.domain == domain))
    else:
        existing = session.scalar(select(Company).where(
            Company.source == raw.source, Company.source_ref == raw.source_ref
        ))
    if existing is not None:
        for name in MERGE_FIELDS:
            if getattr(existing, name) is None and getattr(raw, name) is not None:
                setattr(existing, name, getattr(raw, name))
        return "duplicada"

    company = Company(
        name=raw.name, legal_name=raw.legal_name, domain=domain, website_url=website,
        country=raw.country, state=raw.state, city=raw.city, scian_code=raw.scian_code,
        giro=raw.giro, size_stratum=raw.size_stratum, phone=raw.phone, address=raw.address,
        source=raw.source, source_ref=raw.source_ref, source_url=raw.source_url,
        segment=segment, run_id=run.id, status="new",
    )
    # Correo y teléfono que la empresa registró en la fuente oficial, con su ficha como origen.
    email = accept_email(raw.email, domain) if raw.email and domain else None
    if email:
        company.contacts.append(Contact(type="email", value=email, source_url=raw.source_url))
    phone = normalize_phone(raw.phone) if raw.phone else None
    if phone:
        company.contacts.append(Contact(type="phone", value=phone, source_url=raw.source_url))
    session.add(company)
    session.flush()
    return "nueva"


def ingest_many(
    session: Session, raws: Iterable[RawCompany], segment: Segment, run: Run, limit: int,
    *, require_site: bool = True, commit_every: int = 50,
) -> Counter[str]:
    """Ingiere hasta tener `limit` empresas nuevas. Devuelve el conteo por resultado.

    Confirma cada `commit_every` registros para no perder avance si la fuente falla a la mitad.
    """
    stats: Counter[str] = Counter()
    for raw in raws:
        stats["leidas"] += 1
        stats[ingest(session, raw, segment, run, require_site=require_site)] += 1
        if stats["leidas"] % commit_every == 0:
            session.commit()
        if stats["nueva"] >= limit:
            break
    return stats
