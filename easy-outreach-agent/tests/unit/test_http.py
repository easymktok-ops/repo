from __future__ import annotations

import httpx
import pytest

from outreach.http.client import HttpError, PoliteClient
from outreach.http.ratelimit import DomainRateLimiter
from outreach.http.robots import RobotsCache

ROBOTS = """
User-agent: *
Disallow: /privado/

User-agent: EasyOutreachBot
Disallow: /admin/
Crawl-delay: 3
"""


class FakeClock:
    def __init__(self) -> None:
        self.now = 100.0
        self.sleeps: list[float] = []

    def __call__(self) -> float:
        return self.now

    def sleep(self, seconds: float) -> None:
        self.sleeps.append(seconds)
        self.now += seconds


# -- límite por dominio ---------------------------------------------------------------------

def test_rate_limit_spaces_same_domain():
    clock = FakeClock()
    limiter = DomainRateLimiter(1.0, clock=clock, sleep=clock.sleep)
    limiter.wait("a.mx")
    clock.now += 0.25
    limiter.wait("a.mx")
    assert clock.sleeps == [pytest.approx(0.75)]


def test_rate_limit_does_not_block_other_domains():
    clock = FakeClock()
    limiter = DomainRateLimiter(1.0, clock=clock, sleep=clock.sleep)
    limiter.wait("a.mx")
    limiter.wait("b.mx")
    assert clock.sleeps == []


def test_rate_limit_honors_longer_interval_only():
    clock = FakeClock()
    limiter = DomainRateLimiter(1.0, clock=clock, sleep=clock.sleep)
    limiter.wait("a.mx")
    limiter.wait("a.mx", min_interval=3)
    limiter.wait("a.mx", min_interval=0.1)  # no puede bajar de 1 seg
    assert clock.sleeps == [pytest.approx(3), pytest.approx(1)]


def test_rate_limit_rejects_zero():
    with pytest.raises(ValueError):
        DomainRateLimiter(0)


# -- robots.txt -----------------------------------------------------------------------------

def _robots(status, text="", calls=None):
    def fetch(url):
        if calls is not None:
            calls.append(url)
        if isinstance(status, Exception):
            raise status
        return status, text
    return fetch


def test_robots_rules_for_our_agent():
    cache = RobotsCache(_robots(200, ROBOTS), "EasyOutreachBot/1.0 (+https://easymarketing.mx)")
    assert cache.allowed("https://a.mx/contacto/")
    assert not cache.allowed("https://a.mx/admin/panel")
    assert cache.crawl_delay("https://a.mx/") == 3


def test_robots_4xx_allows_all_and_5xx_or_error_blocks_all():
    assert RobotsCache(_robots(404), "Bot").allowed("https://a.mx/x")
    assert not RobotsCache(_robots(503), "Bot").allowed("https://a.mx/x")
    assert not RobotsCache(_robots(httpx.ConnectError("x")), "Bot").allowed("https://a.mx/x")


def test_robots_is_cached_per_origin():
    calls: list[str] = []
    cache = RobotsCache(_robots(200, ROBOTS, calls), "Bot")
    cache.allowed("https://a.mx/1")
    cache.allowed("https://a.mx/2")
    cache.allowed("https://b.mx/1")
    assert calls == ["https://a.mx/robots.txt", "https://b.mx/robots.txt"]


# -- cliente --------------------------------------------------------------------------------

def test_fetch_page_sends_identified_user_agent(web, client):
    web.add("https://a.mx/", "<html><body>hola</body></html>")
    page = client.fetch_page("https://a.mx/")
    assert page.ok and "hola" in page.text
    assert all(r.headers["user-agent"].startswith("EasyOutreachBot/1.0") for r in web.requests)
    assert web.urls() == ["https://a.mx/robots.txt", "https://a.mx/"]


def test_fetch_page_respects_robots(web, client):
    web.add("https://a.mx/robots.txt", ROBOTS, content_type="text/plain")
    web.add("https://a.mx/admin/", "secreto")
    page = client.fetch_page("https://a.mx/admin/")
    assert page.error == "robots"
    assert "https://a.mx/admin/" not in web.urls()


def test_fetch_page_checks_robots_on_each_redirect(web, client):
    web.redirect("https://a.mx/", "https://b.mx/admin/")
    web.add("https://b.mx/robots.txt", ROBOTS, content_type="text/plain")
    page = client.fetch_page("https://a.mx/")
    assert page.error == "robots"
    assert page.final_url == "https://b.mx/admin/"


def test_fetch_page_follows_redirects(web, client):
    web.redirect("http://a.mx/", "https://www.a.mx/")
    web.add("https://www.a.mx/", "<p>ok</p>")
    page = client.fetch_page("http://a.mx/")
    assert page.ok and page.final_url == "https://www.a.mx/"


def test_fetch_page_retries_server_errors(web, client):
    attempts = []

    def flaky(request):
        attempts.append(1)
        return httpx.Response(503) if len(attempts) < 3 else httpx.Response(200, html="ya")

    web.routes["https://a.mx/"] = flaky
    assert client.fetch_page("https://a.mx/").text == "ya"
    assert len(attempts) == 3


def test_fetch_page_network_error_and_non_html(web, client):
    web.add("https://caido.mx/", httpx.ConnectError("sin red"))
    web.add("https://a.mx/doc.pdf", "%PDF", content_type="application/pdf")
    assert client.fetch_page("https://caido.mx/").error == "network"
    assert client.fetch_page("https://a.mx/doc.pdf").error == "not_html"


def test_fetch_page_caps_body_size(web):
    web.add("https://a.mx/", "x" * 10_000)
    with PoliteClient(user_agent="Bot", transport=httpx.MockTransport(web.handler),
                      sleep=lambda _s: None, max_bytes=1_000) as small:
        assert len(small.fetch_page("https://a.mx/").text) == 1_000


def test_fetch_page_decodes_meta_charset(web, client):
    body = '<meta charset="iso-8859-1"><p>Clínica</p>'.encode("latin-1")
    web.routes["https://a.mx/"] = lambda r: httpx.Response(
        200, headers={"content-type": "text/html"}, content=body
    )
    assert "Clínica" in client.fetch_page("https://a.mx/").text


def test_api_error_does_not_leak_token(web, client):
    web.add("https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/x/SECRETO", status=500)
    with pytest.raises(HttpError) as exc:
        client.get_json("https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/x/SECRETO")
    assert "SECRETO" not in str(exc.value)
