"""Interfaz común de las fuentes de descubrimiento."""

from __future__ import annotations

from collections.abc import Iterator
from dataclasses import dataclass, field
from typing import Any, Protocol

from outreach.config import SegmentConfig


@dataclass
class RawCompany:
    """Empresa tal como la entrega una fuente, antes de deduplicar."""

    name: str
    source: str
    source_ref: str
    # Página pública donde se puede verificar el registro (ficha DENUE, Google Maps).
    source_url: str
    legal_name: str | None = None
    website_url: str | None = None
    email: str | None = None
    phone: str | None = None
    address: str | None = None
    city: str | None = None
    state: str | None = None
    country: str = "MX"
    scian_code: str | None = None
    giro: str | None = None
    size_stratum: str | None = None
    extra: dict[str, Any] = field(default_factory=dict)


class Source(Protocol):
    name: str

    def search(self, segment: SegmentConfig, limit: int) -> Iterator[RawCompany]:
        """Entrega hasta `limit` empresas del segmento (puede entregar menos)."""
        ...


class SourceError(Exception):
    """La fuente respondió algo inesperado (token inválido, servicio caído)."""


def clean(value: Any) -> str | None:
    """Texto sin espacios sobrantes; vacío se vuelve None."""
    if value is None:
        return None
    text = " ".join(str(value).split())
    return text or None
