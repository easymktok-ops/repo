"""Conteos del embudo. En Fase 1: cuántas empresas tienen sitio válido y un canal de contacto."""

from __future__ import annotations

from collections import Counter
from dataclasses import dataclass, field

from sqlalchemy import select
from sqlalchemy.orm import Session, selectinload

from outreach.db.models import Company, Segment
from outreach.enrich.pipeline import is_reachable


@dataclass
class DiscoveryStats:
    total: int = 0
    enriched: int = 0                 # visitadas por `enrich`
    site_ok: int = 0
    reachable: int = 0                # sitio válido + correo o formulario sin CAPTCHA
    any_channel: int = 0              # sitio válido + cualquier canal (incluye WhatsApp/teléfono)
    by_status: Counter[str] = field(default_factory=Counter)
    by_site_status: Counter[str] = field(default_factory=Counter)
    by_channel: Counter[str] = field(default_factory=Counter)

    @property
    def reachable_rate(self) -> float:
        return self.reachable / self.enriched if self.enriched else 0.0

    @property
    def any_channel_rate(self) -> float:
        return self.any_channel / self.enriched if self.enriched else 0.0


def discovery_stats(session: Session, segment: str | None = None) -> DiscoveryStats:
    query = select(Company).options(selectinload(Company.contacts))
    if segment:
        query = query.join(Segment).where(Segment.name == segment)

    stats = DiscoveryStats()
    for company in session.scalars(query):
        stats.total += 1
        stats.by_status[company.status] += 1
        if company.site_status is None:
            continue
        stats.enriched += 1
        stats.by_site_status[company.site_status] += 1
        if company.site_status != "ok":
            continue
        stats.site_ok += 1
        for kind in {c.type for c in company.contacts}:
            stats.by_channel[kind] += 1
        if company.contacts:
            stats.any_channel += 1
        if is_reachable(list(company.contacts)):
            stats.reachable += 1
    return stats
