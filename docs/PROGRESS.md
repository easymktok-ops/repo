# PROGRESS — Joyas Colombianas® Dinner & Show

Stack: `docs/STACK.md`. Tokens y diagnóstico: `docs/DESIGN-TOKENS.md`. Fotos pendientes: `docs/IMAGENES.md`. Cronograma: 15 sesiones de 1h, 28 sept a 16 oct, lanzamiento 17 oct.

## Motor de compras construido (7 oct), con pasarela simulada
- **Qué hay:** página `/comprar/` (función, entradas, datos de facturación), estado del pedido `/pago/<id>/` con el ticket numerado (`#JCD-1001…`), endpoint de avisos `/webhook/<pasarela>`, base de datos (`database/schema.mysql.sql` para Hostinger y `schema.sqlite.sql` para pruebas), pasarela simulada solo fuera de producción (`/pago-simulado/`).
- **Reglas que cumple:** precios siempre del servidor; ticket solo tras aviso de pago verificado; firma verificada; aviso repetido se ignora; si el procesamiento falla a medias el reintento sí se procesa; monto distinto al de la orden no emite ticket; un rechazo tardío no revierte una orden aprobada; `purchase` se dispara solo con pago aprobado, una vez.
- **Pruebas:** `php tests/run.php` (30 pruebas, incluida numeración continua con 14 procesos simultáneos). Corren en SQLite; **el esquema MySQL no se ha ejecutado todavía**: probarlo en el staging de Hostinger con `php tools/migrate.php`.
- **Mercado Pago:** `app/payments/MercadoPagoGateway.php` está vacío a propósito (no acepta avisos) hasta tener la documentación oficial de la API de Orders. Es lo único que cambia al integrarlo.
- **Pendiente:** correo de confirmación, panel `/admin` (login, contenido, door list, reporte contable), política de tratamiento de datos, staging con PHP y MySQL, driver real de Mercado Pago.

## Propuesta pendiente: reservas de Cartagena (7 oct)
La clienta quiere vender en Cartagena una **reserva** (no un ticket): cóctel de cortesía, ubicación preferencial y cupo asegurado. Ella solo se queda con el valor de la reserva (unos $5); el consumo lo paga el cliente en el restaurante. Cambia el alcance original (Cartagena era solo marca, sin checkout), así que requiere aprobación.
- **Diseño:** misma base de compras, con producto de tipo "reserva": numeración propia (`#CTG-1001`), cupos por fecha, lista de reservas por fecha para el restaurante, y textos que dejan claro que el consumo se paga en el restaurante.
- **Por decidir con la clienta:** moneda y valor (en COP, la comisión de Mercado Pago pesa mucho en montos tan pequeños), si es por persona o por reserva, si se devuelve o se abona, días, horarios y cupos del restaurante, acuerdo por escrito de que honran los beneficios, y cómo se factura.
- **Plan de bajo riesgo:** lanzar la landing con solicitud de reserva sin pago y activar el cobro cuando Mercado Pago esté validado.

## Estado al 2 oct (fin de la semana 1)
- **Semana 1 del cronograma: cumplida** (estructura base y home). Pendiente solo lo que depende de otros: logo/fotos/video faltantes, accesos a Hostinger.
- **Todavía NO existe** (no es un bug, está en la fase 3 del cronograma, pero conviene adelantarlo): página de compra `/comprar` (hoy es una página provisional), base de datos, motor de tickets `#JCD-1001`, panel `/admin` con login, door list, correo de confirmación y Mercado Pago.
- **Propuesta:** construir el motor de compras ya (BD + tickets + checkout + admin) con una pasarela simulada, en paralelo a las landings, para que el 12 oct solo se enchufe Mercado Pago. Necesita: versión de PHP y MySQL de Hostinger, credenciales de prueba de Mercado Pago, método de correo.

