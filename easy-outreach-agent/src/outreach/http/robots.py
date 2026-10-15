"""Cache de robots.txt por dominio (regla 3).

Criterio (RFC 9309): 2xx se respeta tal cual; 4xx significa que no hay reglas (todo permitido);
5xx o error de red significa que el sitio no está disponible y no se rastrea nada.
"""

from __future__ import annotations

import threading
from collections.abc import Callable
from urllib.parse import urlsplit
from urllib.robotparser import RobotFileParser

# fetch(url) -> (status, texto). Un status None o una excepción equivalen a error de red.
RobotsFetcher = Callable[[str], tuple[int | None, str]]


class _Rules:
    def __init__(self, parser: RobotFileParser | None, allow_all: bool) -> None:
        self.parser = parser
        self.allow_all = allow_all


class RobotsCache:
    def __init__(self, fetch: RobotsFetcher, user_agent: str) -> None:
        self._fetch = fetch
        self.user_agent = user_agent
        self._lock = threading.Lock()
        self._cache: dict[str, _Rules] = {}

    @staticmethod
    def _origin(url: str) -> str:
        parts = urlsplit(url)
        return f"{parts.scheme}://{parts.netloc}".lower()

    def _rules(self, url: str) -> _Rules:
        origin = self._origin(url)
        with self._lock:
            cached = self._cache.get(origin)
        if cached is not None:
            return cached

        try:
            status, text = self._fetch(origin + "/robots.txt")
        except Exception:
            status, text = None, ""

        if status is not None and 200 <= status < 300:
            parser = RobotFileParser()
            parser.parse(text.splitlines())
            rules = _Rules(parser, allow_all=False)
        elif status is not None and 400 <= status < 500:
            rules = _Rules(None, allow_all=True)
        else:
            rules = _Rules(None, allow_all=False)

        with self._lock:
            self._cache.setdefault(origin, rules)
        return rules

    def allowed(self, url: str) -> bool:
        rules = self._rules(url)
        if rules.parser is None:
            return rules.allow_all
        return rules.parser.can_fetch(self.user_agent, url)

    def crawl_delay(self, url: str) -> float | None:
        rules = self._rules(url)
        if rules.parser is None:
            return None
        delay = rules.parser.crawl_delay(self.user_agent)
        return float(delay) if delay is not None else None
