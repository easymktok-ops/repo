# HANDOFF · Tarifas por temporada
Punto alcanzado: P3 (QA del Día 8 cerrado)   ·   Rama: claude/aerodiverti-seasonal-pricing-d7z472   ·   Último commit: ver `git log -1`

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

## Día 8 (QA) cerrado, checkpoint P3
`docs/qa-tarifas.md`: 13 de 15 casos ✅, incluidos los cobros reales en Stripe test (4 sesiones, pago con 4242,
evento real reenviado firmado al webhook local). Quedan 🟡: caso 8 (iPhone/Android reales) y caso 10 (CLS de
`/reservar` ~0.055, sigue verde; viene de un reacomodo previo del encabezado).
Hallazgos y arreglos: "hoy" con UTC-6 fijo (PHP 7.4 traía horario de verano viejo); OPcache demora el cambio de
la bandera; webhook ahora exige `payment_status = paid` (commit aparte, autorizado).
Entorno: `STRIPE_SECRET_KEY` (rk_test, modo prueba) y red a `*.stripe.com`, `*.stripe.network`, `*.stripecdn.com`
configurados. Para Playwright con Stripe: importar el CA del proxy al NSS del navegador
(`certutil -A -d sql:/root/.pki/nssdb -n ccr-agent-proxy -t "C,," -i /root/.ccr/agent-proxy-ca.crt`, requiere
`apt-get install libnss3-tools`) y lanzar Chromium con `proxy: { server: process.env.HTTPS_PROXY }`.

## Siguiente paso inmediato (Día 9, ahora en ENTORNO DE PRUEBAS)
Decisión de Norman (2026-10-02): probar primero en un subdominio de pruebas, no en producción (D4 = opción B).
D2 = sí (por persona), D3 = sí (se mantiene "Pagar todo"). D1 sin respuesta de Berenice: no se cargan % en
producción; en pruebas los captura quien pruebe.
- Paquete: `bash server/dev/pruebas/armar.sh` → `dist-aerodiverti-pruebas.zip` (pasos en `docs/entorno-pruebas.md`).
- **Montado (2026-10-02):** `pruebas.aerodiverti.com.mx` en `public_html/pruebas.aerodiverti.com.mx`, PHP 8.3,
  Let's Encrypt (vence 31-12-2026), ZIP extraído, `config.php` lleno. Verificado por Norman: `/reservar` abre
  (fecha nativa: aún sin % cargado), `/server/config.php` → 403, panel con pestaña Precios.
- Siguiente: cargar un % y una temporada en el panel de pruebas, pago 4242 y confirmar que el webhook la marca
  pagada. Si se agrega `pruebas.aerodiverti.com.mx` a la red permitida del entorno, Claude puede probarlo directo.
- Luego: verificar el entorno (403 en carpetas internas, panel, calendario, pago 4242) y sesión con Berenice con
  `docs/guia-berenice-precios.pdf`. Producción **no** se toca hasta que Norman lo apruebe por escrito.
- También listo para producción cuando toque: `tarifas-backend.zip` (10 PHP) y la sección de tarifas de `DEPLOY.md`.
- Pendiente de Norman: abrir `https://aerodiverti.com.mx/server/data/aerodiverti.sqlite` en el navegador. Si se
  descarga, la base de reservas de producción está expuesta y hay que bloquearla ya (no se pudo comprobar desde
  aquí: la red de la sesión no llega al sitio).

## Mejoras UX / CRO tras la prueba en pruebas (2026-10-02)
Norman probó el entorno de pruebas completo (calendario, Stripe 4242, webhook → Pagado) y pidió:
- **Panel:** clic en la fecha abre el selector nativo (`showPicker`), `color-scheme: dark`; interruptores
  **Ayudas** / **Animaciones** (localStorage, aplican a todo el panel); globos al guardar (3 suben, 1.5 s) y
  globo que se revienta en error (`.flash.err` o primer `.ferr`). Sin animación con `prefers-reduced-motion` o
  con Animaciones apagado. Código: `panel_ux_head()` / `panel_ux_foot()` en `panel_pricing.php`.
- **Calendario:** precio más visible (0.82rem, 600, tinta plena; móvil 0.72rem); número del día baja un tono.
- **CRO:** "Reservar vuelo" del hero, DoubleExposure y Globopuerto ya no abre WhatsApp: va a `/reservar`.
  Hero: "Reservar mi vuelo" + ancla "Desde $X por persona · Paga en línea o aparta con anticipo" (X = menor
  `priceFrom` de paquetes reservables; sin paquetes con precio no se muestra). WhatsApp queda como canal de
  ayuda (botón de chat, Contacto, paquetes "Consultar").

## Riesgos / bloqueos abiertos
- PHP del hosting confirmado por el usuario (Webempresa, 2026-10-01): **8.3**, con `pdo_sqlite`, `mbstring` y `pdo` activos (cURL viene integrado y el checkout actual ya lo usa). Suite 40/40 en 7.4.33 y 8.4. Con 8.3 el desfase de una hora por horario de verano no aplica en producción (su base de zonas es posterior a 2022); el fix UTC-6 se queda como protección.
- `npm run check` no corre (falta `@astrojs/check`, ya faltaba antes).
- Pendientes de negocio: D1 (Berenice aprueba los %), D10. D2, D3, D4 y D5 resueltos.

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
`/usage` y `/cost` no disponibles en esta sesión en la nube. Fecha: 2026-10-02.