## Hecho
- **28 sept** — Auditoría del repo, decisión de plataforma: sin WordPress, PHP a medida + panel propio (sobre Grav). Stack documentado.
- **30 sept** — Estructura base + home completo (sesiones "estructura base" y "maquetación" del cronograma):
  - Front controller PHP (`public/index.php`) con rutas: `/`, `/contacto` (POST), `/sitemap.xml`, páginas provisionales noindex para `/comprar/`, `/medellin/`, `/bogota/`, `/cartagena/`, `/politica-de-datos/`, y 404.
  - Capa de contenido editable (`app/content/defaults.php`, sobrescribible por `storage/content.json`, que escribirá el panel de admin).
  - Home full SEO: title, canonical, Open Graph, robots, schema.org (PerformingGroup + Event semanal con Offers 195.000 / 180.000 COP generados desde la programación), sitemap, robots.txt.
  - Secciones en el mismo orden del sitio actual: hero, frase + marco de video, experiencia, banda, ciudades + programación 2026, ¿Qué es un Dinner Show?, banda, contacto, footer.
  - Parallax por capas ligado al scroll (CSS, fuera del hilo principal) + profundidad por puntero en el hero (escritorio), todo desactivado con `prefers-reduced-motion`.
  - Estrellas en dos capas fijas a la ventana (`.starfield`): el contenido y las tarjetas pasan por encima. La capa cercana deriva 800 px a lo largo de la página (`--star-drift` en `main.css`); las tarjetas de programación y el marco de la frase flotan ±2.25 rem (`--float`). Sin movimiento si el visitante lo pide.
  - Formulario con CSRF, honeypot, validación por campo y guardado en `storage/leads.csv` (fuera de la carpeta pública).
  - Iconos SVG propios, fuentes locales (Cinzel, Montserrat, Great Vibes de respaldo para el logo, todas OFL).
  - QA: sin errores de consola, sin desborde horizontal en 390px y 1440px, JSON-LD válido.
- Contenido migrado: textos del sitio actual (pantallazo) y de la reseña oficial en Drive. Nombre normalizado a "Joyas Colombianas® Dinner & Show". Sin mención al recinto anterior.

## Confirmar con la clienta antes de publicar
- **Número de WhatsApp** del botón (la reseña trae dos: Esteban Pardo +57 302 854 5902 y Diana López +57 300 308 3374). Hoy el botón apunta a #contacto.
- **Medellín**: el sitio actual dice sábados 8:00 PM en Hotel Marriott Medellín; la reseña de Drive dice otro día, hora y recinto (desactualizada). Se usó la del sitio actual.
- **Bogotá**: se puso Hotel Tequendama (notas de la reunión del 25 sept); el sitio actual solo dice "Funciones privadas".
- **Duración del show**: la reseña dice "una hora en dos intervalos de 45 min" (no cuadra); no se publicó.
- Dirección exacta del Marriott (para schema.org y Google My Business).
- URLs de Facebook y TripAdvisor, y el texto de la reseña de TripAdvisor del footer.
- Segundo botón del hero "Compra entradas COP": se retiró por duplicado; confirmar si era para otra moneda.
- Texto de la política de tratamiento de datos (Ley 1581).

## Bloqueado
- **Fotos pendientes**: llegaron logo, hero (baja resolución), banda grupal y foto vertical del escenario (ya integradas). Faltan 4 espacios y el video; ver `docs/IMAGENES.md`. El sitio original sigue bloqueado por la red de esta sesión.
- **Video del hero**: lo provee Norman.
- **Copy SEO de John**: llega el 6 oct. Hoy hay contenido provisional migrado y `{{META_DESCRIPTION_HOME}}`.
- **Pasarela**: Mercado Pago (confirmado en la reunión del 25 sept); validación de cobertura internacional el 13 oct.
- **Hostinger**: versión de PHP, credenciales, staging.

## Siguiente
1. Panel de administración mínimo (login + edición de `storage/content.json`: programación, precios, textos, WhatsApp).
2. Landings de campaña (semana 2): Cartagena (marca, sin checkout), Medellín y Bogotá. **Enfoque 100% conversión UX/UI, noindex, sin prioridad SEO.** El home sí queda full SEO.
3. Esquema MySQL: funciones, órdenes, tickets (`#JCD-1001…`), door list.

