from __future__ import annotations

import socket

from outreach.http import dns


def test_domain_resolves(monkeypatch):
    table = {"vive.mx": True, "www.vive.mx": False, "www.solowww.mx": True}

    def fake(host, *_a, **_k):
        if table.get(host):
            return [("ok",)]
        if host == "temporal.mx" or host == "www.temporal.mx":
            raise socket.gaierror(socket.EAI_AGAIN, "temporal")
        raise socket.gaierror(socket.EAI_NONAME, "no existe")

    monkeypatch.setattr(socket, "getaddrinfo", fake)
    assert dns.domain_resolves("vive.mx")
    assert dns.domain_resolves("solowww.mx")
    assert not dns.domain_resolves("murio.mx")
    assert dns.domain_resolves("temporal.mx")  # duda: no se descarta
