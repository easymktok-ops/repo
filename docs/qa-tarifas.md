# QA · Tarifas por temporada (Día 8)

Rama `claude/aerodiverti-seasonal-pricing-d7z472`. Entorno: staging local (`php -S` + `server/dev/router.php`,
SQLite local con datos de prueba, `dist/` compilado), Chromium (Playwright) y Lighthouse 12.
Fecha: 2026-10-01.

**Estado:** 13 de 15 casos verificados, incluidos los cobros reales en **Stripe test** (clave restringida
`rk_test_`, `livemode: false`). Quedan a medias el 8 (falta un iPhone y un Android reales) y el 10 (CLS).

Leyenda: ✅ verificado · 🟡 verificado sin Stripe real (falta evidencia en dashboard) · ⏳ pendiente.

| # | Caso | Estado | Evidencia |
|---|---|---|---|
| 1 | Precio del calendario = cobrado (base, temporada, fin de semana, fecha especial) | ✅ | 4 sesiones reales en Stripe test, `amount_total` = calculado (tabla "Stripe test" abajo). Recorrido desde el widget: celda del 31 dic "3,300", botón "Ir a pagar · $1,089", Stripe Checkout "1089,00 MXN". |
| 2 | Anticipo = % correcto y saldo registrado | ✅ | En Stripe: 24 dic × 2 × 45 % → `amount_total` 238500, `unit_amount` 119250, metadata `deposit` 238500, `balance` 291500, `depositPercent` 45; en `bookings` lo mismo. 31 dic con 33 % propio de la fecha especial → 108900. |
| 3 | La manipulación no altera el cobro | ✅ | Body con `amount`, `unit_amount`, `price`, `pricePerPerson`, `amount_total` = 1 o 100: la sesión de Stripe se creó por 265000 (lo resuelto). `expectedUnitPrice` falso: 409 `price_changed` y **ninguna** sesión creada en Stripe (verificado listando las sesiones). |
| 4 | Fecha bloqueada no reservable | ✅ | UI: celda `aria-disabled`, "Sin vuelo", el clic no selecciona. `curl` directo: 409 `date_blocked`, sin fila en `bookings`. |
| 5 | Precio cambia entre ver y pagar | ✅ | Paso 3 → cambio de precio en el panel → "Ir a pagar": aviso "ahora es $2,900 por persona y pagas $2,610 hoy", sigue en el paso 3, sin fila nueva; el reintento envía 290000/130500 y la reserva queda con el precio nuevo. |
| 6 | Cambio en el panel aparece sin acción extra | ✅ | El checkout lee las reglas en cada petición (caso 5). `/api/prices.php` responde `Cache-Control: public, max-age=60` (configurable, `0` = sin caché); los errores van con `no-store`. Pendiente en producción: ver si LiteSpeed respeta el header (`x-litespeed-cache`). |
| 7 | Rango que cruza el año | ✅ | Temporada 15 dic–6 ene visible en diciembre y enero; cobro el 1 ene: 2027-01-01 → $2,650. Tests PHP y vitest de cruce de año. |
| 8 | Móvil y escritorio | 🟡 | Chromium 1200 px y 390 px (táctil): flujo completo, sin scroll horizontal, celdas ≥ 54 px. `/en/reservar`: semana desde domingo, textos y `aria-label` en inglés. Falta un iPhone y un Android reales. |
| 9 | Sin reglas, todo igual que hoy | ✅ | Ver "Caso 9" abajo. |
| 10 | Consola limpia y Lighthouse igual o mejor | 🟡 | Consola sin errores propios (solo las respuestas 409/502 esperadas). Lighthouse: rendimiento, accesibilidad, buenas prácticas y SEO iguales o mejores; **CLS sube** de 0.012–0.039 a ~0.055 (sigue "bueno", < 0.1). Ver la tabla y la explicación abajo. |
| 11 | La reversa funciona en caliente | ✅ | Ver "Caso 11" abajo (ojo con OPcache). |
| 12 | Horario de México | ✅ | 23:30 CDMX (reloj del navegador simulado): mínimo = día siguiente. Además, ver "Hallazgo: PHP 7.4 y horario de verano". |
| 13 | El sondeo de precios falla | ✅ | `/api/prices.php` forzado a 500: input nativo, reserva completa. El checkout sigue validando: como el widget envía lo que muestra, si el servidor tiene tarifas responde 409 y avisa del precio real en vez de cobrar distinto. |
| 14 | PHP 7.4 | ✅ | PHP **7.4.33 real** (WebAssembly, `@php-wasm/node`, con `pdo_sqlite`, `mbstring`, `curl`): suite 40/40, `php -l` de los 28 PHP versionados, y `prices.php` y el checkout ejecutados de punta a punta. Más PHPCompatibility 7.4-. |
| 15 | Migración con la bandera apagada | ✅ | Tablas nuevas creadas y `rules_enabled=false`: la reserva sale idéntica a la del código original `53156bc` ($1,000 por pasajero, sin columnas de tarifa). |

