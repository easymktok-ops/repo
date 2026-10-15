# PLAN — Easy Outreach Agent

Sistema de prospección orgánica y ética para Easy Marketing (easymarketing.mx).
Descubre empresas, audita su sitio, genera una muestra personalizada (mini-reporte PDF + mockup demo) y prepara mensajes que pasan por aprobación humana antes de enviarse.

Estado: **Fase 1 lista** (15-oct-2026), pendiente de la corrida real con `DENUE_TOKEN` para medir el criterio. Siguiente: Fase 2 al recibir "continúa".

---

## 1. Estructura de carpetas

```
easy-outreach-agent/
├── PLAN.md
├── README.md                      # arranque, checklist DNS (SPF/DKIM/DMARC), operación diaria
├── pyproject.toml                 # Python 3.12, uv; deps y config de pytest/ruff
├── .env.example                   # ANTHROPIC_API_KEY, PAGESPEED_KEY, DENUE_TOKEN, GOOGLE_PLACES_KEY (opcional), SMTP_*/IMAP_*, DRY_RUN=true
├── alembic.ini
├── migrations/                    # Alembic (SQLite hoy, Postgres después)
├── config/
│   ├── settings.yaml              # plazas, landing_url, remitente, warmup, límites, umbrales de score
│   ├── scoring.yaml               # pesos del score 0-100
│   ├── segments.yaml              # giro + ciudad, auto_send=false por defecto
│   └── countries/
│       └── mx.yaml                # fuentes, idioma, plantilla legal, horario laboral, zona horaria
├── prompts/                       # prompts versionados (v001_audit_summary.md, v001_email.md, ...)
├── templates/
│   ├── report/                    # HTML+CSS A4 del mini-reporte PDF
│   ├── demos/                     # cotizador, agenda, catálogo, seguimiento WhatsApp (HTML autocontenido)
│   ├── email/                     # cuerpo, pie legal, link de baja
│   └── dashboard/                 # Jinja2 + HTMX
├── src/outreach/
│   ├── __init__.py
│   ├── cli.py                     # Typer: discover, enrich, audit, score, sample, queue, send, replies, report, review-sample
│   ├── config.py                  # carga settings.yaml + .env con pydantic-settings
│   ├── logging.py                 # logs estructurados (structlog, JSON)
│   ├── db/
│   │   ├── base.py                # engine, session
│   │   └── models.py              # SQLAlchemy 2.0
│   ├── http/
│   │   ├── client.py              # httpx: User-Agent identificado, timeouts, retries
│   │   ├── robots.py              # cache de robots.txt por dominio
│   │   └── ratelimit.py           # máx 1 req/seg por dominio
│   ├── sources/
│   │   ├── base.py                # interfaz Source
│   │   ├── denue.py               # INEGI DENUE API
│   │   └── places.py              # Google Places API (oficial, apagado por defecto)
│   ├── enrich/
│   │   ├── contacts.py            # correo público, formulario, WhatsApp, con URL fuente
│   │   ├── stack.py               # WordPress/Wix/Shopify/a la medida, CRM, agenda
│   │   └── branding.py            # logo, colores, nombre, servicios (para el demo)
│   ├── audit/
│   │   ├── seo.py                 # PageSpeed, title/meta/H1, schema, sitemap, robots, canonical, enlaces rotos
│   │   ├── ux.py                  # Playwright: capturas, axe-core, CTA sobre el pliegue, formularios, peso
│   │   ├── microapps.py           # heurísticas de fricción -> oportunidad de micro app
│   │   └── summarize.py           # Claude: 3 hallazgos + quick win SOLO con evidencia guardada
│   ├── scoring.py
│   ├── samples/
│   │   ├── report_pdf.py          # render A4 con Chromium (print, preferCSSPageSize, printBackground)
│   │   ├── demo.py                # genera demo HTML con marca del prospecto, antes/después
│   │   └── publish.py             # sube demos a Cloudflare Pages (demo.easymarketing.mx)
│   ├── messaging/
│   │   ├── writer.py              # Claude: correo 90-120 palabras + variante formulario
│   │   └── validator.py           # bloquea cifras sin evidencia, promesas, falta de identificación/baja
│   ├── sending/
│   │   ├── suppression.py         # lista global, consultada antes de cualquier envío
│   │   ├── scheduler.py           # horario laboral MX, warmup, límites diarios, pausa por rebote/queja
│   │   ├── email_smtp.py          # SMTP, List-Unsubscribe (mailto)
│   │   ├── forms.py               # Playwright, solo sin CAPTCHA, captura del envío
│   │   └── sequence.py            # día 0 / 4 / 10, se detiene al primer reply
│   ├── replies/
│   │   ├── reader.py              # lectura del buzón por IMAP (respuestas y bajas)
│   │   └── classify.py            # interesado / no ahora / baja / rebote / otro + notificación
│   ├── web/
│   │   ├── app.py                 # FastAPI: cola de aprobación, embudo, página de baja
│   │   └── routes/
│   └── jobs/daily.py              # tarea diaria: descubrir, auditar, encolar N
├── data/                          # (gitignored) SQLite, capturas, PDFs, demos
└── tests/
    ├── fixtures/sites/            # sitios HTML locales para pruebas de integración
    ├── unit/                      # parsers, scoring, validador, supresión, robots, rate limit
    └── integration/
```

