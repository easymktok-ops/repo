# Entorno de pruebas: pruebas.aerodiverti.com.mx

Copia aislada del sitio para probar las tarifas por fecha sin tocar producción:

- Su propia base de datos (`server/data/pruebas.sqlite`): no ve ni toca las reservas reales.
- Stripe en **modo prueba**: no se cobra dinero real.
- **Sin analítica** (sin Google Analytics, Ads ni Meta Pixel) y **sin `/admin`**. El CMS escribe en la rama de
  producción y no debe existir aquí.
- Sin correos ni WhatsApp: las notificaciones solo quedan registradas.
- Google no lo indexa (`robots.txt` y cabecera `X-Robots-Tag: noindex`).
- Las carpetas internas del backend (`server/lib`, `server/data`, `server/config.php`) están bloqueadas por web.
- Precios por fecha **encendidos**.

## Armar el paquete

```bash
bash server/dev/pruebas/armar.sh                       # usa https://pruebas.aerodiverti.com.mx
bash server/dev/pruebas/armar.sh https://otro.dominio  # si el subdominio es otro
bash server/dev/pruebas/armar.sh --actualizar          # actualización: sin config.php
```

Genera `dist-aerodiverti-pruebas.zip` (no se versiona). Plantillas en `server/dev/pruebas/`.

## Montarlo en Webempresa (una sola vez)

1. **Subdominio:** panel de Webempresa → Dominios → Subdominios → crear `pruebas` para `aerodiverti.com.mx`.
   Carpeta raíz: Webempresa la pone dentro de `public_html` (`public_html/pruebas.aerodiverti.com.mx`). El
   `.htaccess` de pruebas solo responde al nombre del subdominio (desde `aerodiverti.com.mx/carpeta` da 404) y
   el deploy de producción no borra nada. Sin cuenta FTP adicional.
   **No cambiar los nameservers del dominio** (ver `CLAUDE.md`, sección DNS).
2. **PHP 8.3** para el subdominio (Seleccionar versión de PHP), con `pdo_sqlite` y `mbstring`.
3. **SSL:** activar el certificado (Let's Encrypt / AutoSSL) del subdominio.
4. **Subir** `dist-aerodiverti-pruebas.zip` a la carpeta raíz del subdominio → Extraer → borrar el ZIP.
5. **Stripe (modo prueba)** → Desarrolladores → Webhooks → agregar destino:
   `https://pruebas.aerodiverti.com.mx/api/webhook.php`, evento `checkout.session.completed` → copiar el
   secreto de firma (`whsec_…`).
6. **Editar `server/config.php`** del subdominio: las 3 líneas marcadas `<<< CAMBIA AQUI` (llave de prueba de
   Stripe, `whsec_` del paso 5 y la contraseña del panel).

## Comprobar

- `https://pruebas.aerodiverti.com.mx/reservar` abre el sitio.
- `https://pruebas.aerodiverti.com.mx/server/config.php` y `/server/data/` → **403 Prohibido**.
- `https://pruebas.aerodiverti.com.mx/api/panel.php` → login del panel; pestaña **Precios**.
- Cargar un % de anticipo en **Precios** y ver el calendario en `/reservar`.
- Pago de prueba con la tarjeta `4242 4242 4242 4242`, cualquier fecha futura y CVC: la reserva aparece pagada en
  el panel de pruebas y en Stripe en modo prueba.

## Actualizar

Correr `armar.sh --actualizar` (el ZIP no trae `config.php`, así que no pisa el que ya está lleno) y subirlo.
Por si acaso, respaldar antes `server/config.php` y `server/data/` del subdominio.