## Hallazgo: PHP 7.4 y horario de verano (corregido)

Con PHP 7.4.33 real falló un test. La base de zonas que trae PHP 7.4 (2022.1) es anterior a que México
eliminara el horario de verano (octubre de 2022) y pone la Ciudad de México en UTC-5 de abril a octubre.
De 23:00 a 24:00 el servidor creía que ya era el día siguiente y el checkout habría rechazado la fecha de
mañana. **Corregido** (commit "Tarifas: \"hoy\" en México con desfase fijo UTC-6"): servidor y calendario
usan UTC-6 fijo.

**Fuera de alcance, para que Norman lo sepa:** `server/lib/bootstrap.php` fija
`date_default_timezone_set('America/Mexico_City')` para todo el backend. Si el hosting corre PHP con una base
de zonas anterior a 2022.5 y sin la del sistema, las horas de `created_at`, `paid_at` y de las acciones del
panel quedarían una hora adelantadas de abril a octubre. No afecta cobros ni fechas de vuelo. Se confirma con
la versión de PHP del hosting.

## Caso 9: sin reglas, precio base

Base local sin ninguna temporada, fecha especial ni cierre, con los % de la tabla 3.6 cargados solo en la
SQLite local (no son datos de negocio aprobados). `/api/prices.php` para los 30 días de noviembre de 2026:

| Paquete | priceFrom | Precio del día | % | Anticipo/persona | Tabla 3.6 |
|---|---|---|---|---|---|
| vive-teotihuacan-completo | $3,500 | $3,500 | 29 % | $1,015.00 | $1,015.00 ✅ |
| vuelo-esencial | $2,000 | $2,000 | 50 % | $1,000.00 | $1,000.00 ✅ |
| vuelo-compartido | $2,200 | $2,200 | 45 % | $990.00 | $990.00 ✅ |
| vuelo-celebracion | $2,600 | $2,600 | 38 % | $988.00 | $988.00 ✅ |
| vuelo-con-transporte-cdmx | $3,200 | $3,200 | 31 % | $992.00 | $992.00 ✅ |
| vuelo-privado | $3,900 | $3,900 | 26 % | $1,014.00 | $1,014.00 ✅ |
| vuelo-entrega-de-anillo | $4,750 | $4,750 | 21 % | $997.50 | $997.50 ✅ |

Con la bandera apagada, el cobro es idéntico al de hoy (caso 15).

## Caso 11: reversa en caliente

Servidor corriendo, sin reiniciarlo, editando solo `rules_enabled` en `config.php`:

1. `true`: `/reservar` muestra el calendario.
2. `false`: `/reservar` vuelve a la fecha nativa. El checkout con `expectedUnitPrice: 1` cobra el flujo de
   siempre: 2 pax × $1,000 = `amount_now_cents` 200000, sin columnas de tarifa. Los `expected*` se ignoran.
3. `true` otra vez: vuelve el calendario (probado en `/en/reservar`).

**Dato operativo:** con OPcache activo, PHP puede seguir usando el `config.php` anterior unos segundos
(`opcache.revalidate_freq`, aquí 2 s). En la primera prueba el cambio inmediato no se aplicó; esperando 3 s
sí. En Webempresa ese intervalo puede ser mayor: después de cambiar la bandera hay que esperar uno o dos
minutos y verificar con `curl https://<sitio>/api/prices.php?packageId=vuelo-compartido&from=...&to=...`.
Además, la respuesta pública se cachea 60 s (`cache_seconds`). Se agrega a `DEPLOY.md` en la salida a
producción.

## Lighthouse (`/reservar`, `/en/reservar`)

Mediana de 3 corridas, configuración móvil por defecto de Lighthouse, mismo servidor local.
"Antes" = build del commit `53156bc`; "después" = esta rama con tarifas encendidas.

| Página | Versión | Rendimiento | Accesibilidad | Buenas prácticas | SEO | LCP | TBT | CLS |
|---|---|---|---|---|---|---|---|---|
| `/reservar` | antes | 90 | 99 | 100 | 100 | 3.15 s | 0 ms | 0.012 |
| `/reservar` | después | 97 | 99 | 100 | 100 | 2.10 s | 0–4 ms | 0.058 |
| `/en/reservar` | antes | 97 | 99 | 100 | 100 | 2.19 s | 0 ms | 0.039 |
| `/en/reservar` | después | 89–97 | 99 | 100 | 100 | 2.10–3.15 s | 0 ms | 0.033–0.054 |

