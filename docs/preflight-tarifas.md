# Pre-flight · Tarifas por temporada (PROP-AERODIV-CAL-01)

Día 1 del plan de ejecución. Auditoría revalidada sobre `origin/claude/aerodiverti-platform-xajbkz`
en el commit `53156bc` (mismo HEAD que citó el plan original). Rama de trabajo de esta sesión:
`claude/aerodiverti-seasonal-pricing-d7z472` (la rama designada por el encargo; hace las veces de
la `feat/tarifas-temporada` del plan, porque no dispara despliegue — ver sección 2). Fecha de esta
revalidación: 2026-09-28.

## 0. Aviso operativo importante

La rama designada para esta sesión en el encargo (`claude/aerodiverti-seasonal-pricing-d7z472`)
**no contenía el proyecto Aerodiverti**: apuntaba a un repositorio distinto sin relación
(un wrapper de MarkItDown). Su copia remota ya estaba borrada. Se reconstruyó esa rama local
partiendo de `origin/claude/aerodiverti-platform-xajbkz` (que sí es el proyecto auditado, con el
mismo commit `53156bc` citado en el plan), sin perder trabajo porque no había commits propios de
tarifas todavía. A partir de aquí todo el trabajo de tarifas sigue el plan: se hace en
`feat/tarifas-temporada`, nunca directo en `claude/aerodiverti-platform-xajbkz`.

## 1. Revalidación de la auditoría (paso 2 del Día 1)

`git log 53156bc..HEAD --oneline` → vacío. No hay commits nuevos de Decap ni de nadie más desde
la fecha del plan. Todas las referencias `archivo:línea` del plan siguen vigentes; se verificaron
puntualmente estas y coinciden con el contenido citado:
- `server/public/api/create-checkout-session.php` (creación de la Checkout Session con `unit_amount`
  calculado en servidor, líneas ~120-193).
- `server/lib/stripe.php` (petición cURL sin SDK, `Stripe-Version 2024-06-20`, líneas ~23-51).
- `server/public/api/panel.php` (720 líneas; bloque de acciones de reservas en `do=action`,
  líneas ~144-182).
- `src/components/islands/BookingWidget.tsx` (input de fecha nativo con `min={tomorrow}`, líneas
  ~391-399; cálculo de `tomorrow` con `new Date(Date.now()+86400000).toISOString()`, que es UTC,
  línea ~216).

## 2. Verificaciones del paso 3 (semáforo)

| Verificación | Resultado | Evidencia |
|---|---|---|
| **PHP del hosting** | VERDE (actualizado 2026-10-01): el usuario confirma **PHP 8.3** en Webempresa con `pdo_sqlite` y `mbstring` activos. Texto original: ÁMBAR — **no confirmable desde esta sesión.** No hay acceso al panel de Webempresa. El código actual **no usa** ninguna sintaxis incompatible con 7.4 (`match`, `str_contains`, `?->`, `enum`, `readonly`, separadores numéricos): `grep` sobre `server/**/*.php` no encontró ninguna. Se trata como piso 7.4 tal como pide el plan, y la confirmación real queda pendiente de que Norman la haga en el panel del hosting antes de escribir PHP nuevo. |
| **Despliegue** | VERDE. `.github/workflows/deploy.yml:29-32`: `on.push.branches: [claude/aerodiverti-platform-xajbkz]` más `workflow_dispatch`. Un push a `feat/tarifas-temporada` (o a `claude/aerodiverti-seasonal-pricing-d7z472`) **no dispara** el workflow. El `exclude` del mirror FTP (líneas 191-192) ya protege `server/` y `.htaccess`; falta agregar ahí `server/cli`, `server/dev` y `server/tests` cuando existan (Día 2-3), y confirmar que el ZIP de la vía rápida (líneas 98-103, `zip -r dist/`) tampoco los incluye, porque solo empaqueta `dist/`, así que por diseño ya quedan fuera mientras vivan bajo `server/`. |
| **Esquema (`ensure_schema`)** | Confirmado el patrón que el plan da por hecho: `server/lib/db.php:53` llama `ensure_schema($pdo)` **en cada conexión** (arranque global, vía `db()`). Esto confirma que `ensure_pricing_schema` **no debe** colgarse de `ensure_schema` (rompería el "todo igual con la bandera apagada"); debe llamarse aparte, solo desde `panel.php`, `prices.php` y el checkout con `rules_enabled=true`, como dice el plan. |
| **Stripe `payment_method_types`** | VERDE (actualizado 2026-10-01): en Stripe live, OXXO y transferencias bancarias están **deshabilitados**; solo hay métodos de confirmación inmediata. Riesgo D5 teórico; se recomienda igual validar `payment_status` en el webhook (aparte). Texto original: ÁMBAR, riesgo confirmado en código: `create-checkout-session.php` **no fija `payment_method_types`** en los params de la Checkout Session (bloque `$params`, líneas ~144-176). Eso significa que Stripe usa lo que esté habilitado en el dashboard. Combinado con que `webhook.php:35-38` solo revisa `event.type === 'checkout.session.completed'` y nunca `payment_status`, **si OXXO o SPEI están activos en el dashboard de Stripe, el riesgo de D5 es real, no solo teórico.** No hay forma de confirmar el estado del dashboard desde este entorno; queda para que Norman lo revise y decida D5 antes del Día 2. |

