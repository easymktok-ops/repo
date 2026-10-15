"""Fuente principal: API del DENUE (INEGI). Gratis con token.

Usa BuscarAreaActEstr, que acota por entidad, municipio, actividad SCIAN y estrato:
https://www.inegi.org.mx/servicios/api_denue.html
"""

from __future__ import annotations

from collections.abc import Iterator
from itertools import product
from typing import Any

from outreach.config import DenueQuery, SegmentConfig
from outreach.http.client import HttpError, PoliteClient
from outreach.logging import get_logger
from outreach.sources.base import RawCompany, SourceError, clean

API_BASE = "https://www.inegi.org.mx/app/api/denue/v1/consulta"
# Ficha pública del establecimiento en el mapa del DENUE (es la URL fuente del registro).
FICHA_URL = "https://www.inegi.org.mx/app/mapa/denue/default.aspx?idee={id}"
NO_RESULTS = "no se encontraron resultados"

log = get_logger("sources.denue")


def _activity_path(code: str) -> list[str]:
    """Sector/subsector/rama/clase: solo se llena el nivel de la clave, el resto va en 0."""
    levels = {2: 0, 3: 1, 4: 2, 6: 3}
    path = ["0", "0", "0", "0"]
    path[levels[len(code)]] = code
    return path


def build_url(
    query: DenueQuery, municipio: str, actividad: str, estrato: int, start: int, end: int,
    token: str,
) -> str:
    # entidad/municipio/localidad/ageb/manzana/sector/subsector/rama/clase/nombre/
    # registro_inicial/registro_final/id/estrato/token
    parts = [
        query.entidad, municipio, "0", "0", "0", *_activity_path(actividad), "0",
        str(start), str(end), "0", str(estrato), token,
    ]
    return f"{API_BASE}/BuscarAreaActEstr/" + "/".join(parts)


def _website(value: str | None) -> str | None:
    value = clean(value)
    if not value:
        return None
    value = value.lower()
    if "://" not in value:
        value = "https://" + value
    return value


def _address(row: dict[str, Any]) -> str | None:
    street = " ".join(
        p for p in (clean(row.get("Tipo_vialidad")), clean(row.get("Calle"))) if p
    )
    number = clean(row.get("Num_Exterior"))
    interior = clean(row.get("Num_Interior"))
    parts = [
        f"{street} {number}".strip() if street else number,
        f"int. {interior}" if interior else None,
        clean(row.get("Colonia")),
        f"CP {clean(row.get('CP'))}" if clean(row.get("CP")) else None,
    ]
    return ", ".join(p for p in parts if p) or None


def parse_row(row: dict[str, Any]) -> RawCompany | None:
    ref = clean(row.get("Id"))
    name = clean(row.get("Nombre"))
    if not ref or not name:
        return None
    # Ubicacion: "LOCALIDAD, Municipio, ENTIDAD"
    ubicacion = [p.strip() for p in (row.get("Ubicacion") or "").split(",")]
    city = ubicacion[1].title() if len(ubicacion) >= 3 else None
    state = ubicacion[2].title() if len(ubicacion) >= 3 else None
    email = clean(row.get("Correo_e"))
    return RawCompany(
        name=name,
        legal_name=clean(row.get("Razon_social")),
        source="denue",
        source_ref=ref,
        source_url=FICHA_URL.format(id=ref),
        website_url=_website(row.get("Sitio_internet")),
        email=email.lower() if email else None,
        phone=clean(row.get("Telefono")),
        address=_address(row),
        city=city,
        state=state,
        scian_code=clean(row.get("CLASE_ACTIVIDAD_ID")),
        giro=clean(row.get("Clase_actividad")),
        size_stratum=clean(row.get("Estrato")),
        extra={
            k: row[k] for k in ("CLEE", "Latitud", "Longitud", "Fecha_Alta", "AreaGeo")
            if row.get(k)
        },
    )


def parse_response(data: Any) -> list[dict[str, Any]]:
    """DENUE responde una lista de registros, o una lista/cadena con un mensaje."""
    if isinstance(data, list) and all(isinstance(r, dict) for r in data):
        return data
    message = " ".join(str(x) for x in data) if isinstance(data, list) else str(data)
    if NO_RESULTS in message.lower():
        return []
    raise SourceError(f"DENUE respondió: {message[:200]}")


class DenueSource:
    name = "denue"

    def __init__(self, client: PoliteClient, token: str, page_size: int = 500) -> None:
        if not token:
            raise SourceError("falta DENUE_TOKEN en .env (se obtiene gratis en inegi.org.mx)")
        self.client = client
        self.token = token
        self.page_size = page_size

    def search(self, segment: SegmentConfig, limit: int) -> Iterator[RawCompany]:
        if segment.denue is None:
            raise SourceError(f"el segmento {segment.name} no tiene consulta denue")
        query = segment.denue
        delivered = 0
        for municipio, actividad, estrato in product(
            query.municipios, query.actividades, query.estratos
        ):
            start = 1
            while True:
                end = start + self.page_size - 1
                url = build_url(query, municipio, actividad, estrato, start, end, self.token)
                try:
                    rows = parse_response(self.client.get_json(url))
                except HttpError as exc:
                    raise SourceError(str(exc)) from exc
                log.info("denue_pagina", municipio=municipio, actividad=actividad,
                         estrato=estrato, desde=start, registros=len(rows))
                for row in rows:
                    company = parse_row(row)
                    if company is None:
                        continue
                    yield company
                    delivered += 1
                    if delivered >= limit:
                        return
                if len(rows) < self.page_size:
                    break
                start = end + 1
