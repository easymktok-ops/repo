"""Extracción de contactos publicados por la empresa en su propio sitio (regla 4).

Cada contacto sale con la URL de la página donde se encontró. Solo se aceptan correos
del dominio de la empresa o de proveedores gratuitos (un gmail publicado por la clínica);
correos de terceros (la agencia que hizo el sitio, plantillas) se descartan.
"""

from __future__ import annotations

import re
from dataclasses import dataclass
from urllib.parse import parse_qs, unquote, urljoin, urlsplit

from bs4 import BeautifulSoup, Tag

from outreach.domains import FREE_MAIL_DOMAINS, normalize_domain, normalize_email

EMAIL_RE = re.compile(r"[a-z0-9][a-z0-9._%+-]{0,63}@(?:[a-z0-9-]+\.)+[a-z]{2,24}", re.I)
FILE_SUFFIXES = (".png", ".jpg", ".jpeg", ".gif", ".webp", ".svg", ".css", ".js")
PLACEHOLDER_DOMAINS = frozenset({
    "example.com", "example.mx", "domain.com", "dominio.com", "tudominio.com", "email.com",
    "correo.com", "mail.com", "sentry.io", "wixpress.com", "sentry.wixpress.com",
    "yourdomain.com", "company.com", "empresa.com",
})
PLACEHOLDER_LOCALS = frozenset({"nombre", "name", "usuario", "user", "email", "correo", "tu"})

CAPTCHA_MARKERS = (
    "g-recaptcha", "recaptcha/api.js", "grecaptcha", "recaptcha/enterprise.js",
    "h-captcha", "hcaptcha.com", "cf-turnstile", "challenges.cloudflare.com/turnstile",
    "frc-captcha", "friendlycaptcha",
)
WHATSAPP_HOSTS = ("wa.me", "api.whatsapp.com", "web.whatsapp.com", "chat.whatsapp.com")

# Palabras en enlaces que llevan a páginas de contacto (para visitar después del inicio).
CONTACT_LINK_WORDS = (
    "contacto", "contactanos", "contáctanos", "contact", "cita", "citas", "agenda",
    "ubicacion", "ubicación", "sucursales",
)


@dataclass(frozen=True)
class FoundContact:
    type: str  # email / form / whatsapp / phone
    value: str
    source_url: str
    has_captcha: bool | None = None


def _decode_cfemail(encoded: str) -> str | None:
    """Correo protegido por Cloudflare (data-cfemail): XOR con el primer byte."""
    try:
        data = bytes.fromhex(encoded)
    except ValueError:
        return None
    if not data:
        return None
    key = data[0]
    return bytes(b ^ key for b in data[1:]).decode("utf-8", errors="ignore") or None


def _same_site(domain: str, site_domain: str) -> bool:
    return domain == site_domain or domain.endswith("." + site_domain)


def accept_email(raw: str, site_domain: str) -> str | None:
    email = normalize_email(raw.strip().strip(".,;:"))
    if not email or not EMAIL_RE.fullmatch(email):
        return None
    local, domain = email.split("@")
    if email.endswith(FILE_SUFFIXES) or domain in PLACEHOLDER_DOMAINS:
        return None
    if local in PLACEHOLDER_LOCALS:
        return None
    if _same_site(domain, site_domain) or domain in FREE_MAIL_DOMAINS:
        return email
    return None


def _digits(value: str) -> str:
    return re.sub(r"\D", "", value)


def normalize_phone(value: str, country_code: str = "52") -> str | None:
    """Teléfono como +52XXXXXXXXXX. Acepta 10 dígitos nacionales o con lada de país."""
    digits = _digits(value)
    if digits.startswith("00"):
        digits = digits[2:]
    if len(digits) == 10:
        digits = country_code + digits
    if digits.startswith("521") and len(digits) == 13:  # formato móvil antiguo de México
        digits = "52" + digits[3:]
    if not 11 <= len(digits) <= 15:
        return None
    return "+" + digits