## 2. Esquema de base de datos

Todas las tablas llevan `id` (PK), `created_at`, `updated_at`. Fechas en UTC.

| Tabla | Campos principales | Notas |
|---|---|---|
| **runs** | job (`discover`/`audit`/`send`/...), params (JSON), started_at, finished_at, status, stats (JSON), api_cost_usd | Trazabilidad y costo por corrida |
| **companies** | name, legal_name, domain (UNIQUE), website_url, country, state, city, scian_code, giro, size_stratum, phone, address, source (`denue`/`places`/...), source_ref, source_url, stack (JSON), language, has_whatsapp, status (`new`/`enriched`/`audited`/`scored`/`queued`/`contacted`/`manual`/`discarded`), score, segment_id, run_id | Deduplicación por dominio normalizado |
| **contacts** | company_id, type (`email`/`form`/`whatsapp`/`phone`), value, source_url, found_at, is_valid, has_captcha | Solo datos publicados por la empresa, siempre con URL fuente |
| **audits** | company_id, run_id, pagespeed (JSON), seo (JSON), ux (JSON), microapps (JSON), screenshot_mobile, screenshot_desktop, raw_html_path, model, prompt_version, cost_usd | Una auditoría por corrida |
| **findings** | audit_id, category (`seo`/`ux`/`microapp`), title, description, severity (1-5), priority (1-3), is_quick_win, evidence_type (`api`/`screenshot`/`html`), evidence (JSON: valor, selector, fragmento, ruta de captura) NOT NULL, reviewed_ok (bool, nullable) | Sin evidencia no se inserta (restricción + validación) |
| **samples** | company_id, audit_id, pdf_path, demo_type (`cotizador`/`agenda`/`catalogo`/`whatsapp`), demo_path, demo_token (URL privada), demo_screenshot, branding (JSON) | Demo con `noindex`, datos rotulados como ilustrativos |
| **messages** | company_id, sample_id, channel (`email`/`form`), step (0/4/10), subject, body, body_original, prompt_version, validation (JSON), status (`draft`/`pending`/`approved`/`edited`/`discarded`/`sent`), approved_by, approved_at, edit_diff | `body_original` + `edit_diff` sirven para mejorar prompts |
| **sends** | message_id, contact_id, mailbox, scheduled_at, sent_at, dry_run (bool), provider_msg_id, status (`scheduled`/`sent`/`bounced`/`failed`/`skipped_suppressed`), error, form_screenshot | |
| **replies** | send_id, company_id, received_at, from_addr, subject, body, classification (`interesado`/`no_ahora`/`baja`/`rebote`/`otro`), confidence, notified_at | Detiene la secuencia |
| **suppression** | value (email o dominio, UNIQUE), kind (`email`/`domain`), reason (`baja`/`rebote`/`queja`/`manual`), source, created_at | Se consulta antes de cualquier envío |
| **segments** | name, country, giro, city, auto_send (default false), daily_cap, active | |
| **mailbox_stats** | mailbox, date, sent, bounced, complaints, paused (bool), pause_reason | Base de la pausa automática (>3% rebote, >0.1% queja) |

Índices: `companies(domain)`, `companies(status, score)`, `messages(status)`, `sends(mailbox, sent_at)`, `suppression(value)`.

## 3. Flujo por fase (resumen)

| Fase | Entregable | Criterio |
|---|---|---|
| 0 | Esqueleto, config, logging, modelos + migraciones, tests básicos, README | `pytest` en verde, `alembic upgrade head` limpio |
| 1 | DENUE + Places, dedupe, enriquecedor, CLI `discover`/`enrich` | >= 60% de 200 empresas con sitio válido y un canal de contacto |
| 2 | Auditoría SEO/UX/micro apps, resumen con Claude, scoring, `review-sample` | >= 80% de hallazgos correctos en revisión de 20 |
| 3 | PDF A4, demo HTML, redacción, validador | Norman aprueba >= 70% sin editar |
| 4 | Dashboard, envío con límites y secuencia, formularios, replies | Piloto de 100 envíos sin rebote > 3% |
| 5 | Embudo, tarea diaria, export CSV/Sheets, multi-país | Costo por reunión medible |

Cada fase termina con pruebas, resumen de 5 líneas y espera de "continúa".

