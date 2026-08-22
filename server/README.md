# Backend de reservas (Fase 3) — Aerodiverti

Backend de pago con **Stripe** + **outbox de notificaciones**, en **PHP sin
dependencias** (cURL nativo, sin Composer), pensado para el hosting compartido
del cliente en **Webempresa** (PHP + MySQL + Cron).

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
En wePanel crea una BD MySQL + usuario. Importa `server/sql/schema.sql` desde
PhpMyAdmin. Anota host/nombre/usuario/clave.

### 5. Configuración
Copia `config.example.php` a `config.php` y rellena:
- `site_url` (dominio del cliente, sin barra final)
- `stripe_secret_key` (empieza con `sk_test_`, luego `sk_live_`)
- `stripe_webhook_secret` (paso 7)
- `catalog_path` (ruta absoluta al `catalog.json` subido, p. ej.
  `/home/USUARIO/www/tu-dominio/data/catalog.json`)
- credenciales `db`
- `notifications.admin_email` (a dónde llegan las alertas de reserva)

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
3. Verifica: fila `paid` en `bookings`, filas `sent` en `notifications_outbox`
   (con `email_provider = "mail"` llega el correo; con `"log"` solo queda traza).

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
