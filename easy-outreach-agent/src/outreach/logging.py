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
        logger_factory=structlog.PrintLoggerFactory(file=sys.stderr),
        cache_logger_on_first_use=True,
    )


def get_logger(name: str | None = None) -> structlog.stdlib.BoundLogger:
    return structlog.get_logger(name)
