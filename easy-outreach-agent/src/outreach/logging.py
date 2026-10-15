"""Logs estructurados en JSON (o legibles en consola con pretty=True)."""

from __future__ import annotations

import logging
import sys

import structlog


def configure_logging(level: str = "INFO", pretty: bool = False) -> None:
    logging.basicConfig(stream=sys.stderr, level=level, format="%(message)s")
    renderer = structlog.dev.ConsoleRenderer() if pretty else structlog.processors.JSONRenderer()
    structlog.configure(
        processors=[
            structlog.contextvars.merge_contextvars,
            structlog.processors.add_log_level,
            structlog.processors.TimeStamper(fmt="iso", utc=True),
            renderer,
        ],
        wrapper_class=structlog.make_filtering_bound_logger(logging.getLevelName(level)),
        # sys.stderr se resuelve en cada uso (no al configurar): sobrevive a redirecciones.
        logger_factory=lambda *_args: structlog.PrintLogger(sys.stderr),
        cache_logger_on_first_use=False,
    )


def get_logger(name: str | None = None) -> structlog.stdlib.BoundLogger:
    return structlog.get_logger(name)
