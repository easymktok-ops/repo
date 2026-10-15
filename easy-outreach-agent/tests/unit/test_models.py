from __future__ import annotations

import pytest
from sqlalchemy import inspect
from sqlalchemy.exc import IntegrityError

from outreach.db.models import Audit, Company, Contact, Finding, Message

EXPECTED_TABLES = {
    "runs", "segments", "companies", "contacts", "audits", "findings", "samples",
    "messages", "sends", "replies", "suppression", "mailbox_stats",
}


def _company(session, domain="ejemplo.mx") -> Company:
    c = Company(name="Ejemplo SA", domain=domain, source="denue")
    session.add(c)
    session.flush()
    return c


def test_migration_creates_all_tables(session):
    tables = set(inspect(session.get_bind()).get_table_names())
    assert EXPECTED_TABLES <= tables


def test_company_domain_is_unique(session):
    _company(session)
    with pytest.raises(IntegrityError):
        _company(session)


def test_company_status_is_constrained(session):
    c = _company(session)
    c.status = "inventado"
    with pytest.raises(IntegrityError):
        session.flush()


def test_contact_requires_source_url(session):
    c = _company(session)
    with pytest.raises(ValueError, match="URL fuente"):
        Contact(company_id=c.id, type="email", value="info@ejemplo.mx", source_url="")


def test_finding_requires_evidence(session):
    c = _company(session)
    audit = Audit(company_id=c.id)
    session.add(audit)
    session.flush()
    base = dict(
        audit_id=audit.id, category="seo", title="Sin meta description",
        description="La página de inicio no tiene meta description.",
        severity=3, priority=1, evidence_type="html",
    )
    with pytest.raises(ValueError, match="evidencia"):
        Finding(**base, evidence={})
    with pytest.raises(ValueError, match="evidencia"):
        Finding(**base, evidence={"snippet": ""})

    ok = Finding(**base, evidence={"url": "https://ejemplo.mx/", "snippet": "<head>...</head>"})
    session.add(ok)
    session.flush()
    assert ok.id is not None


def test_message_starts_pending_approval(session):
    c = _company(session)
    m = Message(company_id=c.id, channel="email", body="Hola")
    session.add(m)
    session.flush()
    assert m.status == "pending"


def test_foreign_keys_enforced(session):
    session.add(Contact(company_id=999, type="email", value="x@y.mx", source_url="https://y.mx"))
    with pytest.raises(IntegrityError):
        session.flush()


def test_models_and_migrations_are_in_sync(session):
    from alembic.autogenerate import compare_metadata
    from alembic.migration import MigrationContext

    from outreach.db.base import Base

    ctx = MigrationContext.configure(session.connection())
    assert compare_metadata(ctx, Base.metadata) == []


def test_migration_downgrade_to_base(db_url):
    from alembic import command
    from alembic.config import Config

    from outreach.config import PROJECT_ROOT

    cfg = Config(str(PROJECT_ROOT / "alembic.ini"))
    cfg.set_main_option("script_location", str(PROJECT_ROOT / "migrations"))
    cfg.attributes["database_url"] = db_url
    cfg.attributes["configure_logger"] = False
    command.upgrade(cfg, "head")
    command.downgrade(cfg, "base")