def _whatsapp_number(href: str) -> str | None:
    parts = urlsplit(href)
    host = (parts.hostname or "").lower()
    if parts.scheme == "whatsapp" or host in ("api.whatsapp.com", "web.whatsapp.com"):
        phone = parse_qs(parts.query).get("phone", [""])[0]
        return normalize_phone(phone) if phone else None
    if host == "wa.me":
        return normalize_phone(parts.path.strip("/").split("/")[0])
    return None


def has_captcha(html: str) -> bool:
    lowered = html.lower()
    return any(marker in lowered for marker in CAPTCHA_MARKERS)


def _is_contact_form(form: Tag) -> bool:
    if form.get("role") == "search" or "search" in " ".join(form.get("class", [])).lower():
        return False
    fields = [
        f for f in form.find_all(["input", "textarea", "select"])
        if f.name != "input" or (f.get("type") or "text").lower()
        not in ("hidden", "submit", "button", "image", "reset", "checkbox", "radio")
    ]
    if not fields:
        return False
    names = " ".join(
        f"{f.get('name', '')} {f.get('id', '')} {f.get('type', '')} {f.get('placeholder', '')}"
        for f in fields
    ).lower()
    has_message = form.find("textarea") is not None
    asks_contact = any(w in names for w in ("mail", "correo", "tel", "phone", "whats"))
    # Un buscador o un alta a newsletter (un solo campo) no es un formulario de contacto.
    return has_message or (asks_contact and len(fields) >= 2)


def extract_contacts(page_url: str, html: str, site_domain: str) -> list[FoundContact]:
    soup = BeautifulSoup(html, "html.parser")
    found: dict[tuple[str, str], FoundContact] = {}

    def add(kind: str, value: str | None, captcha: bool | None = None) -> None:
        if value and (kind, value) not in found:
            found[(kind, value)] = FoundContact(kind, value, page_url, captcha)

    for a in soup.find_all("a", href=True):
        href = a["href"].strip()
        lowered = href.lower()
        if lowered.startswith("mailto:"):
            address = unquote(href[7:].split("?")[0])
            for part in address.split(","):
                add("email", accept_email(part, site_domain))
        elif lowered.startswith("tel:"):
            add("phone", normalize_phone(unquote(href[4:])))
        elif lowered.startswith("whatsapp:") or any(h in lowered for h in WHATSAPP_HOSTS):
            add("whatsapp", _whatsapp_number(href))

    for node in soup.select("[data-cfemail]"):
        decoded = _decode_cfemail(node.get("data-cfemail", ""))
        if decoded:
            add("email", accept_email(decoded, site_domain))

    for tag in soup(["script", "style", "noscript", "template"]):
        tag.decompose()
    for match in EMAIL_RE.findall(soup.get_text(" ")):
        add("email", accept_email(match, site_domain))

    captcha = has_captcha(html)
    for form in soup.find_all("form"):
        if _is_contact_form(form):
            # Se guarda la página que contiene el formulario: ahí se llenará (Fase 4).
            add("form", page_url, captcha)
            break

    return list(found.values())


def contact_links(page_url: str, html: str, site_domain: str, limit: int = 3) -> list[str]:
    """Enlaces internos que probablemente lleven a la página de contacto."""
    soup = BeautifulSoup(html, "html.parser")
    links: list[str] = []
    for a in soup.find_all("a", href=True):
        url = urljoin(page_url, a["href"].strip()).split("#")[0]
        if not url.startswith(("http://", "https://")) or url in links or url == page_url:
            continue
        domain = normalize_domain(url)
        if not domain or not _same_site(domain, site_domain):
            continue
        haystack = (unquote(urlsplit(url).path) + " " + a.get_text(" ")).lower()
        if any(word in haystack for word in CONTACT_LINK_WORDS):
            links.append(url)
            if len(links) >= limit:
                break
    return links
