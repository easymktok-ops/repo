from __future__ import annotations

import pytest
from alembic import command
from alembic.config import Config

from outreach.config import PROJECT_ROOT
from outreach.db.base import make_engine, make_session_factory


@pytest.fixture
def db_url(tmp_path) -> str:
    return f"sqlite:///{tmp_path / 'test.db'}"


@pytest.fixture
def session(db_url):
    """Sesión sobre una base creada con las migraciones reales (no con create_all)."""
    cfg = Config(str(PROJECT_ROOT / "alembic.ini"))
    cfg.set_main_option("script_location", str(PROJECT_ROOT / "migrations"))
    cfg.attributes["database_url"] = db_url
    cfg.attributes["configure_logger"] = False
    command.upgrade(cfg, "head")

    engine = make_engine(db_url)
    factory = make_session_factory(engine)
    with factory() as s:
        yield s
    engine.dispose()
