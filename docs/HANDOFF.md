# HANDOFF · Tarifas por temporada
Punto alcanzado: fin del Día 7 (antes del QA del Día 8)   ·   Rama: claude/aerodiverti-seasonal-pricing-d7z472   ·   Último commit: ver `git log -1`

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
- `npm run test:php` 39/39 · `npm test` 26/26 · `tsc --noEmit` OK · `npm run build` OK · `npm run lint:php74` OK.

## Decisiones tomadas y por qué
- El widget **siempre** envía `expectedUnitPrice` / `expectedDeposit` (lo que la persona ve). Con la bandera apagada el servidor los ignora. Cierra el caso "sondeo caído + bandera encendida": antes se cobraba $2,650 mostrando $2,200; ahora responde 409 y avisa.
- Paquete sin % de anticipo se cobra como hoy, incluidos días sin vuelo (calendario y cobro no divergen).
- `amountDueNow` se reemplazó por `chargeCents` (centavos, sin flotantes); el anticipo de una fecha puede traer centavos.
- `BookingWidget.tsx` ya incumplía prettier antes de estos cambios; no se reformateó para no ensuciar el diff.
- Texto del sitio: `grep` en `src/content` y `src/pages` **no** encuentra el monto fijo de $1,000 (D8 no requiere cambios de contenido).

## Siguiente paso inmediato (Día 8, QA, checkpoint P3)
Recorrer `docs/qa-tarifas.md` con la tabla de la sección 5 del plan. Pendiente de evidencia real: cobros en **Stripe test** (IDs de session y metadata en el dashboard, webhook con `stripe listen`), Lighthouse antes/después de `/reservar` y `/en/reservar`, dispositivo iOS/Android real, PHP 7.4 real.

## Riesgos / bloqueos abiertos
- Sin `sk_test_` en este entorno: falta el cobro de prueba real (caso QA 1-3).
- Versión de PHP del hosting sin confirmar; la compatibilidad 7.4 se verificó con PHPCompatibility.
- `npm run check` no corre (falta `@astrojs/check`, ya faltaba antes).
- Pendientes de negocio: D1 (Berenice aprueba los %), D2, D5, D10.
- `/en/reservar` no se probó en navegador; comparte el widget (textos `en` incluidos).

## Cómo correr los tests
npm run test:php · npm test · npx tsc --noEmit · npm run build · PHPCS=/ruta/phpcs npm run lint:php74
Staging local: npm run build; php -S localhost:8000 server/dev/router.php (config local en `server/config.php`, no versionado)

## Consumo
`/usage` y `/cost` no disponibles en esta sesión en la nube. Fecha: 2026-09-30.
