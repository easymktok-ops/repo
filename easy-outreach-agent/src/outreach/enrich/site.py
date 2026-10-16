"""Visita al sitio de la empresa: inicio + páginas de contacto; detecta sitios no válidos."""

from __future__ import annotations

from dataclasses import dataclass, field
from urllib.parse import urlsplit

from bs4 import BeautifulSoup

from outreach.domains import is_platform_domain, normalize_domain
from outreach.enrich.contacts import contact_links
from outreach.http.client import Page, PoliteClient

PARKED_MARKERS = (
    "this domain is for sale", "domain is for sale", "buy this domain",
    "este dominio está a la venta", "este dominio esta a la venta", "dominio en venta",
    "sedoparking", "hugedomains.com",
    "afternic", "parkingcrew", "this domain has been registered", "domain parking",
    "account suspended", "cuenta suspendida", "this account has been suspended",
)
# Solo cuentan si la página casi no tiene texto (una página real puede mencionarlas).
PLACEHOLDER_MARKERS = (
    "coming soon", "under construction", "en construcción", "en construccion", "próximamente",
    "proximamente", "it works!", "index of /", "default web site page", "welcome to nginx",
    "future home of something quite cool",
)
PLACEHOLDER_MAX_TEXT = 400


@dataclass
class SiteVisit:
    status: str  # ver SITE_STATUSES en db.models
    final_url: str | None = None
    domain: str | None = None
    pages: list[Page] = field(default_factory=list)
    detail: str | None = None


def visible_text(html: str) -> str:
    soup = BeautifulSoup(html, "html.parser")
    for tag in soup(["script", "style", "noscript", "template"]):
        tag.decompose()
    return " ".join(soup.get_text(" ").split())


def is_parked(html: str) -> bool:
    text = visible_text(html).lower()
    if any(m in text for m in PARKED_MARKERS):
        return True
    return len(text) < PLACEHOLDER_MAX_TEXT and any(m in text for m in PLACEHOLDER_MARKERS)


def _own_site(url: str) -> bool:
    return not is_platform_domain(normalize_domain(url))


def _status_for_error(page: Page) -> str:
    if page.error == "redirect_stopped":
        return "no_site"
    if page.error == "robots":
        return "robots"
    if page.error == "not_html":
        return "not_html"
    if page.error is not None:
        return "unreachable"
    return "http_error"


def home_candidates(website_url: str) -> list[str]:
    """Variantes a probar si la URL registrada no responde.

    Muchos registros traen "www.dominio.mx" aunque el certificado solo cubra "dominio.mx",
    o el sitio solo sirva http.
    """
    parts = urlsplit(website_url)
    host = parts.hostname or ""
    bare = host.removeprefix("www.")
    path = parts.path or "/"
    candidates = [website_url]
    seen = {(parts.scheme, host, path)}
    for scheme, h in (("http", host), ("https", bare), ("https", "www." + bare)):
        if (scheme, h, path) not in seen:
            seen.add((scheme, h, path))
            candidates.append(f"{scheme}://{h}{path}")
    return candidates


def _fetch_home(client: PoliteClient, website_url: str) -> Page:
    page = Page(url=website_url, final_url=website_url, error="network")
    for url in home_candidates(website_url):
        page = client.fetch_page(url, follow=_own_site)
        if page.error not in ("network", "timeout"):
            return page
    return page


def visit_site(client: PoliteClient, website_url: str, max_pages: int = 4) -> SiteVisit:
    home = _fetch_home(client, website_url)
    if not home.ok:
        detail = home.error or f"HTTP {home.status}"
        return SiteVisit(_status_for_error(home), home.final_url, detail=detail)

    domain = normalize_domain(home.final_url)
    if not domain or is_platform_domain(domain):
        return SiteVisit("no_site", home.final_url, domain, detail="redirige a una plataforma")
    if is_parked(home.text):
        return SiteVisit("parked", home.final_url, domain, detail="dominio estacionado o vacío")

    visit = SiteVisit("ok", home.final_url, domain, pages=[home])
    for url in contact_links(home.final_url, home.text, domain, limit=max_pages - 1):
        page = client.fetch_page(url, follow=_own_site)
        if page.ok:
            visit.pages.append(page)
    return visit