## 3. Estructura ya presente en el repo (referencia para los Días 2-7)

- `server/lib/`: `bootstrap.php`, `channels.php`, `db.php`, `outbox.php`, `pricing.php`, `schema.php`,
  `stripe.php`, `templates.php`. No existen todavía `pricing_rules.php`, `pricing_store.php`,
  `panel_pricing.php` ni `panel_auth.php` — se crean en los Días 2, 3 y 4.
- `server/public/api/`: `create-checkout-session.php`, `deploy-extract.php`, `oauth/`, `panel.php`,
  `webhook.php`. No existe `prices.php` — se crea en el Día 3.
- No existen `server/cli/`, `server/dev/` ni `server/tests/` — se crean en los Días 2 y 3.
- 7 paquetes en `src/content/packages/*.md`, confirmando la tabla 3.6 del plan.
- `src/config/booking.ts`: `depositPerPassenger: 1000` (línea 16) y `amountDueNow()` (línea ~32),
  duplicado del lado servidor en `server/config.example.php:37` (`deposit_per_passenger => 1000`).
  Confirma el hallazgo ámbar de "Anticipo" del plan.

## 4. Documentación inconsistente (D9, informativo)

Confirmado: `DEPLOY.md:7` y `:62` dicen que el pipeline corre en cada push a `main`, cuando el
workflow real dispara sobre `claude/aerodiverti-platform-xajbkz`. Además `DEPLOY.md:31` da la beta
como `https://beta.aerodiverti.mx` mientras `GO-LIVE.md:3` la nombra `beta.aerodiverti.com.mx`. Se
deja para un commit aparte, con autorización de Norman, fuera de este sprint.

## 5. Tabla de anticipo calibrado propuesta (sección 3.6 del plan)

Sin cambios respecto al plan; se reproduce aquí para que quede junto con el resto de decisiones
pendientes. **Política de negocio: la aprueba Berenice, no solo Norman** (D1).

| Paquete | Base/persona | % propuesto | Anticipo/persona | Contra $1,000 |
|---|---|---|---|---|
| vuelo-esencial | $2,000 | 50 | $1,000.00 | 0 |
| vuelo-compartido | $2,200 | 45 | $990.00 | −10 |
| vuelo-celebracion | $2,600 | 38 | $988.00 | −12 |
| vuelo-con-transporte-cdmx | $3,200 | 31 | $992.00 | −8 |
| vive-teotihuacan-completo | $3,500 | 29 | $1,015.00 | +15 |
| vuelo-privado | $3,900 | 26 | $1,014.00 | +14 |
| vuelo-entrega-de-anillo | $4,750 | 21 | $997.50 | −2.50 |

## 6. Decisiones pendientes de Norman (sección 9 del plan, sin cambios)

D1 (Berenice aprueba el % de anticipo), D2 (confirmar "por persona" en todos los paquetes),
D3 (confirmar que se mantiene "Pagar todo"), D4 (entorno de prueba de Berenice: modo ensayo en
producción vs. subdominio beta), D5 (`payment_method_types` — ver semáforo arriba, riesgo
confirmado en código, falta confirmar el dashboard de Stripe), D6 (horizonte máximo de venta,
propuesta 365 días), D7 (si Berenice contrató el Anexo A de cupos), D8 (textos con el anticipo
fijo de $1,000, los ajusta el negocio desde `/admin`), D9 (documentación inconsistente, ver
sección 4 de este documento), D10 (agendar con Berenice la ventana de prueba del Día 9 — única
dependencia externa del cronograma de 2 semanas).

## 7. Semáforos: ¿hay bloqueos rojos?

**No hay bloqueos rojos abiertos.** El único punto que podría volverse rojo es la versión de PHP
del hosting si resulta menor a 7.4, y eso no se puede confirmar desde este entorno de ejecución
(sin acceso al panel de Webempresa). Se dejan como ámbar, a resolver por Norman, antes de escribir
PHP nuevo en el Día 2:
- Confirmar versión de PHP del hosting.
- Decidir D5 (payment_method_types / OXXO / SPEI).
- Aprobar D1 (tabla de % de anticipo) con Berenice.
- Agendar D10 (ventana de prueba de Berenice).

## 8. Criterio de terminado del Día 1

Este reporte queda listo para revisión de Norman. **No se avanza al Día 2 sin su visto bueno**,
según el plan.
