"""Enriquecimiento: visita el sitio, extrae contactos y stack, y decide el siguiente estado.

`enrich_site` no toca la base (se puede correr en hilos); `apply_result` guarda en la sesión.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any

from sqlalchemy import select
from sqlalchemy.orm import Session

from outreach.db.base import utcnow
from outreach.db.models import Company, Contact
from outreach.enrich.contacts import FoundContact, extract_contacts
from outreach.enrich.site import visit_site
from outreach.enrich.stack import detect_language, detect_stack
from outreach.http.client import PoliteClient

# Canales que permiten escribir sin intervención manual (correo o formulario sin CAPTCHA).
OUTREACH_TYPES = ("email", "form")


@dataclass
class EnrichResult:
    company_id: int
    site_status: str
    final_url: str | None = None
    domain: str | None = None
    contacts: list[FoundContact] = field(default_factory=list)
    stack: dict[str, Any] = field(default_factory=dict)
    language: str | None = None
    detail: str | None = None


def enrich_site(
    client: PoliteClient, company_id: int, website_url: str, max_pages: int = 4
) -> EnrichResult:
    visit = visit_site(client, website_url, max_pages=max_pages)
    result = EnrichResult(
        company_id, visit.status, visit.final_url, visit.domain, detail=visit.detail
    )
    if visit.status != "ok":
        return result

    home = visit.pages[0]
    seen: set[tuple[str, str]] = set()
    for page in visit.pages:
        for contact in extract_contacts(page.final_url, page.text, visit.domain or ""):
            if (contact.type, contact.value) not in seen:
                seen.add((contact.type, contact.value))
                result.contacts.append(contact)
    result.stack = detect_stack(home.text, home.headers)
    for page in visit.pages[1:]:
        extra = detect_stack(page.text)
        for category, techs in extra.items():
            if category in ("cms", "generator"):
                continue
            merged = result.stack.setdefault(category, [])
            merged.extend(t for t in techs if t not in merged)
    result.language = detect_language(home.text)
    return result


def is_reachable(contacts: list[Contact]) -> bool:
    return any(
        c.type in OUTREACH_TYPES and not (c.type == "form" and c.has_captcha)
        for c in contacts
    )


def next_status(site_status: str, contacts: list[Contact]) -> str:
    """ok + correo o formulario sin CAPTCHA -> enriched; ok + solo teléfono/WhatsApp -> manual."""
    if site_status != "ok":
        return "discarded"
    if is_reachable(contacts):
        return "enriched"
    if contacts:
        return "manual"
    return "discarded"


def apply_result(session: Session, company: Company, result: EnrichResult) -> str:
    """Guarda el resultado y devuelve el nuevo estado de la empresa."""
    company.site_status = result.site_status
    company.enriched_at = utcnow()

    if result.site_status == "ok":
        if result.domain and result.domain != company.domain:
            owner = session.scalar(select(Company.id).where(Company.domain == result.domain))
            if owner is not None and owner != company.id:
                # El sitio redirige al de otra empresa ya registrada: es un duplicado.
                company.status = "discarded"
                return company.status
            company.domain = result.domain
        company.website_url = result.final_url or company.website_url
        company.stack = result.stack
        company.language = result.language

        existing = {(c.type, c.value) for c in company.contacts}
        for found in result.contacts:
            if (found.type, found.value) in existing:
                continue
            company.contacts.append(Contact(
                type=found.type, value=found.value, source_url=found.source_url,
                has_captcha=found.has_captcha,
            ))
            existing.add((found.type, found.value))
        company.has_whatsapp = any(c.type == "whatsapp" for c in company.contacts)

    company.status = next_status(result.site_status, list(company.contacts))
    session.flush()
    return company.status
