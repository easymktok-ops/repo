from __future__ import annotations

import json

import httpx
import pytest

from conftest import FIXTURES
from outreach.config import DenueQuery, PlacesQuery, SegmentConfig
from outreach.sources.base import SourceError
from outreach.sources.denue import API_BASE, DenueSource, build_url, parse_response, parse_row
from outreach.sources.places import SEARCH_URL, PlacesSource

ROWS = json.loads((FIXTURES / "denue" / "pagina.json").read_text(encoding="utf-8"))


def _segment(**kw) -> SegmentConfig:
    base = dict(name="qro-salud", country="MX", city="Querétaro", giro="dental")
    return SegmentConfig(**{**base, **kw})


def test_denue_query_validates_codes():
    with pytest.raises(ValueError, match="SCIAN"):
        DenueQuery(entidad="22", actividades=["62121"])
    with pytest.raises(ValueError, match="municipio"):
        DenueQuery(entidad="22", municipios=["14"], actividades=["621211"])
    with pytest.raises(ValueError):
        DenueQuery(entidad="22", actividades=["621211"], estratos=[9])


def test_build_url_puts_activity_in_its_level():
    q = DenueQuery(entidad="22", actividades=["621211"])
    url = build_url(q, "014", "621211", 0, 1, 500, "TOKEN")
    assert url == f"{API_BASE}/BuscarAreaActEstr/22/014/0/0/0/0/0/0/621211/0/1/500/0/0/TOKEN"
    url = build_url(q, "0", "332", 3, 501, 1000, "T")
    assert url.endswith("/BuscarAreaActEstr/22/0/0/0/0/0/332/0/0/0/501/1000/0/3/T")


def test_parse_row():
    company = parse_row(ROWS[0])
    assert company.name == "CLINICA DENTAL SONRISA"
    assert company.source == "denue" and company.source_ref == "1001"
    assert company.source_url.endswith("idee=1001")
    assert company.website_url == "https://www.sonrisaqro.mx"
    assert company.email == "contacto@sonrisaqro.mx"
    assert company.city == "Querétaro" and company.state == "Querétaro"
    assert company.address == "AVENIDA ZARAGOZA 120, int. 3, CENTRO, CP 76000"
    assert company.scian_code == "621211" and company.size_stratum == "6 a 10 personas"
    assert parse_row({"Id": "", "Nombre": "X"}) is None


def test_parse_response_messages():
    assert parse_response(ROWS) == ROWS
    assert parse_response(["No se encontraron resultados"]) == []
    with pytest.raises(SourceError, match="Token"):
        parse_response("Token no válido")


def test_denue_source_paginates_and_stops_at_limit(web, client):
    q = DenueQuery(entidad="22", municipios=["014"], actividades=["621211"])
    web.add(build_url(q, "014", "621211", 0, 1, 2, "T"), json.dumps(ROWS[:2]),
            content_type="application/json")
    web.add(build_url(q, "014", "621211", 0, 3, 4, "T"), json.dumps(ROWS[2:]),
            content_type="application/json")
    source = DenueSource(client, "T", page_size=2)
    names = [c.name for c in source.search(_segment(denue=q), limit=10)]
    assert names == [r["Nombre"] for r in ROWS]
    assert len([u for u in web.urls() if "BuscarAreaActEstr" in u]) == 2

    limited = list(DenueSource(client, "T", page_size=2).search(_segment(denue=q), limit=1))
    assert len(limited) == 1


def test_denue_requires_token_and_query(client):
    with pytest.raises(SourceError, match="DENUE_TOKEN"):
        DenueSource(client, "")
    with pytest.raises(SourceError, match="consulta denue"):
        list(DenueSource(client, "T").search(_segment(), limit=1))


def test_denue_http_error_becomes_source_error(web, client):
    q = DenueQuery(entidad="22", municipios=["014"], actividades=["621211"])
    web.add(build_url(q, "014", "621211", 0, 1, 500, "T"), status=500)
    with pytest.raises(SourceError, match="HTTP 500"):
        list(DenueSource(client, "T").search(_segment(denue=q), limit=5))


def test_places_source_paginates(web, client):
    pages = [
        {"places": [{"id": "p1", "displayName": {"text": "Clínica Uno"},
                     "websiteUri": "https://uno.mx/", "googleMapsUri": "https://maps.google.com/?cid=1",
                     "nationalPhoneNumber": "442 111 2233"}],
         "nextPageToken": "next"},
        {"places": [{"id": "p2", "displayName": {"text": "Clínica Dos"}}]},
    ]
    seen = []

    def handler(request):
        body = json.loads(request.content)
        seen.append((body, request.headers["x-goog-api-key"], request.headers["x-goog-fieldmask"]))
        return httpx.Response(200, json=pages[1] if body.get("pageToken") else pages[0])

    web.routes[SEARCH_URL] = handler
    seg = _segment(places=PlacesQuery(queries=["clínica dental en Querétaro"]))
    found = list(PlacesSource(client, "KEY").search(seg, limit=10))
    assert [c.name for c in found] == ["Clínica Uno", "Clínica Dos"]
    assert found[0].website_url == "https://uno.mx/" and found[0].source_url.startswith("https://maps")
    assert seen[0][0]["textQuery"] == "clínica dental en Querétaro" and seen[0][1] == "KEY"
    assert "places.websiteUri" in seen[0][2]
