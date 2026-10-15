"""Modelos de la base de datos (ver PLAN.md, sección 2)."""

from __future__ import annotations

import datetime as dt
from datetime import datetime
from typing import Any

from sqlalchemy import (
    JSON,
    Boolean,
    CheckConstraint,
    Date,
    DateTime,
    Float,
    ForeignKey,
    Index,
    Integer,
    String,
    Text,
    UniqueConstraint,
)
from sqlalchemy.orm import Mapped, mapped_column, relationship, validates

from outreach.db.base import Base, TimestampMixin, utcnow

COMPANY_STATUSES = (
    "new", "enriched", "audited", "scored", "queued", "contacted", "manual", "discarded",
)
# Resultado de la visita al sitio en `enrich`. Solo "ok" sigue a auditoría.
SITE_STATUSES = (
    "ok", "unreachable", "http_error", "parked", "robots", "not_html", "no_site",
)
CONTACT_TYPES = ("email", "form", "whatsapp", "phone")
FINDING_CATEGORIES = ("seo", "ux", "microapp")
EVIDENCE_TYPES = ("api", "screenshot", "html")
DEMO_TYPES = ("cotizador", "agenda", "catalogo", "whatsapp")
MESSAGE_CHANNELS = ("email", "form")
MESSAGE_STATUSES = ("draft", "pending", "approved", "edited", "discarded", "sent")
SEND_STATUSES = ("scheduled", "sent", "bounced", "failed", "skipped_suppressed")
REPLY_CLASSES = ("interesado", "no_ahora", "baja", "rebote", "otro")
SUPPRESSION_KINDS = ("email", "domain")
SUPPRESSION_REASONS = ("baja", "rebote", "queja", "manual")


def _in(column: str, values: tuple[str, ...]) -> str:
    return f"{column} IN ({', '.join(repr(v) for v in values)})"


class Run(TimestampMixin, Base):
    __tablename__ = "runs"

    id: Mapped[int] = mapped_column(primary_key=True)
    job: Mapped[str] = mapped_column(String(40))
    params: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    started_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), default=utcnow)
    finished_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    status: Mapped[str] = mapped_column(String(20), default="running")
    stats: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    api_cost_usd: Mapped[float] = mapped_column(Float, default=0.0)


class Segment(TimestampMixin, Base):
    __tablename__ = "segments"

    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(80), unique=True)
    country: Mapped[str] = mapped_column(String(2))
    giro: Mapped[str] = mapped_column(String(200))
    city: Mapped[str] = mapped_column(String(120))
    auto_send: Mapped[bool] = mapped_column(Boolean, default=False)
    daily_cap: Mapped[int] = mapped_column(Integer, default=40)
    active: Mapped[bool] = mapped_column(Boolean, default=True)

    companies: Mapped[list[Company]] = relationship(back_populates="segment")


