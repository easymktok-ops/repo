# Backend de reservas (Fase 3) — Aerodiverti

Backend de pago con **Stripe** + **outbox de notificaciones**, en **PHP sin
dependencias** (cURL nativo, sin Composer), pensado para el hosting compartido
del cliente en **Webempresa** (PHP + Cron; almacenamiento en SQLite, sin MySQL).

El sitio (Astro estático) y este backend conviven en el mismo host: el front
llama a `/api/create-checkout-session.php` (mismo origen), Stripe cobra en su
página, y el `webhook.php` confirma el pago y encola las notificaciones que un
**Cron** envía con reintentos.

> Modelo de cobro (bases del sitio .mx): el checkout ofrece **pagar todo** o
> **apartar lugares** con un **anticipo de $1,000 MXN por pasajero**; el saldo
> se liquida **en sitio** el día del vuelo. El precio de cada paquete es **por
> persona**. El monto SIEMPRE se recalcula en el servidor (`catalog.json`); el
> cliente nunca envía precios.

---

## Estructura

```
server/
  config.example.php      -> copiar a config.php y rellenar (NO se versiona)
  lib/                    -> bootstrap, db, stripe, pricing, outbox, canales, plantillas
  public/api/
    create-checkout-session.php
    webhook.php
  cron/process-outbox.php -> worker del outbox (Cron cada minuto)
  sql/schema.sql          -> tablas bookings + notifications_outbox
  data/catalog.json       -> autoridad de precios (la genera el build de Astro)
```

## Despliegue en Webempresa (paso a paso)

### 1. Limpiar el WordPress temporal
En wePanel elimina la instalación WordPress de demo (WPCenter → Eliminar
WordPress) y su base de datos. Libera disco y una de las 2 BD permitidas.
Haz antes un SuperBackup por si acaso.

### 2. Subir el sitio estático
Compila el sitio y sube el contenido de `dist/` a la web root (`public_html`
o `www/tu-dominio`):

```bash
PUBLIC_SITE_URL="https://tu-dominio" npm run build
```

El build genera `dist/data/catalog.json` (autoridad de precios).

### 3. Subir el backend PHP
Sube la carpeta `server/` al host. Recomendado: `server/` **fuera** de la web
root, y expón solo `server/public/api/` como `/api` (alias o symlink), para que
`lib/`, `cron/` y `config.php` no sean accesibles por web. Si tu plan no permite
eso, sube `server/public/api/*.php` a `public_html/api/` y ajusta los `require`
(o deja `server/` completo y protégelo con `.htaccess`).

### 4. Base de datos
Por defecto usa **SQLite**: un archivo (`server/data/aerodiverti.sqlite`) que se
crea SOLO en el primer arranque, con su esquema. **No hay que crear ninguna base
MySQL, usuario ni correr SQL a mano.** Solo asegúrate de que la carpeta
`server/data/` sea escribible (permiso 755, normal al subir por el panel).

Si tu hosting no trae SQLite (raro), pasa a MySQL: en `config.php` cambia
`db.driver` a `mysql`, rellena host/nombre/usuario/clave, y corre
`server/sql/schema.sql` en esa base (queda como referencia MySQL).

### 5. Configuración
Copia `config.example.php` a `config.php` y rellena:
- `site_url` (dominio del cliente, sin barra final)
- `stripe_secret_key` (empieza con `sk_test_`, luego `sk_live_`)
- `stripe_webhook_secret` (paso 7)
- `catalog_path` (ruta absoluta al `catalog.json`; por defecto ya apunta a
  `server/data/catalog.json`, que viene incluido)
- `notifications.admin_email` (a dónde llegan las alertas de reserva)

Con SQLite (por defecto) no hay credenciales de base que rellenar.
`config.php` está en `.gitignore`: nunca se sube al repo.

### 6. Cron del outbox
En Herramientas → Tareas de Cron, cada minuto:

```
* * * * *  /usr/bin/php /home/USUARIO/.../server/cron/process-outbox.php
```

(o vía URL con `?token=` si tu Cron solo acepta wget; ver el encabezado del
worker).

### 7. Webhook de Stripe
En el dashboard de Stripe → Desarrolladores → Webhooks → “Añadir endpoint”:
- URL: `https://tu-dominio/api/webhook.php`
- Evento: `checkout.session.completed`
- Copia el **signing secret** (`whsec_...`) a `stripe_webhook_secret`.

## Pruebas (modo test)
1. `config.php` con llaves `sk_test_` y el `whsec_` del endpoint de prueba.
2. Reserva desde `/reservar`. Usa la tarjeta de prueba `4242 4242 4242 4242`,
   cualquier fecha futura y CVC.
3. Verifica en la base SQLite (o con un pequeño visor): fila `paid` en
   `bookings`, filas `sent` en `notifications_outbox`
   (con `email_provider = "mail"` llega el correo; con `"log"` solo queda traza).

## Panel de ventas (Fase 4, paso 1)

`server/public/api/panel.php` es una pantalla protegida para que el negocio vea
y gestione las reservas (parecido a los "Pedidos" de una tienda). Un solo
archivo, sin dependencias, se sube junto a los demás endpoints.

- **URL:** `https://tu-dominio/api/panel.php`
- **Login:** usuario y contraseña definidos en `config.php` → bloque `panel`
  (`user`, `password`). La contraseña vive solo en el servidor. Opción segura:
  usar `password_hash` (bcrypt) en vez de `password` en texto.
- **Hace:** KPIs (reservas pagadas, cobrado en línea, saldo por cobrar en sitio,
  vuelos próximos), lista con filtros/buscador/paginación, detalle de cada
  reserva con acciones (marcar **saldo pagado en sitio**, **vuelo completado**,
  **cancelar/reactivar**, notas internas) y **exportar a CSV**.
- **Base de datos:** lee la misma SQLite de las reservas. Al primer arranque
  agrega solas las columnas administrativas que necesita (idempotente).
- **Deploy de una actualización:** basta subir `panel.php` a `public_html/api/`
  (o donde vivan los endpoints) y, si cambió `lib/schema.php`, subir también ese
  archivo. Añade el bloque `panel` a tu `config.php`.
- **Seguridad:** sesión con cookie httponly, token CSRF en cada acción, salida
  escapada, `noindex`. Aun así, el panel expone datos de clientes: usa una
  contraseña fuerte y siempre por HTTPS.

## Producción
Cambia a llaves `sk_live_` y al `whsec_` del endpoint en vivo, y `site_url` al
dominio final. Nada de código cambia entre entornos: solo `config.php`.

## Notas de seguridad
- La `stripe_secret_key` vive solo en el servidor. El front no la necesita: el
  pago usa la URL que devuelve el servidor (sin Stripe.js), cero JS de terceros.
- El webhook valida la firma HMAC (`stripe_verify_webhook`) con tolerancia de
  tiempo (anti-replay).
- El monto se recalcula server-side desde `catalog.json`; manipular el front no
  cambia el cobro.
