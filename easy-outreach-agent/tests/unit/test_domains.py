from __future__ import annotations

import pytest

from outreach.domains import normalize_domain, normalize_email


@pytest.mark.parametrize(
    ("raw", "expected"),
    [
        ("https://www.Clinica-Ejemplo.mx/contacto?x=1", "clinica-ejemplo.mx"),
        ("http://clinica-ejemplo.mx", "clinica-ejemplo.mx"),
        ("www.clinica-ejemplo.com.mx", "clinica-ejemplo.com.mx"),
        ("clinica-ejemplo.mx.", "clinica-ejemplo.mx"),
        ("contacto@Clinica-Ejemplo.MX", "clinica-ejemplo.mx"),
        ("https://sub.clinica-ejemplo.mx:8080/", "sub.clinica-ejemplo.mx"),
        ("", None),
        (None, None),
        ("localhost", None),
    ],
)
def test_normalize_domain(raw, expected):
    assert normalize_domain(raw) == expected


def test_same_company_dedupes_to_one_domain():
    variants = ["https://www.ejemplo.mx/", "ejemplo.mx", "HTTP://EJEMPLO.MX/inicio"]
    assert {normalize_domain(v) for v in variants} == {"ejemplo.mx"}


@pytest.mark.parametrize(
    ("raw", "expected"),
    [
        ("  Info@WWW.Ejemplo.mx ", "info@ejemplo.mx"),
        ("info@ejemplo.mx", "info@ejemplo.mx"),
        ("sin-arroba.mx", None),
        ("@ejemplo.mx", None),
        (None, None),
    ],
)
def test_normalize_email(raw, expected):
    assert normalize_email(raw) == expected
