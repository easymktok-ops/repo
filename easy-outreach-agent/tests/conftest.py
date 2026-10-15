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


FIXTURES = PROJECT_ROOT / "tests" / "fixtures"
UA = "EasyOutreachBot/1.0 (+https://easymarketing.mx)"


def _key(url: str) -> str:
    """"https://a.mx" y "https://a.mx/" son la misma página."""
    scheme, _, rest = url.partition("://")
    return url + "/" if "/" not in rest else url


class _Routes(dict):
    def __setitem__(self, url, value):
        super().__setitem__(_key(url), value)

    def __getitem__(self, url):
        return super().__getitem__(_key(url))

    def get(self, url, default=None):
        return super().get(_key(url), default)


class FakeWeb:
    """Internet de mentira para httpx.MockTransport: URL -> respuesta. Registra cada petición.

    Las URL sin ruta registrada responden 404 (incluido robots.txt, que entonces permite todo).
    """

    def __init__(self) -> None:
        self.routes: dict[str, object] = _Routes()
        self.requests: list = []

    def add(self, url: str, body: str = "", status: int = 200,
            content_type: str = "text/html; charset=utf-8", headers: dict | None = None) -> None:
        self.routes[url] = (status, {"content-type": content_type, **(headers or {})}, body)

    def page(self, url: str, fixture: str) -> None:
        self.add(url, (FIXTURES / "sites" / fixture).read_text(encoding="utf-8"))

    def redirect(self, url: str, location: str, status: int = 301) -> None:
        self.add(url, status=status, headers={"location": location})

    def handler(self, request):
        import httpx

        self.requests.append(request)
        route = self.routes.get(str(request.url))
        if route is None:
            return httpx.Response(404, text="no encontrado")
        if callable(route):
            return route(request)
        status, headers, body = route
        if isinstance(body, Exception):
            raise body
        return httpx.Response(status, headers=headers, content=body.encode("utf-8")
                              if isinstance(body, str) else body)

    def urls(self) -> list[str]:
        return [str(r.url) for r in self.requests]


@pytest.fixture
def web() -> FakeWeb:
    return FakeWeb()


@pytest.fixture
def client(web):
    import httpx

    from outreach.http.client import PoliteClient

    c = PoliteClient(user_agent=UA, transport=httpx.MockTransport(web.handler),
                     sleep=lambda _s: None, retries=2)
    yield c
    c.close()
