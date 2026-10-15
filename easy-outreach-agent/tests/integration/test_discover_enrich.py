"""Flujo de Fase 1 de punta a punta, sin red: DENUE simulado + sitios locales en tests/fixtures."""

from __future__ import annotations

import json

import httpx
import pytest
from sqlalchemy import select
from typer.testing import CliRunner

from conftest import FIXTURES
from outreach.db.models import Company, Run
from outreach.enrich.pipeline import apply_result, enrich_site
from outreach.sources.denue import build_url

ROWS = json.loads((FIXTURES / "denue" / "pagina.json").read_text(encoding="utf-8"))


def _company(session, domain, url=None, status="new") -> Company:
    company = Company(name=domain, domain=domain, website_url=url or f"https://{domain}",
                      source="denue", status=status)
    session.add(company)
    session.flush()
    return company


def _serve_clinic(web):
    web.redirect("https://www.sonrisaqro.mx", "https://sonrisaqro.mx/")
    web.page("https://sonrisaqro.mx/", "clinica_home.html")
    web.page("https://sonrisaqro.mx/contacto/", "clinica_contacto.html")


# -- enriquecimiento ------------------------------------------------------------------------

def test_enrich_clinic_site(session, web, client):
    _serve_clinic(web)
    company = _company(session, "sonrisaqro.mx", "https://www.sonrisaqro.mx")
    result = enrich_site(client, company.id, company.website_url)
    assert apply_result(session, company, result) == "enriched"

    assert company.site_status == "ok" and company.enriched_at is not None
    assert company.website_url == "https://sonrisaqro.mx/"
    assert company.language == "es-MX" and company.has_whatsapp is True
    assert company.stack["cms"] == ["wordpress"]
    assert company.stack["crm"] == ["hubspot"] and company.stack["booking"] == ["calendly"]
    by_type = {(c.type, c.value): c for c in company.contacts}
    assert set(by_type) == {
        ("email", "citas@sonrisaqro.mx"), ("email", "administracion@sonrisaqro.mx"),
        ("email", "recepcion@sonrisaqro.mx"), ("whatsapp", "+524421234567"),
        ("phone", "+524421234567"), ("form", "https://sonrisaqro.mx/contacto/"),
    }
    assert by_type[("email", "recepcion@sonrisaqro.mx")].source_url.endswith("/contacto/")
    assert by_type[("form", "https://sonrisaqro.mx/contacto/")].has_captcha is True
    # Nunca se pidió algo fuera del sitio (ni Facebook ni la agencia).
    assert all("sonrisaqro.mx" in url for url in web.urls())


def test_enrich_is_idempotent_for_contacts(session, web, client):
    _serve_clinic(web)
    company = _company(session, "sonrisaqro.mx")
    for _ in range(2):
        apply_result(session, company, enrich_site(client, company.id, company.website_url))
    assert len(company.contacts) == 6


def test_only_captcha_form_and_whatsapp_goes_to_manual(session, web, client):
    web.page("https://captcha.mx/", "clinica_contacto.html")
    html = web.routes["https://captcha.mx/"][2].replace("recepcion@sonrisaqro.mx", "")
    web.add("https://captcha.mx/", html + '<a href="https://wa.me/524420000000">wa</a>')
    company = _company(session, "captcha.mx")
    assert apply_result(session, company, enrich_site(client, company.id, company.website_url)) \
        == "manual"


@pytest.mark.parametrize(("setup", "expected"), [
    (lambda w: w.page("https://vendido.mx/", "parked.html"), "parked"),
    (lambda w: w.add("https://vendido.mx/", status=500), "http_error"),
    (lambda w: w.redirect("https://vendido.mx/", "https://www.facebook.com/vendido"), "no_site"),
    (lambda w: w.add("https://vendido.mx/robots.txt", "User-agent: *\nDisallow: /",
                     content_type="text/plain"), "robots"),
])
def test_invalid_sites_are_discarded(session, web, client, setup, expected):
    setup(web)
    company = _company(session, "vendido.mx")
    assert apply_result(session, company, enrich_site(client, company.id, company.website_url)) \
        == "discarded"
    assert company.site_status == expected
    assert "https://www.facebook.com/vendido" not in web.urls()


