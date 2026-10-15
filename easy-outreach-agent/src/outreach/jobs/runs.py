"""Registro de corridas en la tabla runs (trazabilidad y costo por corrida)."""

from __future__ import annotations

from collections.abc import Iterator
from contextlib import contextmanager
from typing import Any

from sqlalchemy.orm import Session

from outreach.db.base import utcnow
from outreach.db.models import Run


@contextmanager
def track_run(session: Session, job: str, params: dict[str, Any]) -> Iterator[Run]:
    """Abre una corrida; al salir guarda status, hora de fin y lo que se puso en run.stats.

    Si la corrida falla, se conserva lo ya confirmado (commit) y el error queda en stats.
    """
    run = Run(job=job, params=params, status="running", stats={})
    session.add(run)
    session.commit()
    try:
        yield run
    except BaseException as exc:
        session.rollback()
        run.status = "failed"
        run.stats = {**run.stats, "error": f"{type(exc).__name__}: {exc}"[:500]}
        raise
    else:
        run.status = "finished"
    finally:
        run.finished_at = utcnow()
        session.commit()
