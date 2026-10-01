# HANDOFF · Tarifas por temporada
Punto alcanzado: Día 8 (QA) sin Stripe real; falta la parte de Stripe para cerrar P3   ·   Rama: claude/aerodiverti-seasonal-pricing-d7z472   ·   Último commit: ver `git log -1`

## Hecho
- **Días 2-3 (P2):** `resolve_price`, tablas, `/api/prices.php`, checkout con recálculo, metadata, router local. Ver commits `Tarifas:` y `Checkout:`.
- **Día 4-5, panel (`server/lib/panel_pricing.php`, pestaña Precios):** anticipo % por paquete, temporadas / fechas especiales / días de la semana (crear, editar, duplicar, pausar, eliminar), días sin vuelo (marcar / volver a abrir), avisos de coincidencia, historial legible y paginado, vista previa (iframe + lista de 60 días). Detalle de reserva muestra la tarifa aplicada y la versión.
- **Día 6, calendario:** `src/lib/pricing/{calendar,api,charge}.ts` (26 tests vitest), `PriceCalendar.tsx/.css`, `PricePreview.tsx`, `src/pages/panel-vista-previa.astro` (noindex, fuera del sitemap, sin banner de cookies: `BaseLayout` nuevo prop `analytics`).
- **Día 7, reserva:** `BookingWidget.tsx` sondea `/api/prices`; con tarifas muestra el calendario y cotiza por fecha (centavos), recotiza al cambiar de paquete, maneja 409 `price_changed` / `date_blocked` y 422 `date_invalid`; con bandera apagada, paquete sin anticipo o sondeo fallido usa la fecha nativa con `min` = mañana en hora de México.

## Verificado (staging local, PHP 8.4, Chromium; sin Stripe)
- Reserva completa en escritorio y móvil (390 px) con calendario; sin scroll horizontal; teclado completo; consola sin errores propios.
- Precio cambiado entre ver y pagar: aviso con montos nuevos, sin fila nueva en `bookings`, el reintento cobra el precio nuevo.
- Fecha bloqueada antes de pagar: vuelve al paso 1, limpia la fecha, el calendario ya la muestra cerrada.
- Cambio de paquete con fecha elegida (recotiza / avisa / cae a fecha nativa si el paquete no tiene anticipo).
- Bandera apagada y sondeo 500: fecha nativa, montos de siempre. 23:30 CDMX: `min` = mañana.
- `npm run test:php` 40/40 (PHP 7.4.33 y 8.4) · `npm test` 27/27 · `tsc --noEmit` OK · `npm run build` OK · `npm run lint:php74` OK.

## Decisiones tomadas y por qué
- El widget **siempre** envía `expectedUnitPrice` / `expectedDeposit` (lo que la persona ve). Con la bandera apagada el servidor los ignora. Cierra el caso "sondeo caído + bandera encendida": antes se cobraba $2,650 mostrando $2,200; ahora responde 409 y avisa.
- Paquete sin % de anticipo se cobra como hoy, incluidos días sin vuelo (calendario y cobro no divergen).
- `amountDueNow` se reemplazó por `chargeCents` (centavos, sin flotantes); el anticipo de una fecha puede traer centavos.
- `BookingWidget.tsx` ya incumplía prettier antes de estos cambios; no se reformateó para no ensuciar el diff.
- Texto del sitio: `grep` en `src/content` y `src/pages` **no** encuentra el monto fijo de $1,000 (D8 no requiere cambios de contenido).

## Día 8 (QA) hecho sin Stripe
Ver `docs/qa-tarifas.md`: 15 casos; 11 ✅, 4 🟡. Hallazgos: (1) PHP 7.4 trae base de zonas 2022.1 con horario de
verano en México: corregido con UTC-6 fijo; (2) OPcache retrasa unos segundos el cambio de la bandera;
(3) CLS de `/reservar` sube de 0.012 a ~0.055 (sigue verde) por un reacomodo previo del encabezado.

## Siguiente paso inmediato (sesión nueva con Stripe)
El usuario está configurando en el entorno: variable `STRIPE_SECRET_KEY` (clave restringida `rk_test_`) y red a
`api.stripe.com`, `checkout.stripe.com`, `js.stripe.com`, `m.stripe.network`, `m.stripe.com`, `r.stripe.com`,
`q.stripe.com`, `b.stripecdn.com`. Con eso:
1. `server/config.php` local ya lee `getenv('STRIPE_SECRET_KEY')`; reconstruir `dist/` (`npm ci && npm run build`)
   y recrear el `config.php` local (no versionado; ver plantilla en este archivo, sección Staging).
2. Cuatro cobros en Stripe test (base, temporada, fin de semana, fecha especial; uno con anticipo); anotar IDs.
3. Pagar con 4242 en Checkout (Playwright), leer `checkout.session.completed` de `/v1/events`, reenviarlo firmado
   al `webhook.php` local con un `whsec` local propio, comprobar `paid`.
4. Caso 3 contra Stripe. Completar `docs/qa-tarifas.md`, commit `checkpoint: P3 — QA de tarifas`.
D5 resuelto (captura del usuario, 2026-10-01, Stripe live, "Default · Tu cuenta"): **OXXO y Transferencias bancarias DESHABILITADOS**. Habilitados: Tarjetas, Apple Pay, Google Pay, Link, Cartes Bancaires, Bancontact, EPS, iDEAL (estos tres últimos son de euros y no aparecen en un cobro en MXN). Todos confirman el pago en el momento, así que hoy el webhook sin `payment_status` no marca como pagadas reservas sin pagar: riesgo teórico. Recomendación (no hecha, fuera de alcance): 3 líneas en `webhook.php` que ignoren el evento si `payment_status !== 'paid'`, por si algún día se activa OXXO o SPEI. Nota: la cuenta de Stripe es compartida (WooCommerce, Bókun).

## Riesgos / bloqueos abiertos
- Sin llave de Stripe ni red a Stripe en esta sesión: falta el cobro de prueba real (casos QA 1-3).
- PHP del hosting confirmado por el usuario (Webempresa, 2026-10-01): **8.3**, con `pdo_sqlite`, `mbstring` y `pdo` activos (cURL viene integrado y el checkout actual ya lo usa). Suite 40/40 en 7.4.33 y 8.4. Con 8.3 el desfase de una hora por horario de verano no aplica en producción (su base de zonas es posterior a 2022); el fix UTC-6 se queda como protección.
- `npm run check` no corre (falta `@astrojs/check`, ya faltaba antes).
- Pendientes de negocio: D1 (Berenice aprueba los %), D2, D5, D10.

## Cómo correr los tests
npm run test:php · npm test · npx tsc --noEmit · npm run build · PHPCS=/ruta/phpcs npm run lint:php74
Staging local: npm run build; php -S localhost:8000 server/dev/router.php
`server/config.php` local (no versionado):
    <?php $c = require __DIR__ . '/config.example.php';
    $c['site_url'] = 'http://localhost:8000';
    $c['stripe_secret_key'] = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_local_sin_llave';
    $c['catalog_path'] = dirname(__DIR__) . '/dist/data/catalog.json';
    $c['db']['path'] = '<scratchpad>/dev.sqlite';
    $c['pricing']['rules_enabled'] = getenv('RULES') !== '0';
    $c['panel']['password'] = 'dev';
    return $c;

## Consumo
`/usage` y `/cost` no disponibles en esta sesión en la nube. Fecha: 2026-10-01.
