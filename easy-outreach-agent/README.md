# Easy Outreach Agent

Prospección orgánica y ética para Easy Marketing (easymarketing.mx). El sistema descubre empresas, audita su sitio, genera una muestra personalizada (mini-reporte PDF + mockup demo) y prepara mensajes que **siempre** pasan por aprobación humana antes de enviarse.

Plan completo, esquema de base de datos y decisiones: [PLAN.md](PLAN.md).

## Estado

| Fase | Estado |
|---|---|
| 0. Esqueleto (config, logging, modelos, migraciones, tests) | Lista |
| 1. Descubrimiento y enriquecimiento | Pendiente |
| 2. Auditoría y scoring | Pendiente |
| 3. Muestra y mensaje | Pendiente |
| 4. Cola, envío y seguimiento | Pendiente |
| 5. Métricas y operación | Pendiente |

## Arranque

Requisitos: Python 3.12 o superior y [uv](https://docs.astral.sh/uv/).

```bash
cd easy-outreach-agent
uv venv && source .venv/bin/activate
uv pip install -e ".[dev]"
cp .env.example .env          # llena las claves que tengas; DRY_RUN=true se queda así
outreach config-check         # valida config/*.yaml y dice qué claves faltan por fase
outreach init-db              # crea data/outreach.db con las migraciones
pytest                        # pruebas
```

## Configuración

- `config/settings.yaml`: remitente, límites de envío, calentamiento, CTA, crawler, fuentes, modelos.
- `config/scoring.yaml`: pesos del score (suman 100).
- `config/segments.yaml`: giro + ciudad del piloto; `auto_send: false` por defecto.
- `config/countries/mx.yaml`: idioma, zona horaria y pie legal por país.
- `.env`: claves y contraseñas. Nunca se sube al repo.

## Reglas que el código hace cumplir

- `DRY_RUN=true` por defecto: nada se envía de verdad hasta que lo cambies a mano.
- Todo mensaje nace en estado `pending` y necesita aprobación.
- Un hallazgo sin evidencia no se puede guardar; un contacto sin URL fuente tampoco.
- El remitente debe usar el subdominio `hola.easymarketing.mx`; la configuración falla si apunta a la raíz.
- Tope de 40 envíos por día por buzón (la configuración no acepta más).
- Antes de cualquier envío se consulta la lista de supresión (correo y dominio completo).

## Checklist de dominio: hola.easymarketing.mx

1. **Buzón**: crear `norman@hola.easymarketing.mx` en el hosting de correo actual con acceso SMTP (envío) e IMAP (lectura de respuestas). Crear también `baja@hola.easymarketing.mx` o un alias que llegue al mismo buzón.
2. **SPF** (registro TXT en `hola.easymarketing.mx`), con el include que indique tu proveedor:
   `v=spf1 include:<proveedor> -all`
3. **DKIM**: generar la llave en el panel del proveedor y publicarla como TXT en `<selector>._domainkey.hola.easymarketing.mx`.
4. **DMARC** (TXT en `_dmarc.hola.easymarketing.mx`):
   `v=DMARC1; p=none; rua=mailto:<tu-correo-de-reportes>; fo=1`
   Subir a `p=quarantine` después de 4 semanas sin incidencias.
5. **MX** del subdominio, si el buzón recibe correo en él (lo indica el proveedor).
6. **Verificación**: mail-tester.com (meta 9/10 o más) y alta del subdominio en Google Postmaster Tools.
7. **Páginas públicas**: aviso de privacidad y página de baja (`easymarketing.mx/baja`) publicadas.
8. **Calentamiento de 3 semanas** antes de cualquier envío frío: semana 1 de 5 a 10 por día, semana 2 de 15 a 20, semana 3 de 25 a 30, siempre a contactos conocidos que respondan.
9. **Demos**: registro CNAME `demo.easymarketing.mx` hacia el proyecto de Cloudflare Pages (Fase 3).

## Estructura

```
config/       reglas de negocio en YAML
migrations/   Alembic (SQLite hoy, Postgres después)
prompts/      prompts versionados
templates/    PDF, demos, correo y dashboard
src/outreach/ código (db, sources, enrich, audit, samples, messaging, sending, replies, web, jobs)
tests/        unitarias e integración
data/         base local, capturas, PDFs y demos (no se sube al repo)
```
