"""Normalización de dominios y correos (base de la deduplicación y la supresión)."""

from __future__ import annotations

from urllib.parse import urlsplit

# Proveedores de correo gratuito: suprimir el dominio completo nunca aplica a ellos.
FREE_MAIL_DOMAINS = frozenset({
    "gmail.com", "hotmail.com", "outlook.com", "live.com", "yahoo.com", "yahoo.com.mx",
    "icloud.com", "prodigy.net.mx", "aol.com", "protonmail.com",
})


def normalize_domain(value: str | None) -> str | None:
    """Convierte una URL, dominio o correo en un dominio canónico (sin www, minúsculas)."""
    if not value:
        return None
    raw = value.strip().lower()
    if "@" in raw and "://" not in raw:
        raw = raw.rsplit("@", 1)[1]
    if "://" not in raw:
        raw = "http://" + raw
    host = urlsplit(raw).hostname
    if not host or "." not in host:
        return None
    host = host.rstrip(".")
    if host.startswith("www."):
        host = host[4:]
    return host


def normalize_email(value: str | None) -> str | None:
    if not value or "@" not in value:
        return None
    local, _, domain = value.strip().lower().rpartition("@")
    domain = normalize_domain(domain)
    if not local or not domain:
        return None
    return f"{local}@{domain}"


# Redes y directorios: una "página web" ahí no es un sitio propio que se pueda auditar.
PLATFORM_DOMAINS = frozenset({
    "facebook.com", "fb.com", "instagram.com", "twitter.com", "x.com", "tiktok.com",
    "linkedin.com", "youtube.com", "wa.me", "whatsapp.com", "linktr.ee", "google.com",
    "goo.gl", "maps.app.goo.gl", "business.site", "sites.google.com", "doctoralia.com.mx",
    "doctoralia.com", "mercadolibre.com.mx", "blogspot.com", "seccionamarilla.com.mx",
})


def is_platform_domain(domain: str | None) -> bool:
    if not domain:
        return False
    return any(domain == p or domain.endswith("." + p) for p in PLATFORM_DOMAINS)