def test_https_failure_falls_back_to_http(session, web, client):
    web.add("https://viejo.mx", httpx.ConnectError("sin https"))
    web.page("http://viejo.mx", "simple_form.html")
    company = _company(session, "viejo.mx", "https://viejo.mx")
    assert apply_result(session, company, enrich_site(client, company.id, company.website_url)) \
        == "enriched"
    assert company.website_url == "http://viejo.mx"


def test_redirect_to_another_known_company_is_duplicate(session, web, client):
    web.redirect("https://marca-vieja.mx/", "https://sonrisaqro.mx/")
    web.page("https://sonrisaqro.mx/", "clinica_home.html")
    _company(session, "sonrisaqro.mx")
    old = _company(session, "marca-vieja.mx")
    assert apply_result(session, old, enrich_site(client, old.id, old.website_url)) == "discarded"
    assert old.domain == "marca-vieja.mx"


# -- CLI de punta a punta -------------------------------------------------------------------

@pytest.fixture
def cli_env(monkeypatch, db_url, web):
    from outreach import cli
    from outreach.config import get_secrets
    from outreach.http.client import PoliteClient

    monkeypatch.setenv("DATABASE_URL", db_url)
    monkeypatch.setenv("DENUE_TOKEN", "T")
    get_secrets.cache_clear()
    # Cada comando abre y cierra su propio cliente, como en producción.
    monkeypatch.setattr(cli, "_client", lambda: PoliteClient(
        user_agent="EasyOutreachBot/1.0", transport=httpx.MockTransport(web.handler),
        sleep=lambda _s: None,
    ))
    yield cli
    get_secrets.cache_clear()


def test_cli_discover_enrich_stats(cli_env, web, db_url):
    from outreach.config import get_segment
    from outreach.db.base import make_engine, make_session_factory

    query = get_segment("qro-salud").denue
    for municipio in query.municipios:
        for actividad in query.actividades:
            rows = ROWS if (municipio, actividad) == ("014", "621211") else []
            web.add(build_url(query, municipio, actividad, 0, 1, 500, "T"),
                    json.dumps(rows or ["No se encontraron resultados"]),
                    content_type="application/json")
    _serve_clinic(web)
    # estéticabella.com.mx (deducido del correo) no responde
    web.add("https://esteticabella.com.mx", httpx.ConnectError("caído"))
    web.add("http://esteticabella.com.mx", httpx.ConnectError("caído"))

    runner = CliRunner()
    out = runner.invoke(cli_env.app, ["discover", "-s", "qro-salud", "--limit", "50"])
    assert out.exit_code == 0, out.output
    assert "nuevas 2" in out.output and "sin sitio 1" in out.output

    out = runner.invoke(cli_env.app, ["enrich", "-s", "qro-salud", "--workers", "2"])
    assert out.exit_code == 0, out.output
    assert "enriched 1" in out.output and "discarded 1" in out.output

    out = runner.invoke(cli_env.app, ["stats", "-s", "qro-salud"])
    assert out.exit_code == 0, out.output
    assert "correo o formulario sin CAPTCHA: 1/2 (50%)" in out.output

    with make_session_factory(make_engine(db_url))() as session:
        runs = session.scalars(select(Run).order_by(Run.id)).all()
        assert [(r.job, r.status) for r in runs] == [("discover", "finished"),
                                                     ("enrich", "finished")]
        assert runs[0].stats["nueva"] == 2
        clinic = session.scalar(select(Company).where(Company.domain == "sonrisaqro.mx"))
        # El correo que la clínica registró en DENUE queda con la ficha como fuente.
        denue_email = next(c for c in clinic.contacts if c.value == "contacto@sonrisaqro.mx")
        assert "inegi.org.mx" in denue_email.source_url


def test_cli_discover_rejects_disabled_source(cli_env):
    out = CliRunner().invoke(cli_env.app, ["discover", "-s", "qro-salud", "--source", "places"])
    assert out.exit_code == 1
    assert "apagada" in out.output
