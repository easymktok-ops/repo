from __future__ import annotations

import pytest

from outreach.db.models import Suppression
from outreach.sending.suppression import is_suppressed, suppress


def test_unknown_address_is_not_suppressed(session):
    assert is_suppressed(session, "info@ejemplo.mx") is False


def test_suppressed_email_is_blocked_case_insensitive(session):
    suppress(session, "Info@Ejemplo.mx", reason="baja", source="reply")
    assert is_suppressed(session, "info@ejemplo.mx") is True
    assert is_suppressed(session, "ventas@ejemplo.mx") is False


def test_suppressed_domain_blocks_every_address(session):
    suppress(session, "quejas@ejemplo.mx", reason="queja", whole_domain=True)
    assert is_suppressed(session, "ventas@ejemplo.mx") is True
    assert is_suppressed(session, "direccion@www.ejemplo.mx") is True


def test_suppress_is_idempotent(session):
    first = suppress(session, "info@ejemplo.mx", reason="baja")
    second = suppress(session, "INFO@ejemplo.mx", reason="rebote")
    assert first.id == second.id
    assert session.query(Suppression).count() == 1


def test_cannot_suppress_free_mail_domain(session):
    with pytest.raises(ValueError, match="gratuito"):
        suppress(session, "alguien@gmail.com", reason="queja", whole_domain=True)


def test_invalid_address_is_treated_as_suppressed(session):
    assert is_suppressed(session, "no-es-un-correo") is True
