"""Carga de configuración: config/*.yaml para reglas de negocio y .env para secretos."""

from __future__ import annotations

from functools import lru_cache
from pathlib import Path

import yaml
from pydantic import BaseModel, Field, model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

PROJECT_ROOT = Path(__file__).resolve().parents[2]
CONFIG_DIR = PROJECT_ROOT / "config"


class Secrets(BaseSettings):
    """Valores sensibles o de entorno. Solo se leen de .env o variables de entorno."""

    model_config = SettingsConfigDict(env_file=PROJECT_ROOT / ".env", extra="ignore")

    dry_run: bool = True
    database_url: str = f"sqlite:///{PROJECT_ROOT / 'data' / 'outreach.db'}"

    denue_token: str = ""
    pagespeed_key: str = ""
    google_places_key: str = ""
    anthropic_api_key: str = ""

    smtp_host: str = ""
    smtp_port: int = 587
    smtp_user: str = ""
    smtp_password: str = ""
    imap_host: str = ""
    imap_port: int = 993
    imap_user: str = ""
    imap_password: str = ""

    cloudflare_api_token: str = ""
    cloudflare_account_id: str = ""


class Company(BaseModel):
    name: str
    website: str
    landing_url: str
    privacy_url: str
    unsubscribe_url: str


class Pilot(BaseModel):
    country: str
    cities: list[str]


class People(BaseModel):
    approver: str
    interested_notify: str
    public_contact: str


class WarmupWeek(BaseModel):
    week: int
    min: int
    max: int


class Warmup(BaseModel):
    weeks: list[WarmupWeek]


class BusinessHours(BaseModel):
    start: str
    end: str
    days: list[str]


class Sending(BaseModel):
    domain: str
    from_name: str
    from_address: str
    reply_to: str
    unsubscribe_mailto: str
    max_per_day_per_mailbox: int = Field(le=40)
    pause_bounce_rate: float
    pause_complaint_rate: float
    sequence_days: list[int]
    business_hours: BusinessHours
    warmup: Warmup

    @model_validator(mode="after")
    def _from_uses_sending_domain(self) -> Sending:
        if not self.from_address.endswith("@" + self.domain):
            raise ValueError("from_address debe usar el subdominio de envío, nunca la raíz")
        return self


class Crawler(BaseModel):
    user_agent: str
    max_requests_per_second_per_domain: float = Field(gt=0, le=1)
    timeout_seconds: float
    retries: int = Field(default=2, ge=0, le=5)
    max_pages_per_site: int = Field(default=4, ge=1, le=10)
    max_page_bytes: int = 2_000_000
    workers: int = Field(default=8, ge=1, le=32)


class SourceToggle(BaseModel):
    enabled: bool
    page_size: int = Field(default=500, ge=1, le=1000)


class Sources(BaseModel):
    denue: SourceToggle
    places: SourceToggle


class LLM(BaseModel):
    analysis_model: str
    light_model: str
    max_daily_cost_usd: float


class Demos(BaseModel):
    base_url: str
    retention_days: int


class Score(BaseModel):
    threshold: int = Field(ge=0, le=100)


class Messaging(BaseModel):
    language: str
    min_words: int
    max_words: int
    positioning: str


class Settings(BaseModel):
    company: Company
    pilot: Pilot
    people: People
    sending: Sending
    crawler: Crawler
    sources: Sources
    llm: LLM
    demos: Demos
    score: Score
    messaging: Messaging


class ScoringWeights(BaseModel):
    size: int
    severity: int
    contactability: int
    fit: int

    @model_validator(mode="after")
    def _sum_100(self) -> ScoringWeights:
        total = self.size + self.severity + self.contactability + self.fit
        if total != 100:
            raise ValueError(f"los pesos de scoring deben sumar 100 (suman {total})")
        return self


class DenueQuery(BaseModel):
    """Consulta BuscarAreaActEstr de DENUE. Claves INEGI (entidad, municipio) y SCIAN."""

    entidad: str = Field(pattern=r"^\d{2}$")
    municipios: list[str] = ["0"]
    # Clave SCIAN de 2 (sector), 3 (subsector), 4 (rama) o 6 dígitos (clase).
    actividades: list[str] = Field(min_length=1)
    # 0 = todos; 1 = 0-5 personas, 2 = 6-10, 3 = 11-30, 4 = 31-50, 5 = 51-100, 6 = 101-250, 7 = 251+
    estratos: list[int] = [0]

    @model_validator(mode="after")
    def _valid_codes(self) -> DenueQuery:
        for code in self.actividades:
            if not code.isdigit() or len(code) not in (2, 3, 4, 6):
                raise ValueError(f"clave SCIAN inválida: {code!r} (usa 2, 3, 4 o 6 dígitos)")
        for m in self.municipios:
            if m != "0" and not (m.isdigit() and len(m) == 3):
                raise ValueError(f"clave de municipio inválida: {m!r} (3 dígitos o 0)")
        if any(e not in range(8) for e in self.estratos):
            raise ValueError("los estratos van de 0 a 7")
        return self


class PlacesQuery(BaseModel):
    queries: list[str] = Field(min_length=1)


class SegmentConfig(BaseModel):
    name: str
    country: str
    city: str
    giro: str
    auto_send: bool = False
    daily_cap: int = 40
    active: bool = True
    denue: DenueQuery | None = None
    places: PlacesQuery | None = None


class CountryConfig(BaseModel):
    code: str
    name: str
    language: str
    timezone: str
    sources: list[str]
    legal: dict[str, str]


def _load_yaml(path: Path) -> dict:
    with path.open(encoding="utf-8") as fh:
        return yaml.safe_load(fh) or {}


def load_settings(config_dir: Path = CONFIG_DIR) -> Settings:
    return Settings.model_validate(_load_yaml(config_dir / "settings.yaml"))


def load_scoring(config_dir: Path = CONFIG_DIR) -> ScoringWeights:
    return ScoringWeights.model_validate(_load_yaml(config_dir / "scoring.yaml")["weights"])


def load_segments(config_dir: Path = CONFIG_DIR) -> list[SegmentConfig]:
    data = _load_yaml(config_dir / "segments.yaml")
    return [SegmentConfig.model_validate(s) for s in data.get("segments", [])]


def get_segment(name: str, config_dir: Path = CONFIG_DIR) -> SegmentConfig:
    for segment in load_segments(config_dir):
        if segment.name == name:
            return segment
    raise KeyError(f"no existe el segmento {name!r} en segments.yaml")


def load_country(code: str, config_dir: Path = CONFIG_DIR) -> CountryConfig:
    path = config_dir / "countries" / f"{code.lower()}.yaml"
    return CountryConfig.model_validate(_load_yaml(path))


@lru_cache
def get_settings() -> Settings:
    return load_settings()


@lru_cache
def get_secrets() -> Secrets:
    return Secrets()
