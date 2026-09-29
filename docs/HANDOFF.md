# HANDOFF · Tarifas por temporada
Punto alcanzado: P2   ·   Rama: claude/aerodiverti-seasonal-pricing-d7z472   ·   Último commit: ver `git log -1`

## Hecho (días 2 y 3)
- `server/lib/pricing_rules.php`: `resolve_price` (única implementación), `resolve_range`,
  `pricing_overlaps`, `pricing_validate_rule` y helpers de fecha en hora de México. Anticipo con
  enteros (`intdiv($p*$pct+50,100)`).
- `server/lib/schema.php`: `ensure_pricing_schema` (4 tablas + 4 columnas en `bookings`), fuera de
  `ensure_schema`, nunca lanza. `server/sql/schema.sql` con el equivalente MySQL (`VARCHAR` en llaves).
- `server/lib/pricing_store.php`: CRUD en transacción con `pricing_audit` y `pricing_meta.version`;
  `pricing_version` = `"{version}-{sha1 precios base, 8}"`.
- `server/public/api/prices.php` (contrato 3.4) y `server/lib/panel_auth.php` (sesión compartida).
- Checkout (contrato 3.5): 422 `date_invalid`, 409 `date_blocked`, 409 `price_changed` con `current`,
  columnas nuevas en `bookings` y metadata en la session y en `payment_intent_data`.
  `compute_charge(..., ?array $resolved = null)`.
- `server/tests/` (runner sin composer, 33 tests), `npm run test:php`, `npm run lint:php74`.
- Staging local: `server/dev/router.php` + proxy `/api` en `astro.config.mjs` (solo `astro dev`).

## Verificado en staging local (PHP 8.4, SQLite, datos de prueba solo locales)
- Día base, temporada, fin de semana y fecha especial: montos en `bookings` iguales al cálculo manual.
- `expected*` falsos → 409 sin fila; montos inventados en el body → ignorados; fecha bloqueada → 409;
  pasada, vacía, 30 de febrero y fuera del horizonte → 422; ninguno crea reserva.
- Metadata exacta que se enviaría a Stripe (arnés con la llamada a Stripe sustituida).
- Bandera apagada: filas idénticas a las del código original `53156bc` ($1,000 por pasajero).

## Siguiente paso inmediato (Día 4, Sonnet 5)
Panel: pestaña "Precios" (anticipo por paquete, temporadas/fechas especiales, días sin vuelo) en
`server/lib/panel_pricing.php`, usando `pricing_store.php`. Mostrar en el detalle de la reserva la
regla aplicada y `pricing_version` (pendiente de 3.5). Las funciones de store ya incluyen
`pricing_duplicate_rule` y `pricing_set_rule_active`.

## Decisiones tomadas y por qué
- `pricing_overlaps($rule, $rules)` sin el parámetro `$packageTitles`: el texto lo arma el panel.
- Paquete sin % de anticipo → flujo anterior completo, **incluidos los días sin vuelo** (no se
  bloquean). Así el calendario (que responde `enabled:false`) y el cobro nunca divergen.
- `ensure_pricing_schema` sin caché estática: es idempotente y se llama una vez por petición.
- Una fecha con formato inválido y la bandera encendida sigue respondiendo el 422 genérico
  "Fecha invalida." (sin `code`); con formato válido pero no vendible, 422 `date_invalid`.
- Se corrigió en `pricing_valid_ymd` la regex para no aceptar `"2026-01-01\n"` (`\z` en vez de `$`).

## Riesgos / bloqueos abiertos
- **Sin llave `sk_test`** en este entorno: falta el cobro de prueba real en Stripe test y el
  webhook con `stripe listen`. Necesita que Norman comparta una `sk_test_` o lo corra localmente.
- **PHP 7.4 sin Docker aquí:** se verificó con PHPCompatibility (7.4-) + lint por tokens. Sigue
  pendiente confirmar la versión real del hosting.
- `npm run check` no corre: el repo no tiene `@astrojs/check` (ya faltaba antes). `npm run build` OK.
- Pendientes del pre-flight: D1 (aprobación del % por Berenice), D5, D10.

## Cómo correr los tests
npm run test:php · npm run lint:php74 (PHPCS=/ruta/phpcs para PHPCompatibility) · npm run build
Staging local: php -S localhost:8000 server/dev/router.php  +  npm run dev

## Consumo
`/usage` y `/cost` no están disponibles en esta sesión en la nube. Fecha: 2026-09-29.
