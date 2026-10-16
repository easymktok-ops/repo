from __future__ import annotations

from sqlalchemy import func, select

from outreach.config import load_segments
from outreach.db.models import Company, Run, Segment
from outreach.sending.suppression import suppress
from outreach.sources.base import RawCompany
from outreach.sources.ingest import ingest, ingest_many, site_of, sync_segments


def _raw(ref="1", **kw) -> RawCompany:
    base = dict(name=f"Empresa {ref}", source="denue", source_ref=ref,
                source_url=f"https://www.inegi.org.mx/app/mapa/denue/default.aspx?idee={ref}")
    return RawCompany(**{**base, **kw})


def _setup(session):
    segment = sync_segments(session, load_segments())["qro-salud"]
    run = Run(job="discover")
    session.add(run)
    session.flush()
    return segment, run


def test_sync_segments_is_idempotent(session):
    sync_segments(session, load_segments())
    sync_segments(session, load_segments())
    assert session.scalar(select(func.count(Segment.id))) == len(load_segments())


def test_site_of():
    assert site_of(_raw(website_url="https://www.clinica.mx/inicio")) == (
        "clinica.mx", "https://www.clinica.mx/inicio")
    # Página en Facebook: se usa el dominio del correo corporativo.
    assert site_of(_raw(website_url="https://facebook.com/x", email="info@bella.com.mx")) == (
        "bella.com.mx", "https://bella.com.mx")
    assert site_of(_raw(email="dra@gmail.com")) == (None, None)
    # Sitio capturado sin TLD: se usa el dominio del correo.
    assert site_of(_raw(website_url="https://www.dentnuvo", email="dra@dentnuvo.com")) == (
        "dentnuvo.com", "https://dentnuvo.com")
    # "Sitios" que en realidad son correos gratuitos mal escritos.
    assert site_of(_raw(website_url="https://gmail.mx")) == (None, None)
    assert site_of(_raw(email="x@hotlmail.com")) == (None, None)


def test_ingest_dedupes_by_domain_and_merges(session):
    segment, run = _setup(session)
    first = _raw("1", website_url="https://www.clinica.mx")
    assert ingest(session, first, segment, run) == "nueva"
    again = _raw("2", website_url="http://clinica.mx/", phone="4421112233", giro="Dental")
    assert ingest(session, again, segment, run) == "duplicada"
    company = session.scalar(select(Company).where(Company.domain == "clinica.mx"))
    assert company.phone == "4421112233" and company.giro == "Dental"
    assert session.scalar(select(func.count(Company.id))) == 1


def test_ingest_without_site(session):
    segment, run = _setup(session)
    raw = _raw("9", email="dra@gmail.com")
    assert ingest(session, raw, segment, run) == "sin_sitio"
    assert ingest(session, raw, segment, run, require_site=False) == "nueva"
    assert ingest(session, raw, segment, run, require_site=False) == "duplicada"


def test_ingest_skips_suppressed_domain(session):
    segment, run = _setup(session)
    suppress(session, "alguien@noquiere.mx", "baja", whole_domain=True)
    assert ingest(session, _raw(website_url="https://noquiere.mx"), segment, run) == "suprimida"


def test_ingest_saves_source_contacts_with_source_url(session):
    segment, run = _setup(session)
    raw = _raw("5", website_url="www.sonrisa.mx", email="contacto@sonrisa.mx",
               phone="442 123 4567")
    ingest(session, raw, segment, run)
    company = session.scalar(select(Company).where(Company.domain == "sonrisa.mx"))
    assert {(c.type, c.value) for c in company.contacts} == {
        ("email", "contacto@sonrisa.mx"), ("phone", "+524421234567")}
    assert all(c.source_url == raw.source_url for c in company.contacts)
    assert company.status == "new" and company.segment.name == "qro-salud"


def test_ingest_many_counts_and_limit(session):
    segment, run = _setup(session)
    raws = [_raw(str(i), website_url=f"https://e{i}.mx") for i in range(10)]
    raws.insert(1, _raw("x"))  # sin sitio
    stats = ingest_many(session, iter(raws), segment, run, limit=3, commit_every=2)
    assert stats["nueva"] == 3 and stats["sin_sitio"] == 1 and stats["leidas"] == 4


def test_ingest_skips_domains_that_do_not_exist(session):
    segment, run = _setup(session)
    alive = {"vive.mx"}
    raws = [_raw("1", website_url="https://www.vive.mx"), _raw("2", website_url="https://murio.mx")]
    stats = ingest_many(session, iter(raws), segment, run, limit=10,
                        site_exists=lambda d: d in alive)
    assert stats["nueva"] == 1 and stats["dominio_inexistente"] == 1
