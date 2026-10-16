"""Comprobación barata de que un dominio existe antes de guardarlo como prospecto.

El DENUE trae muchos sitios que ya no existen (dominios vencidos). Resolverlos en DNS evita
guardarlos y visitarlos. Solo "el nombre no existe" cuenta como inexistente; un fallo temporal
del resolvedor no descarta a nadie.
"""

from __future__ import annotations

import socket

NOT_FOUND = {socket.EAI_NONAME, getattr(socket, "EAI_NODATA", socket.EAI_NONAME)}


def _resolves(host: str) -> bool | None:
    try:
        socket.getaddrinfo(host, 443, proto=socket.IPPROTO_TCP)
        return True
    except socket.gaierror as exc:
        return False if exc.errno in NOT_FOUND else None
    except OSError:
        return None


def domain_resolves(domain: str) -> bool:
    """True si el dominio o www.dominio tienen dirección (o si no se pudo saber)."""
    results = [_resolves(domain), _resolves("www." + domain)]
    if any(r is True for r in results):
        return True
    return not all(r is False for r in results)