## 4. Decisiones (confirmadas el 14-oct-2026)

Principio rector: **priorizar siempre plataformas sin costo**; solo se paga lo inevitable (API de Claude, centavos por prospecto).

| # | Tema | Decisión |
|---|---|---|
| 1 | Ubicación del código | Carpeta `easy-outreach-agent/` dentro de `easymktok-ops/repo` (la integración no puede crear repos). Se puede mover a un repo propio más adelante sin cambios de código. |
| 2 | Envío | **SMTP** + **IMAP** para leer respuestas. Buzón `norman@hola.easymarketing.mx` en el hosting de correo que ya tiene easymarketing.mx (costo ya cubierto). No se usan Brevo, SES, Mailgun ni similares: sus términos prohíben correo frío y pueden cerrar la cuenta. Respaldo de bajo costo: Zoho Mail Lite (el plan gratuito de Zoho no da SMTP/IMAP). |
| 3 | Descubrimiento | **DENUE** (gratis, token de INEGI) es la fuente principal. **Google Places** queda implementado pero **apagado por defecto** (pide tarjeta; tiene uso gratuito mensual); se enciende solo si DENUE no alcanza el 60% de contacto. SIEM fuera del MVP. |
| 3 | Auditoría | **PageSpeed Insights API** (gratis, solo llave de Google Cloud, sin facturación), Playwright y axe-core (open source). |
| 3 | Claude | Se usa solo donde aporta: resumen de hallazgos y redacción para prospectos que ya pasaron el score; la clasificación de respuestas va con un modelo ligero. Las heurísticas hacen el trabajo pesado. Tope de gasto diario configurable. |
| 3 | Base de datos y ejecución | SQLite local. Corre en tu computadora con cron (gratis). Más adelante, opcional: GitHub Actions (2,000 min/mes gratis en repos privados). |
| 3 | Entregabilidad | mail-tester.com (gratis, 3 pruebas/día), Google Postmaster Tools (gratis), reportes DMARC con un lector gratuito. |
| 4 | Demos | **Cloudflare Pages (gratis)** en `demo.easymarketing.mx` (un CNAME en el DNS actual). URL privada por prospecto (`/d/<token-aleatorio>/`), `noindex` en meta y en cabecera `X-Robots-Tag`, sin listado. Se borran a los 60 días. |
| 4 | Landing | **https://easymarketing.mx/demand/ se queda como único destino de CTA**, no como alojamiento del demo. Cada botón del PDF, del demo y del correo apunta ahí con UTM (`?utm_source=outreach&utm_medium=email&utm_content=<token>`) para atribuir cada reunión al prospecto. |
| 4 | Baja | Sin servidor: `List-Unsubscribe` por mailto (`baja@hola.easymarketing.mx`) + texto "responde BAJA" + página `easymarketing.mx/baja` con formulario simple que manda a ese buzón. El lector IMAP da de alta la supresión automáticamente. |

Pendientes menores (se resuelven con el default si no dices otra cosa):
- Aviso de privacidad: se enlaza `https://easymarketing.mx/aviso-de-privacidad/` (ajustar si la URL es otra).
- Dashboard local en `localhost`, sin autenticación en el MVP.
- Giros iniciales: Querétaro — clínicas dentales y estéticas; CDMX — despachos profesionales y desarrolladoras inmobiliarias; Monterrey — manufactura y servicios industriales B2B.

## 5. Reglas duras (recordatorio)

Sin evasión de CAPTCHA; sin scraping de LinkedIn ni de Google Maps fuera de su API; robots.txt, User-Agent `EasyOutreachBot/1.0 (+https://easymarketing.mx)` y 1 req/seg por dominio; solo contactos publicados por la empresa con URL fuente; cola de aprobación obligatoria (`auto_send=false`); identificación, origen del dato y baja en cada correo; supresión global; máx 40/día por buzón y pausa por rebote > 3% o queja > 0.1%; hallazgos solo con evidencia; `DRY_RUN=true` por defecto; sin emoji, íconos SVG.

---

## 6. Notas de Fase 1

- Migración `0002`: `companies.site_status` (ok / unreachable / http_error / parked / robots / not_html / no_site) y `companies.enriched_at`.
- La extracción de marca (logo, colores) para el demo se movió a la Fase 3, que es donde se usa.
- Los correos de terceros que aparecen en un sitio (la agencia que lo hizo, plantillas) se descartan; solo se aceptan correos del dominio de la empresa o de proveedores gratuitos.

---

**Siguiente paso:** correr `outreach discover -s qro-salud --limit 200`, `outreach enrich` y `outreach stats` con el `DENUE_TOKEN` real para validar el 60%; luego "continúa" para la Fase 2 (necesita PAGESPEED_KEY y ANTHROPIC_API_KEY).
