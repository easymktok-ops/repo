# STACK — Joyas Colombianas® Dinner & Show

Confirmado con Norman (2026-09-28): sin WordPress, sitio a medida. Este archivo es la referencia técnica única; no repetir en otros docs.

## Hosting
- Hostinger (plan existente de la clienta), hosting compartido.
- Sin Node en producción, sin servicios pagos adicionales.
- PHP: versión a confirmar contra el plan real de Hostinger (objetivo 8.1+; ver `docs/PROGRESS.md` → pendientes).

## Backend
- **PHP** puro, sin framework pesado (Laravel/Symfony quedan fuera — de más para el alcance y más difícil de mantener a 1h/día).
- Estructura de includes/templates simple (PHP + HTML), sin build step ni bundler.
- **MySQL** (estándar en Hostinger vía phpMyAdmin) para tickets, reservas, door list y contenido editable del panel. Se prefiere sobre SQLite por mejor manejo de escrituras concurrentes del webhook (conteo atómico de tickets).
- Acceso a datos con **PDO + prepared statements** (sin SQL concatenado).

## Frontend
- HTML5 + CSS3 a medida (sin Bootstrap/Tailwind de más — control total del peso y el look).
- JS vanilla para interacción (checkout, motion del hero, menús) — sin frameworks (React/Vue no aportan nada aquí y penalizan performance).
- Imágenes WebP/AVIF, lazy loading, dimensiones fijas; fuentes locales o con preload.

## Pagos
- **Mercado Pago** (confirmado en el cronograma; pendiente validar cobertura para pagos internacionales el 13 oct).
- Integración detrás de un **adaptador de pagos** (interfaz propia) para poder cambiar de pasarela sin reescribir el resto del flujo.
- API keys solo en variables de entorno / configuración del servidor — nunca en el repo.

## Tickets y door list
- Webhook de Mercado Pago: firma verificada, idempotente (no genera tickets duplicados si Mercado Pago reintenta la notificación).
- Ticket numerado secuencial (`#JCD-1001`, `#JCD-1002`...) generado solo tras confirmación de pago, con incremento atómico en MySQL (transacción con bloqueo) para evitar duplicados bajo concurrencia.
- Door List exportable a CSV/Excel desde el panel de administración (nombre, ticket, función/fecha, cantidad, estado del pago).

## Correo transaccional
- Por confirmar: SMTP de Hostinger vs. servicio externo (pregunta abierta en `docs/PLAN.md`).
- Envío vía PHPMailer (o similar) para entrega confiable, con número de ticket y datos de la reserva.

## Panel de administración
- Propio, a medida — no es un CMS genérico.
- Login con sesión + CSRF en formularios, sin exponer más superficie que la necesaria.
- Campos editables: títulos, fechas de función, precios, textos de landings, media (hero/galería) — solo lo que la clienta realmente necesita tocar.

## Medición
- GA4 + Meta Pixel vía `dataLayer` / `gtag.js`.
- Eventos `begin_checkout` y `purchase` (este último solo dispara con pago aprobado, en la URL de confirmación del propio dominio, con valor y moneda).

## Seguridad
- Sanitización de entradas, nonces/CSRF en formularios.
- Prepared statements en toda consulta a MySQL.
- Verificación de firma en el webhook de Mercado Pago.

## Fuera del stack (descartado explícitamente)
- WordPress (core + plugins).
- Grav CMS u otro CMS de terceros.
- Cualquier framework JS de frontend (React/Vue/etc.) o framework PHP pesado (Laravel/Symfony).
- Servidor Node en producción.
