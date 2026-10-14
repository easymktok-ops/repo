# PLAN — Easy Outreach Agent

Sistema de prospección orgánica y ética para Easy Marketing (easymarketing.mx).
Descubre empresas, audita su sitio, genera una muestra personalizada (mini-reporte PDF + mockup demo) y prepara mensajes que pasan por aprobación humana antes de enviarse.

Estado: **borrador para aprobación**. No se escribe código hasta recibir "continúa".

---

## 1. Estructura de carpetas

```
easy-outreach-agent/
├── PLAN.md
├── README.md                      # arranque, checklist DNS (SPF/DKIM/DMARC), operación diaria
├── pyproject.toml                 # Python 3.12, uv; deps y config de pytest/ruff
├── .env.example                   # ANTHROPIC_API_KEY, GOOGLE_PLACES_KEY, PAGESPEED_KEY, DENUE_TOKEN, SMTP/Gmail, DRY_RUN=true
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
│   │   ├── places.py              # Google Places API (oficial)
│   │   └── siem.py                # SIEM (solo si su robots.txt/términos lo permiten)
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
│   │   └── demo.py                # genera demo HTML con marca del prospecto, antes/después
│   ├── messaging/
│   │   ├── writer.py              # Claude: correo 90-120 palabras + variante formulario
│   │   └── validator.py           # bloquea cifras sin evidencia, promesas, falta de identificación/baja
│   ├── sending/
│   │   ├── suppression.py         # lista global, consultada antes de cualquier envío
│   │   ├── scheduler.py           # horario laboral MX, warmup, límites diarios, pausa por rebote/queja
│   │   ├── email_smtp.py          # SMTP / Gmail API, List-Unsubscribe
│   │   ├── forms.py               # Playwright, solo sin CAPTCHA, captura del envío
│   │   └── sequence.py            # día 0 / 4 / 10, se detiene al primer reply
│   ├── replies/
│   │   ├── reader.py              # lectura del buzón (IMAP / Gmail API)
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

## 4. Supuestos a confirmar

1. **Ubicación del código.** Este repositorio (`easymktok-ops/repo`) contiene otro proyecto (markitdown-cli + imágenes). Propongo mantener el agente en la carpeta `easy-outreach-agent/` de este repo, o moverlo a un repo propio. ¿Cuál prefieres?
2. **Proveedor del buzón `norman@hola.easymarketing.mx`.** ¿Google Workspace (Gmail API + OAuth) o SMTP de otro proveedor (Zoho, Amazon SES, Postmark)? Define el módulo de envío y de lectura de replies.
3. **DENUE.** Requiere token gratuito de INEGI; ¿lo tramitas tú? Google Places y PageSpeed requieren un proyecto de Google Cloud con facturación activa.
4. **SIEM.** Su sitio no ofrece API pública documentada; propongo dejarlo fuera del MVP y usar solo DENUE + Places, salvo que confirmes una vía permitida.
5. **Hosting de demos y página de baja.** Opciones: subruta en easymarketing.mx (p. ej. `/d/<token>` y `/baja`) o un subdominio estático (`demo.easymarketing.mx`) con `noindex`. ¿Quién administra el WordPress/hosting de easymarketing.mx?
6. **Aviso de privacidad.** ¿Ya existe URL publicada? El pie del correo la enlaza.
7. **Dashboard.** Corre local (`localhost`) en el MVP. Si se quiere acceso remoto, se agrega autenticación básica y despliegue (Railway/Fly/VPS).
8. **Modelo de Claude.** Uso el modelo más reciente disponible para auditoría y redacción, con un modelo ligero para clasificación de replies y control de costo; configurable en `settings.yaml`.
9. **Giros iniciales por plaza.** Propuesta: Querétaro — clínicas dentales y estéticas; CDMX — despachos profesionales y desarrolladoras inmobiliarias; Monterrey — manufactura y servicios industriales B2B.
10. **Ejecución diaria.** Cron local o tarea programada en un servidor; ¿dónde va a correr?

## 5. Reglas duras (recordatorio)

Sin evasión de CAPTCHA; sin scraping de LinkedIn ni de Google Maps fuera de su API; robots.txt, User-Agent `EasyOutreachBot/1.0 (+https://easymarketing.mx)` y 1 req/seg por dominio; solo contactos publicados por la empresa con URL fuente; cola de aprobación obligatoria (`auto_send=false`); identificación, origen del dato y baja en cada correo; supresión global; máx 40/día por buzón y pausa por rebote > 3% o queja > 0.1%; hallazgos solo con evidencia; `DRY_RUN=true` por defecto; sin emoji, íconos SVG.

---

**Siguiente paso:** responde a los supuestos de la sección 4 (o di "continúa con los defaults") para arrancar la Fase 0.