El LCP salta entre ~2.1 s y ~3.15 s en ambas versiones según la corrida (es variación del servidor local, no
del cambio). **CLS:** el elemento desplazado es la sección completa de reserva, y eso ya pasaba en el original
(`/en/reservar` antes: 0.039, mismo elemento). La causa es un reacomodo de lo que está arriba del widget al
cargar. Con el calendario, el bloque de la fecha es más alto (esqueleto de 34rem del mismo alto que el
calendario, para que no haya un segundo salto al aparecer), así que el mismo reacomodo afecta un área mayor.
Sigue en verde (< 0.1). Si se quiere bajar, hay que corregir el reacomodo previo del encabezado, que es
anterior a este proyecto: queda propuesto, no hecho.

## Stripe test (2026-10-01)

Sesiones reales creadas por el checkout local con la clave restringida de prueba. `vuelo-compartido` con 45 %:

| Folio | Fecha | Tipo de día | Modo | Pax | Session | `amount_total` | `unit_amount` × qty | Metadata |
|---|---|---|---|---|---|---|---|---|
| AERO-1D1EC3D | 2 oct | base | anticipo | 2 | `cs_test_a1iZQGM26M…` | 198000 ✅ | 99000 × 2 | ruleId `base`, fullPrice 440000, deposit 198000, balance 242000, 45 % |
| AERO-CBDB5BF | 24 dic | temporada | anticipo | 2 | `cs_test_a1fhAUMSk8…` | 238500 ✅ | 119250 × 2 | fullPrice 530000, deposit 238500, balance 291500, 45 % |
| AERO-5EE44C1 | 26 dic | fin de semana | total | 3 | `cs_test_a1ZmTtUqRL…` | 750000 ✅ | 250000 × 3 | fullPrice 750000, balance 0 |
| AERO-953A112 | 31 dic | fecha especial | anticipo | 1 | `cs_test_a1sTH9dxWH…` | 108900 ✅ | 108900 × 1 | 33 % de la fecha |

Todas con `livemode: false`, `date`, `packageId`, `ruleId`, `unitPrice` y `pricingVersion` `6-88979cec`.
La primera fecha vendible se respeta: el 1 de octubre (hoy) devolvió 422 `date_invalid`.

**Pago y webhook:** la sesión AERO-CBDB5BF se pagó con la tarjeta 4242 en el Checkout real (Playwright) y
regresó a `/reserva-confirmada`. Stripe: `status complete`, `payment_status paid`, `pi_3ULlEQE3mCkhkZXy0oY2NpWd`.
El evento real `evt_1ULlESE3mCkhkZXyOBqnjQi6` se leyó de `/v1/events` y se reenvió al `webhook.php` local
firmado con un `whsec` local (no hay endpoint público al que Stripe pueda llamar):

- firma válida: 200, reserva `paid` con `payment_intent` y 2 notificaciones encoladas (cliente y admin);
- mismo evento otra vez: 200 `already` (idempotente);
- firma falsa: 400.

**Webhook endurecido** (commit aparte, autorizado): ahora ignora `checkout.session.completed` si
`payment_status` no es `paid`. Probado con eventos firmados: `unpaid` deja la reserva pendiente y `paid` la
marca pagada. En Stripe live, OXXO y transferencias están deshabilitados, así que hoy no había riesgo real.

**Observación (no es de este cambio):** la cuenta tiene activa la conversión de moneda de Stripe. Desde una IP
de EE. UU., Checkout ofrece primero USD (62.38 US$ por 1,089 MXN). El monto en MXN de la sesión no cambia.

**Falta en producción:** registrar el webhook real en Stripe (si no existe ya) y verificar un primer
`checkout.session.completed` real tras la salida (sección 6 del plan).

## Cómo repetir la prueba con PHP 7.4 real (sin Docker)

```bash
mkdir -p /tmp/php74 && cd /tmp/php74 && npm init -y && npm i @php-wasm/node @php-wasm/universal
# run74.mjs: carga PHP 7.4 (loadNodeRuntime('7.4', { emscriptenOptions: { processId: 1 } })),
# monta el repo con createNodeFsMountHandler y ejecuta php.cli([...]) volcando stdout y stderr.
node run74.mjs /ruta/al/repo/server/tests/run.php        # suite
node run74.mjs -l /ruta/al/repo/server/lib/pricing.php    # lint de un archivo
```
