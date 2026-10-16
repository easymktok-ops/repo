from __future__ import annotations

from conftest import FIXTURES
from outreach.enrich.contacts import (
    accept_email,
    contact_links,
    extract_contacts,
    normalize_phone,
)
from outreach.enrich.site import is_parked
from outreach.enrich.stack import detect_language, detect_stack

HOME = "https://sonrisaqro.mx/"


def _site(name: str) -> str:
    return (FIXTURES / "sites" / name).read_text(encoding="utf-8")


def _by_type(contacts, kind):
    return {c.value for c in contacts if c.type == kind}


def test_extract_contacts_from_home():
    contacts = extract_contacts(HOME, _site("clinica_home.html"), "sonrisaqro.mx")
    assert _by_type(contacts, "email") == {"citas@sonrisaqro.mx", "administracion@sonrisaqro.mx"}
    assert _by_type(contacts, "whatsapp") == {"+524421234567"}
    assert _by_type(contacts, "phone") == {"+524421234567"}
    # El buscador del sitio no es un formulario de contacto.
    assert _by_type(contacts, "form") == set()
    assert all(c.source_url == HOME for c in contacts)


def test_third_party_and_placeholder_emails_are_rejected():
    contacts = extract_contacts(HOME, _site("clinica_home.html"), "sonrisaqro.mx")
    emails = _by_type(contacts, "email")
    assert "hola@agenciax.com" not in emails      # la agencia que hizo el sitio
    assert "no-real@sentry.io" not in emails      # dentro de un <script>


def test_contact_page_form_with_captcha():
    url = "https://sonrisaqro.mx/contacto/"
    contacts = extract_contacts(url, _site("clinica_contacto.html"), "sonrisaqro.mx")
    assert _by_type(contacts, "email") == {"recepcion@sonrisaqro.mx"}
    forms = [c for c in contacts if c.type == "form"]
    assert len(forms) == 1
    assert forms[0].value == url and forms[0].has_captcha is True


def test_form_without_captcha_and_newsletter_ignored():
    url = "https://tallernorte.mx/"
    contacts = extract_contacts(url, _site("simple_form.html"), "tallernorte.mx")
    forms = [c for c in contacts if c.type == "form"]
    assert len(forms) == 1 and forms[0].has_captcha is False


def test_accept_email_rules():
    assert accept_email("Ventas@SonrisaQro.mx", "sonrisaqro.mx") == "ventas@sonrisaqro.mx"
    assert accept_email("citas@mail.sonrisaqro.mx", "sonrisaqro.mx") == "citas@mail.sonrisaqro.mx"
    assert accept_email("dra.lopez@gmail.com", "sonrisaqro.mx") == "dra.lopez@gmail.com"
    assert accept_email("hola@otraempresa.mx", "sonrisaqro.mx") is None
    assert accept_email("dra@hotlmail.com", "sonrisaqro.mx") is None  # rebotaría
    assert accept_email("logo@2x.png", "2x.png") is None
    assert accept_email("nombre@sonrisaqro.mx", "sonrisaqro.mx") is None
    assert accept_email("sin-arroba", "sonrisaqro.mx") is None


def test_home_candidates():
    from outreach.enrich.site import home_candidates

    assert home_candidates("https://www.clinica.mx") == [
        "https://www.clinica.mx", "http://www.clinica.mx/", "https://clinica.mx/",
    ]


def test_normalize_phone():
    assert normalize_phone("442 123 4567") == "+524421234567"
    assert normalize_phone("+52 1 442 123 4567") == "+524421234567"
    assert normalize_phone("0052 442 123 4567") == "+524421234567"
    assert normalize_phone("123") is None


def test_whatsapp_link_variants():
    html = """
      <a href="https://api.whatsapp.com/send?phone=524421112233">a</a>
      <a href="whatsapp://send?phone=+52 442 999 8877">b</a>
      <a href="https://wa.me/message/ABCDEF">c</a>
    """
    contacts = extract_contacts("https://x.mx/", html, "x.mx")
    assert _by_type(contacts, "whatsapp") == {"+524421112233", "+524429998877"}


def test_contact_links_only_internal():
    links = contact_links("https://sonrisaqro.mx/", _site("clinica_home.html"), "sonrisaqro.mx")
    assert links == ["https://sonrisaqro.mx/contacto/"]


def test_detect_stack():
    home = detect_stack(_site("clinica_home.html"))
    assert home["cms"] == ["wordpress"]
    assert "ga4" in home["analytics"]
    assert home["generator"].startswith("WordPress")
    contact = detect_stack(_site("clinica_contacto.html"))
    assert contact["crm"] == ["hubspot"]
    assert contact["booking"] == ["calendly"]
    assert detect_stack(_site("simple_form.html"))["cms"] == ["a_la_medida"]
    assert detect_stack("<html></html>", {"X-Shopify-Stage": "production"})["cms"] == ["shopify"]


def test_detect_language():
    assert detect_language(_site("clinica_home.html")) == "es-MX"
    assert detect_language("<html><body></body></html>") is None


def test_parked_and_placeholder_pages():
    assert is_parked(_site("parked.html"))
    assert is_parked(_site("construccion.html"))
    assert not is_parked(_site("clinica_home.html"))
    assert not is_parked(_site("simple_form.html"))
