"""Cliente HTTP cortés: User-Agent identificado, robots.txt, 1 req/seg por dominio, reintentos.

`fetch_page` es para sitios de empresas (respeta robots.txt en cada salto de redirección).
`get_json`/`post_json` son para APIs oficiales (DENUE, Places), donde robots.txt no aplica
pero sí el límite por dominio y los reintentos.
"""

from __future__ import annotations

import json
import re
import time
from collections.abc import Callable
from dataclasses import dataclass, field
from typing import Any
from urllib.parse import urljoin, urlsplit

import httpx

from outreach.http.ratelimit import DomainRateLimiter
from outreach.http.robots import RobotsCache

RETRY_STATUSES = frozenset({429, 500, 502, 503, 504})
REDIRECT_STATUSES = frozenset({301, 302, 303, 307, 308})
HTML_TYPES = ("text/html", "application/xhtml+xml")
_META_CHARSET = re.compile(rb"""<meta[^>]+charset=["']?([\w-]+)""", re.I)


class HttpError(Exception):
    """Error al llamar una API (status inesperado o respuesta inválida)."""


@dataclass
class Page:
    url: str
    final_url: str
    status: int | None = None
    content_type: str = ""
    text: str = ""
    headers: dict[str, str] = field(default_factory=dict)
    # robots / network / timeout / too_many_redirects / not_html / redirect_stopped
    error: str | None = None

    @property
    def ok(self) -> bool:
        return self.error is None and self.status is not None and 200 <= self.status < 300


@dataclass
class _Raw:
    url: str
    status: int
    headers: httpx.Headers
    content: bytes

    def text(self) -> str:
        return _decode(self.content, self.headers.get("content-type", ""))


def _decode(content: bytes, content_type: str) -> str:
    match = re.search(r"charset=([\w-]+)", content_type, re.I)
    charset = match.group(1) if match else None
    if not charset:
        meta = _META_CHARSET.search(content[:4096])
        charset = meta.group(1).decode("ascii", "ignore") if meta else "utf-8"
    try:
        return content.decode(charset, errors="replace")
    except LookupError:
        return content.decode("utf-8", errors="replace")


class PoliteClient:
    def __init__(
        self,
        *,
        user_agent: str,
        timeout: float = 15.0,
        max_per_second: float = 1.0,
        retries: int = 2,
        max_bytes: int = 2_000_000,
        transport: httpx.BaseTransport | None = None,
        limiter: DomainRateLimiter | None = None,
        sleep: Callable[[float], None] = time.sleep,
    ) -> None:
        self.user_agent = user_agent
        self.retries = retries
        self.max_bytes = max_bytes
        self._sleep = sleep
        self._http = httpx.Client(
            headers={"User-Agent": user_agent, "Accept-Language": "es-MX,es;q=0.9,en;q=0.5"},
            timeout=timeout,
            follow_redirects=False,
            transport=transport,
        )
        self.limiter = limiter or DomainRateLimiter(max_per_second, sleep=sleep)
        self.robots = RobotsCache(self._fetch_robots, user_agent)

    def close(self) -> None:
        self._http.close()

    def __enter__(self) -> PoliteClient:
        return self

    def __exit__(self, *_exc: object) -> None:
        self.close()

    # -- envío con límite y reintentos ------------------------------------------------------

    def _send(
        self,
        method: str,
        url: str,
        *,
        max_bytes: int,
        min_interval: float | None = None,
        **kwargs: Any,
    ) -> _Raw:
        host = urlsplit(url).hostname or ""
        attempt = 0
        while True:
            self.limiter.wait(host, min_interval)
            try:
                with self._http.stream(method, url, **kwargs) as resp:
                    content = bytearray()
                    for chunk in resp.iter_bytes():
                        content.extend(chunk)
                        if len(content) >= max_bytes:
                            break
                    raw = _Raw(
                        str(resp.url), resp.status_code, resp.headers, bytes(content[:max_bytes])
                    )
            except httpx.TransportError:
                if attempt >= self.retries:
                    raise
            else:
                if raw.status not in RETRY_STATUSES or attempt >= self.retries:
                    return raw
            attempt += 1
            self._sleep(2.0**attempt)

    def _fetch_robots(self, url: str) -> tuple[int | None, str]:
        raw = self._send("GET", url, max_bytes=500_000)
        return raw.status, raw.text()

    # -- sitios de empresas -----------------------------------------------------------------

    def fetch_page(
        self,
        url: str,
        *,
        max_redirects: int = 5,
        follow: Callable[[str], bool] | None = None,
    ) -> Page:
        """Descarga una página HTML respetando robots.txt en cada salto.

        Si `follow(destino)` es falso, no se sigue esa redirección: se devuelve la página con
        error "redirect_stopped" y el destino en final_url (p. ej. un dominio que lleva a Facebook).
        """
        current = url
        for _ in range(max_redirects + 1):
            if not self.robots.allowed(current):
                return Page(url=url, final_url=current, error="robots")
            try:
                raw = self._send(
                    "GET", current, max_bytes=self.max_bytes,
                    min_interval=self.robots.crawl_delay(current),
                )
            except httpx.TimeoutException:
                return Page(url=url, final_url=current, error="timeout")
            except httpx.HTTPError:
                return Page(url=url, final_url=current, error="network")

            if raw.status in REDIRECT_STATUSES and "location" in raw.headers:
                target = urljoin(current, raw.headers["location"])
                if follow is not None and not follow(target):
                    return Page(url=url, final_url=target, status=raw.status,
                                error="redirect_stopped")
                current = target
                continue

            content_type = raw.headers.get("content-type", "").split(";")[0].strip().lower()
            page = Page(
                url=url, final_url=current, status=raw.status, content_type=content_type,
                headers=dict(raw.headers),
            )
            if content_type and content_type not in HTML_TYPES:
                page.error = "not_html"
                return page
            page.text = raw.text()
            return page
        return Page(url=url, final_url=current, error="too_many_redirects")

    # -- APIs oficiales ---------------------------------------------------------------------

    def _json(self, raw: _Raw) -> Any:
        if not 200 <= raw.status < 300:
            raise HttpError(f"HTTP {raw.status} en {_redact(raw.url)}")
        try:
            return json.loads(raw.text())
        except json.JSONDecodeError as exc:
            raise HttpError(f"respuesta no es JSON en {_redact(raw.url)}") from exc

    def get_json(self, url: str, **kwargs: Any) -> Any:
        return self._json(self._send("GET", url, max_bytes=50_000_000, **kwargs))

    def post_json(self, url: str, payload: dict[str, Any], **kwargs: Any) -> Any:
        return self._json(self._send("POST", url, max_bytes=50_000_000, json=payload, **kwargs))


def _redact(url: str) -> str:
    """Quita el último segmento de la ruta (el token de DENUE va ahí) para no filtrarlo en logs."""
    parts = urlsplit(url)
    path = parts.path.rsplit("/", 1)[0] + "/***" if "/consulta/" in parts.path else parts.path
    return f"{parts.scheme}://{parts.netloc}{path}"
