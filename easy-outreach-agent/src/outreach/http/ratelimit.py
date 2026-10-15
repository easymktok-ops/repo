"""Límite de peticiones por dominio (regla 3: máx 1 req/seg por dominio)."""

from __future__ import annotations

import threading
import time
from collections.abc import Callable


class DomainRateLimiter:
    """Espacia las peticiones a un mismo dominio. Seguro entre hilos.

    Las peticiones a dominios distintos no se esperan entre sí; las del mismo dominio
    se atienden una por una con al menos `min_interval` segundos de separación.
    """

    def __init__(
        self,
        max_per_second: float = 1.0,
        *,
        clock: Callable[[], float] = time.monotonic,
        sleep: Callable[[float], None] = time.sleep,
    ) -> None:
        if max_per_second <= 0:
            raise ValueError("max_per_second debe ser mayor que 0")
        self.min_interval = 1.0 / max_per_second
        self._clock = clock
        self._sleep = sleep
        self._guard = threading.Lock()
        self._locks: dict[str, threading.Lock] = {}
        self._last: dict[str, float] = {}

    def _lock_for(self, domain: str) -> threading.Lock:
        with self._guard:
            return self._locks.setdefault(domain, threading.Lock())

    def wait(self, domain: str, min_interval: float | None = None) -> None:
        """Bloquea hasta que se pueda hacer la siguiente petición a `domain`.

        `min_interval` alarga la espera (p. ej. Crawl-delay de robots.txt), nunca la acorta.
        """
        interval = max(self.min_interval, min_interval or 0.0)
        with self._lock_for(domain):
            last = self._last.get(domain)
            if last is not None:
                remaining = last + interval - self._clock()
                if remaining > 0:
                    self._sleep(remaining)
            self._last[domain] = self._clock()
