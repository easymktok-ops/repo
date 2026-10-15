"""Lista de supresión global. Se consulta antes de cualquier envío (regla 6)."""

from __future__ import annotations

from sqlalchemy import select
from sqlalchemy.orm import Session

from outreach.db.models import Suppression
from outreach.domains import FREE_MAIL_DOMAINS, normalize_domain, normalize_email


def is_suppressed(session: Session, address: str) -> bool:
    """True si el correo, o su dominio completo, está en la lista de supresión."""
    email = normalize_email(address)
    domain = normalize_domain(address)
    candidates = [v for v in (email, domain) if v]
    if not candidates:
        # Un destino que no se puede normalizar nunca se envía.
        return True
    hit = session.scalar(select(Suppression.id).where(Suppression.value.in_(candidates)).limit(1))
    return hit is not None


def suppress(
    session: Session, address: str, reason: str, source: str | None = None, *, whole_domain=False
) -> Suppression:
    """Agrega un correo (o su dominio) a la lista. Idempotente."""
    if whole_domain:
        value = normalize_domain(address)
        if value in FREE_MAIL_DOMAINS:
            raise ValueError(f"no se puede suprimir el dominio de correo gratuito {value}")
        kind = "domain"
    else:
        value = normalize_email(address)
        kind = "email"
    if not value:
        raise ValueError(f"dirección inválida para supresión: {address!r}")

    existing = session.scalar(select(Suppression).where(Suppression.value == value))
    if existing:
        return existing
    entry = Suppression(value=value, kind=kind, reason=reason, source=source)
    session.add(entry)
    session.flush()
    return entry
