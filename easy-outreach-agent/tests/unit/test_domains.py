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


def test_domain_without_tld_is_invalid():
    from outreach.domains import normalize_domain

    assert normalize_domain("https://www.dentnuvo") is None
    assert normalize_domain("www.dentnuvo.com") == "dentnuvo.com"


def test_looks_like_free_mail():
    from outreach.domains import looks_like_free_mail

    for d in ("gmail.com", "gmail.mx", "yahoo.co.uk", "hotlmail.com", "outlook.es", "live.com.mx"):
        assert looks_like_free_mail(d), d
    for d in ("sonrisaqro.mx", "mail.sonrisaqro.mx", "livecare.mx", "clinicayahualica.mx"):
        assert not looks_like_free_mail(d), d