## Requisito nuevo (7 oct): datos para contabilidad
La clienta factura cada venta y su contadora necesita los datos. Se construye junto al checkout:
- **Datos de facturación en la compra:** tipo y número de documento (CC, CE, NIT, pasaporte), nombre o razón social, correo, teléfono, ciudad. Campos exactos por confirmar con la contadora.
- **Reporte contable en el admin (CSV/Excel, por rango de fechas):** fecha, N° de ticket, ID de pago de Mercado Pago, estado, comprador, documento, correo, función, producto, cantidad, valor, comisión de Mercado Pago, neto recibido, medio de pago. La comisión y el neto vienen del aviso de pago de Mercado Pago.
- **Por confirmar con la contadora:** software de facturación, impuesto aplicable y si ya va incluido en el precio, una factura por boleta o por compra, frecuencia del reporte.
- **Datos personales:** guardar documentos exige la política de tratamiento de datos publicada (Ley 1581).

## Fuera de alcance / cotizar aparte
- A/B testing de CRO: lo hace John después del lanzamiento (15 días), no entra en esta construcción.

## Demo de revisión
- Enlace privado (se comparte desde el menú Share de la página): https://claude.ai/artifact/SbDpSFazMnKr313jAPRUWo
- Se regenera con `php tools/build-demo.php <carpeta>` y se republica al mismo enlace en cada sesión. Es una foto estática del home: el formulario y la compra no envían.
- Staging real (PHP + base de datos): subdominio en Hostinger en cuanto lleguen las credenciales.

## Cómo correrlo en local
```
php -S 127.0.0.1:8000 -t public public/index.php
```
En Hostinger: el contenido de `public/` va a `public_html/`; `app/`, `config/` y `storage/` quedan un nivel arriba (fuera de la carpeta pública). `config/config.php` se crea en el servidor a partir de `config/config.example.php`.

## 9 oct (tarde): compra estilo WeTravel y menús
- `/comprar/`: calendario de sábados por mes, 3 menús con detalle desplegable, resumen con imagen.
- Sección "Los menús" (partial `menus`) en la home y en la landing de Medellín, con los menús vigentes.
- Pendiente de confirmar: precio del vegetariano (asumido 195.000), moneda USD/COP, código de descuento (fuera de alcance).

## 9 oct (noche): reservas de Cartagena construidas
- Producto aparte del show: `/reservar/cartagena/` (formulario), `/cartagena/` (landing de campaña, noindex), `/terminos-reservas/` (texto provisional).
- Reglas: jueves, viernes y sábado; 50 cupos por día (se bloquea el día lleno, también ante compras simultáneas); mesa de máximo 6; cover USD 5 por persona.
- Moneda: se publica en USD y se cobra en COP con la TRM de referencia `checkout.usd_cop_rate` (PROVISIONAL en 4.000; Mercado Pago Colombia cobra en COP). El voucher muestra COP cobrado, USD y la tasa.
- Voucher `#CTG-5001…` (contador propio, no mezcla con `#JCD-`) con los campos que pidió la clienta para facturar: nombre, apellido, identificación con tipo (pasaporte/cédula/extranjería), email, fecha, personas, precio total recibido.
- Casilla obligatoria: el cover no se devuelve. Se guarda `kind`, `usd_total` y `fx_rate` por orden para el reporte de la contadora.
- Pruebas: 42 correctas (cupos, mesa de 6, fechas, documento, numeración separada).
- Falta: hora/dirección, correo de la lista de reservas, exportación CSV de reservas (va con el panel), correo transaccional con el voucher, revisión legal de los términos.

## 9 oct (noche): panel de administración
- `/admin/`: ingreso con usuario y clave (hash en `config.php`; en producción no funciona sin `ADMIN_PASSWORD_HASH`; en desarrollo la clave de práctica es `admin-dev`). Bloqueo tras 5 intentos fallidos en 15 minutos, token CSRF, sesión regenerada, `noindex`.
- Resumen por fecha (pedidos, personas, cupos libres, recaudo), listado de pedidos con filtros, edición de WhatsApp/redes/reseña.
- CSV con BOM y `;` (abre directo en Excel): lista de la puerta, lista de reservas de Cartagena (campos de facturación), reporte de la contadora (cobrado, comisión, neto, USD y TRM). Texto protegido contra fórmulas.
- Pruebas: 45 correctas.
