from __future__ import annotations

import shutil

import pytest
import yaml
from pydantic import ValidationError

from outreach.config import (
    CONFIG_DIR,
    Secrets,
    load_country,
    load_scoring,
    load_segments,
    load_settings,
)


def test_settings_load_with_pilot_decisions():
    s = load_settings()
    assert s.company.landing_url == "https://easymarketing.mx/demand/"
    assert s.sending.from_address == "norman@hola.easymarketing.mx"
    assert s.sending.reply_to == "norman@hola.easymarketing.mx"
    assert s.people.approver == "info@easymarketing.mx"
    assert s.sending.max_per_day_per_mailbox <= 40
    assert s.pilot.cities == ["Querétaro", "CDMX", "Monterrey"]
    assert s.sources.places.enabled is False


def test_scoring_weights_sum_100():
    w = load_scoring()
    assert w.size + w.severity + w.contactability + w.fit == 100


def test_segments_default_to_manual_approval():
    segments = load_segments()
    assert segments
    assert all(seg.auto_send is False for seg in segments)


def test_country_mx_has_legal_footer():
    mx = load_country("MX")
    assert mx.timezone == "America/Mexico_City"
    assert "{unsubscribe_url}" in mx.legal["footer"]
    assert "{source_url}" in mx.legal["footer"]


def test_dry_run_is_true_by_default(monkeypatch, tmp_path):
    monkeypatch.delenv("DRY_RUN", raising=False)
    assert Secrets(_env_file=tmp_path / "missing.env").dry_run is True


def _write_settings(tmp_path, mutate):
    target = tmp_path / "config"
    shutil.copytree(CONFIG_DIR, target)
    path = target / "settings.yaml"
    data = yaml.safe_load(path.read_text(encoding="utf-8"))
    mutate(data)
    path.write_text(yaml.safe_dump(data, allow_unicode=True), encoding="utf-8")
    return target


def test_rejects_sending_from_root_domain(tmp_path):
    cfg = _write_settings(
        tmp_path, lambda d: d["sending"].update(from_address="norman@easymarketing.mx")
    )
    with pytest.raises(ValidationError, match="subdominio"):
        load_settings(cfg)


def test_rejects_daily_cap_above_40(tmp_path):
    cfg = _write_settings(tmp_path, lambda d: d["sending"].update(max_per_day_per_mailbox=100))
    with pytest.raises(ValidationError):
        load_settings(cfg)


def test_rejects_crawler_faster_than_1_rps(tmp_path):
    cfg = _write_settings(
        tmp_path, lambda d: d["crawler"].update(max_requests_per_second_per_domain=5)
    )
    with pytest.raises(ValidationError):
        load_settings(cfg)
