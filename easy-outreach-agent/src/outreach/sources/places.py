"""Fuente opcional: Google Places API (New), Text Search. Apagada por defecto.

Solo se usa la API oficial, nunca scraping de Google Maps (regla 2). Se pide únicamente
lo necesario para encontrar el sitio de la empresa (field mask mínimo).

Ojo con los términos de Google Maps Platform: el place_id se puede guardar sin límite,
pero el resto del contenido de Places no se debe almacenar de forma permanente. Por eso,
después de enriquecer, los datos que importan salen del sitio de la propia empresa.
"""

from __future__ import annotations

from collections.abc import Iterator
from typing import Any

from outreach.config import SegmentConfig
from outreach.http.client import HttpError, PoliteClient
from outreach.logging import get_logger
from outreach.sources.base import RawCompany, SourceError, clean

SEARCH_URL = "https://places.googleapis.com/v1/places:searchText"
FIELD_MASK = ",".join([
    "places.id", "places.displayName", "places.formattedAddress", "places.websiteUri",
    "places.nationalPhoneNumber", "places.googleMapsUri", "places.primaryTypeDisplayName",
    "nextPageToken",
])
MAX_PAGES = 3  # Text Search entrega como máximo 60 resultados (3 páginas de 20)

log = get_logger("sources.places")


def parse_place(place: dict[str, Any], city: str) -> RawCompany | None:
    ref = clean(place.get("id"))
    name = clean((place.get("displayName") or {}).get("text"))
    if not ref or not name:
        return None
    return RawCompany(
        name=name,
        source="places",
        source_ref=ref,
        source_url=place.get("googleMapsUri") or f"https://www.google.com/maps/place/?q=place_id:{ref}",
        website_url=clean(place.get("websiteUri")),
        phone=clean(place.get("nationalPhoneNumber")),
        address=clean(place.get("formattedAddress")),
        city=city,
        giro=clean((place.get("primaryTypeDisplayName") or {}).get("text")),
    )


class PlacesSource:
    name = "places"

    def __init__(self, client: PoliteClient, api_key: str) -> None:
        if not api_key:
            raise SourceError("falta GOOGLE_PLACES_KEY en .env")
        self.client = client
        self.api_key = api_key

    def search(self, segment: SegmentConfig, limit: int) -> Iterator[RawCompany]:
        if segment.places is None:
            raise SourceError(f"el segmento {segment.name} no tiene consultas places")
        headers = {"X-Goog-Api-Key": self.api_key, "X-Goog-FieldMask": FIELD_MASK}
        delivered = 0
        for text in segment.places.queries:
            token = None
            for _ in range(MAX_PAGES):
                payload: dict[str, Any] = {
                    "textQuery": text, "languageCode": "es", "regionCode": segment.country,
                    "pageSize": 20,
                }
                if token:
                    payload["pageToken"] = token
                try:
                    data = self.client.post_json(SEARCH_URL, payload, headers=headers)
                except HttpError as exc:
                    raise SourceError(str(exc)) from exc
                places = data.get("places", [])
                log.info("places_pagina", consulta=text, resultados=len(places))
                for place in places:
                    company = parse_place(place, segment.city)
                    if company is None:
                        continue
                    yield company
                    delivered += 1
                    if delivered >= limit:
                        return
                token = data.get("nextPageToken")
                if not token:
                    break