class Company(TimestampMixin, Base):
    __tablename__ = "companies"
    __table_args__ = (
        CheckConstraint(_in("status", COMPANY_STATUSES), name="status"),
        CheckConstraint("score IS NULL OR (score >= 0 AND score <= 100)", name="score_range"),
        CheckConstraint(
            f"site_status IS NULL OR {_in('site_status', SITE_STATUSES)}", name="site_status"
        ),
        Index("ix_companies_status_score", "status", "score"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(300))
    legal_name: Mapped[str | None] = mapped_column(String(300))
    domain: Mapped[str | None] = mapped_column(String(253), unique=True)
    website_url: Mapped[str | None] = mapped_column(String(500))
    country: Mapped[str] = mapped_column(String(2), default="MX")
    state: Mapped[str | None] = mapped_column(String(120))
    city: Mapped[str | None] = mapped_column(String(120))
    scian_code: Mapped[str | None] = mapped_column(String(10))
    giro: Mapped[str | None] = mapped_column(String(200))
    size_stratum: Mapped[str | None] = mapped_column(String(60))
    phone: Mapped[str | None] = mapped_column(String(40))
    address: Mapped[str | None] = mapped_column(String(500))
    source: Mapped[str] = mapped_column(String(20))
    source_ref: Mapped[str | None] = mapped_column(String(120))
    source_url: Mapped[str | None] = mapped_column(String(500))
    stack: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    language: Mapped[str | None] = mapped_column(String(10))
    has_whatsapp: Mapped[bool | None] = mapped_column(Boolean)
    status: Mapped[str] = mapped_column(String(20), default="new")
    site_status: Mapped[str | None] = mapped_column(String(20))
    enriched_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    score: Mapped[int | None] = mapped_column(Integer)
    segment_id: Mapped[int | None] = mapped_column(ForeignKey("segments.id"))
    run_id: Mapped[int | None] = mapped_column(ForeignKey("runs.id"))

    segment: Mapped[Segment | None] = relationship(back_populates="companies")
    contacts: Mapped[list[Contact]] = relationship(back_populates="company")
    audits: Mapped[list[Audit]] = relationship(back_populates="company")


class Contact(TimestampMixin, Base):
    __tablename__ = "contacts"
    __table_args__ = (
        CheckConstraint(_in("type", CONTACT_TYPES), name="type"),
        UniqueConstraint("company_id", "type", "value", name="uq_contacts_company_type_value"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    company_id: Mapped[int] = mapped_column(ForeignKey("companies.id"))
    type: Mapped[str] = mapped_column(String(20))
    value: Mapped[str] = mapped_column(String(500))
    # Regla 4: solo datos publicados por la empresa, siempre con la URL donde se encontraron.
    source_url: Mapped[str] = mapped_column(String(500))
    found_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), default=utcnow)
    is_valid: Mapped[bool | None] = mapped_column(Boolean)
    has_captcha: Mapped[bool | None] = mapped_column(Boolean)

    company: Mapped[Company] = relationship(back_populates="contacts")

    @validates("source_url")
    def _require_source(self, _key: str, value: str) -> str:
        if not value or not value.strip():
            raise ValueError("todo contacto necesita la URL fuente donde se publicó")
        return value


class Audit(TimestampMixin, Base):
    __tablename__ = "audits"

    id: Mapped[int] = mapped_column(primary_key=True)
    company_id: Mapped[int] = mapped_column(ForeignKey("companies.id"))
    run_id: Mapped[int | None] = mapped_column(ForeignKey("runs.id"))
    pagespeed: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    seo: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    ux: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    microapps: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    screenshot_mobile: Mapped[str | None] = mapped_column(String(500))
    screenshot_desktop: Mapped[str | None] = mapped_column(String(500))
    raw_html_path: Mapped[str | None] = mapped_column(String(500))
    model: Mapped[str | None] = mapped_column(String(80))
    prompt_version: Mapped[str | None] = mapped_column(String(40))
    cost_usd: Mapped[float] = mapped_column(Float, default=0.0)

    company: Mapped[Company] = relationship(back_populates="audits")
    findings: Mapped[list[Finding]] = relationship(back_populates="audit")


class Finding(TimestampMixin, Base):
    __tablename__ = "findings"
    __table_args__ = (
        CheckConstraint(_in("category", FINDING_CATEGORIES), name="category"),
        CheckConstraint(_in("evidence_type", EVIDENCE_TYPES), name="evidence_type"),
        CheckConstraint("severity BETWEEN 1 AND 5", name="severity_range"),
        CheckConstraint("priority BETWEEN 1 AND 3", name="priority_range"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    audit_id: Mapped[int] = mapped_column(ForeignKey("audits.id"))
    category: Mapped[str] = mapped_column(String(20))
    title: Mapped[str] = mapped_column(String(200))
    description: Mapped[str] = mapped_column(Text)
    severity: Mapped[int] = mapped_column(Integer)
    priority: Mapped[int] = mapped_column(Integer)
    is_quick_win: Mapped[bool] = mapped_column(Boolean, default=False)
    evidence_type: Mapped[str] = mapped_column(String(20))
    # Regla 8: sin evidencia no hay hallazgo.
    evidence: Mapped[dict[str, Any]] = mapped_column(JSON, nullable=False)
    reviewed_ok: Mapped[bool | None] = mapped_column(Boolean)

    audit: Mapped[Audit] = relationship(back_populates="findings")

    @validates("evidence")
    def _require_evidence(self, _key: str, value: dict[str, Any] | None) -> dict[str, Any]:
        if not value or not any(v not in (None, "", [], {}) for v in value.values()):
            raise ValueError("un hallazgo sin evidencia no se puede guardar")
        return value


class Sample(TimestampMixin, Base):
    __tablename__ = "samples"
    __table_args__ = (
        CheckConstraint(f"demo_type IS NULL OR {_in('demo_type', DEMO_TYPES)}", name="demo_type"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    company_id: Mapped[int] = mapped_column(ForeignKey("companies.id"))
    audit_id: Mapped[int] = mapped_column(ForeignKey("audits.id"))
    pdf_path: Mapped[str | None] = mapped_column(String(500))
    demo_type: Mapped[str | None] = mapped_column(String(20))
    demo_path: Mapped[str | None] = mapped_column(String(500))
    demo_token: Mapped[str | None] = mapped_column(String(64), unique=True)
    demo_screenshot: Mapped[str | None] = mapped_column(String(500))
    branding: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)


class Message(TimestampMixin, Base):
    __tablename__ = "messages"
    __table_args__ = (
        CheckConstraint(_in("channel", MESSAGE_CHANNELS), name="channel"),
        CheckConstraint(_in("status", MESSAGE_STATUSES), name="status"),
        Index("ix_messages_status", "status"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    company_id: Mapped[int] = mapped_column(ForeignKey("companies.id"))
    sample_id: Mapped[int | None] = mapped_column(ForeignKey("samples.id"))
    channel: Mapped[str] = mapped_column(String(10))
    step: Mapped[int] = mapped_column(Integer, default=0)
    subject: Mapped[str | None] = mapped_column(String(200))
    body: Mapped[str] = mapped_column(Text)
    body_original: Mapped[str | None] = mapped_column(Text)
    prompt_version: Mapped[str | None] = mapped_column(String(40))
    validation: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
    # Regla 5: todo mensaje nace pendiente de aprobación humana.
    status: Mapped[str] = mapped_column(String(20), default="pending")
    approved_by: Mapped[str | None] = mapped_column(String(200))
    approved_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    edit_diff: Mapped[str | None] = mapped_column(Text)


class Send(TimestampMixin, Base):
    __tablename__ = "sends"
    __table_args__ = (
        CheckConstraint(_in("status", SEND_STATUSES), name="status"),
        Index("ix_sends_mailbox_sent_at", "mailbox", "sent_at"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    message_id: Mapped[int] = mapped_column(ForeignKey("messages.id"))
    contact_id: Mapped[int] = mapped_column(ForeignKey("contacts.id"))
    mailbox: Mapped[str] = mapped_column(String(200))
    scheduled_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    sent_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    dry_run: Mapped[bool] = mapped_column(Boolean, default=True)
    provider_msg_id: Mapped[str | None] = mapped_column(String(300))
    status: Mapped[str] = mapped_column(String(30), default="scheduled")
    error: Mapped[str | None] = mapped_column(Text)
    form_screenshot: Mapped[str | None] = mapped_column(String(500))


class Reply(TimestampMixin, Base):
    __tablename__ = "replies"
    __table_args__ = (
        CheckConstraint(
            f"classification IS NULL OR {_in('classification', REPLY_CLASSES)}",
            name="classification",
        ),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    send_id: Mapped[int | None] = mapped_column(ForeignKey("sends.id"))
    company_id: Mapped[int | None] = mapped_column(ForeignKey("companies.id"))
    received_at: Mapped[datetime] = mapped_column(DateTime(timezone=True))
    from_addr: Mapped[str] = mapped_column(String(320))
    subject: Mapped[str | None] = mapped_column(String(300))
    body: Mapped[str | None] = mapped_column(Text)
    classification: Mapped[str | None] = mapped_column(String(20))
    confidence: Mapped[float | None] = mapped_column(Float)
    notified_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))


class Suppression(TimestampMixin, Base):
    __tablename__ = "suppression"
    __table_args__ = (
        CheckConstraint(_in("kind", SUPPRESSION_KINDS), name="kind"),
        CheckConstraint(_in("reason", SUPPRESSION_REASONS), name="reason"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    value: Mapped[str] = mapped_column(String(320), unique=True, index=True)
    kind: Mapped[str] = mapped_column(String(10))
    reason: Mapped[str] = mapped_column(String(20))
    source: Mapped[str | None] = mapped_column(String(200))


class MailboxStat(TimestampMixin, Base):
    __tablename__ = "mailbox_stats"
    __table_args__ = (UniqueConstraint("mailbox", "date", name="uq_mailbox_stats_mailbox_date"),)

    id: Mapped[int] = mapped_column(primary_key=True)
    mailbox: Mapped[str] = mapped_column(String(200))
    date: Mapped[dt.date] = mapped_column(Date)
    sent: Mapped[int] = mapped_column(Integer, default=0)
    bounced: Mapped[int] = mapped_column(Integer, default=0)
    complaints: Mapped[int] = mapped_column(Integer, default=0)
    paused: Mapped[bool] = mapped_column(Boolean, default=False)
    pause_reason: Mapped[str | None] = mapped_column(String(200))
