# GO-LIVE · Corte a producción (aerodiverti.com.mx)

Runbook para pasar el sitio de `beta.aerodiverti.com.mx` a producción en
`aerodiverti.com.mx`. Canónico elegido: **`https://aerodiverti.com.mx`** (sin www).

Regla de oro: el correo del dominio vive en el mismo Webempresa. **Nunca toques
los registros `MX` ni SPF/DKIM/DMARC** al hacer el corte. Solo se agrega/mueve
la web (registro `A`/alias) y los TXT de verificacion si hicieran falta.

---

## 0. Antes de empezar (ten a la mano)

- Llaves **LIVE** de Stripe: `sk_live_...` y el `whsec_...` del webhook live.
- Acceso a GitHub (OAuth App "Aerodiverti CMS").
- Acceso a wePanel (Webempresa) y a Google Search Console.
- Un `cron_token` nuevo aleatorio (para rotar el actual).

## 1. Encender el dominio en Webempresa

1. wePanel -> **Dominios** -> agrega `aerodiverti.com.mx` (y `www`) como
   dominio/alias apuntando a **`public_html`** (misma carpeta del sitio nuevo).
2. wePanel -> **Editor de Zona DNS** de `aerodiverti.com.mx`: confirma que
   `A @` y `www` apunten al hosting y que **los `MX` sigan intactos**.
3. wePanel -> **SSL / Let's Encrypt**: emite certificado para
   `aerodiverti.com.mx` y `www.aerodiverti.com.mx`.

> Como el sitio nuevo ya vive en `public_html`, apuntar el dominio ahi ES el
> "encendido". No hay un "apagado" separado: al servir `public_html`, el sitio
> viejo de `.com.mx` queda reemplazado por el nuevo.

## 2. Reconstruir y subir el sitio (con la URL de produccion)

1. Build con la URL final:
   `PUBLIC_SITE_URL=https://aerodiverti.com.mx npm run build`
   (Antes, en `public/admin/config.yml` cambia `base_url` a
   `https://aerodiverti.com.mx` para que el /admin quede correcto.)
2. Empaqueta `dist/` en ZIP y subelo a `public_html` (extraer, sobrescribir).
3. Purga la Magic Cache.

## 3. config.php de produccion

En `public_html/server/config.php`:

```php
'site_url' => 'https://aerodiverti.com.mx',
...
'stripe_secret_key'     => 'sk_live_...(LIVE)',
'stripe_webhook_secret' => 'whsec_...(del webhook LIVE, paso 4)',
...
'allowed_origins' => [
    'https://aerodiverti.com.mx',
],
...
'cron_token' => 'NUEVO_TOKEN_ALEATORIO',
```

El bloque `notifications` (SMTP con `Reservas@aerodiverti.com.mx`) se queda igual.

## 4. Externos: cambiar TODO lo que dice "beta"

Estos son los unicos puntos con dominio (no hay URLs hardcodeadas en el codigo):

| # | Donde | Cambiar a |
|---|---|---|
| 1 | Build `PUBLIC_SITE_URL` | `https://aerodiverti.com.mx` |
| 2 | `config.php` `site_url` | `https://aerodiverti.com.mx` |
| 3 | `config.php` `allowed_origins` | `https://aerodiverti.com.mx` |
| 4 | `config.yml` `base_url` (se reconstruye) | `https://aerodiverti.com.mx` |
| 5 | GitHub OAuth App -> Authorization callback URL | `https://aerodiverti.com.mx/server/public/api/oauth/callback.php` |
| 6 | Stripe (LIVE) -> Webhook endpoint | `https://aerodiverti.com.mx/server/public/api/webhook.php` |
| 7 | Cron job (wget) | `https://aerodiverti.com.mx/server/cron/process-outbox.php?token=NUEVO_TOKEN` |

**Stripe (tu duda):** si, es "regresar" a live. Apaga el Modo de prueba, copia
`sk_live_`, crea un webhook LIVE apuntando al endpoint #6 con el evento
`checkout.session.completed`, copia su `whsec_` live -> ambos al `config.php`.

## 5. SEO

1. Alta/propiedad de `https://aerodiverti.com.mx` en **Google Search Console**.
2. Envia el sitemap: `https://aerodiverti.com.mx/sitemap-index.xml`.
3. Verifica que las paginas NO tengan `noindex` (las transaccionales /reserva
   si lo llevan, es correcto). El canonical ya apunta al dominio nuevo por el
   build.
4. Reemplaza en Google Business Profile / Maps la URL por `aerodiverti.com.mx`.

## 6. Redirecciones 301 (consolidar SEO de los dominios viejos)

Nota: 301 (permanente), no 304. Objetivo: todo apunta al canonico
`aerodiverti.com.mx`.

### a) www -> sin www + /api (agrega al `.htaccess` de `public_html`)

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On

  # /api/ -> backend (ya existente, NO lo quites)
  RewriteCond %{REQUEST_URI} ^/api/
  RewriteRule ^api/(.*)$ server/public/api/$1 [L]

  # www -> sin www (canonico)
  RewriteCond %{HTTP_HOST} ^www\.aerodiverti\.com\.mx$ [NC]
  RewriteRule ^(.*)$ https://aerodiverti.com.mx/$1 [R=301,L]
</IfModule>
```

### b) `aerodiverti.com` (Jimdo, se queda por el correo)

No se puede 301 a nivel servidor (Jimdo controla su DNS). Opciones, en orden:
1. En Jimdo, si hay opcion de **redireccion del dominio** -> apunta a
   `https://aerodiverti.com.mx` (301). Ideal.
2. Si no, **despublica** el sitio de Jimdo (deja de competir). El correo NO se
   toca (sigue en Jimdo).

### c) `aerodiverti.mx` (si aun lo controlas y quieres su SEO)

Si puedes apuntar su DNS a Webempresa: agregalo como dominio adicional a
`public_html` y añade al `.htaccess`:

```apache
  RewriteCond %{HTTP_HOST} ^(www\.)?aerodiverti\.mx$ [NC]
  RewriteRule ^(.*)$ https://aerodiverti.com.mx/$1 [R=301,L]
```

Si no lo controlas o no vale la pena, dejalo caido (no daña).

## 7. Verificacion post-corte (smoke test)

- [ ] `https://aerodiverti.com.mx` carga con candado (SSL).
- [ ] `www` redirige a sin www (301).
- [ ] Reserva real chica en LIVE -> pago OK -> reembolsala en Stripe.
- [ ] Llega la confirmacion al cliente (rapido, por el cron) + aviso a ventas.
- [ ] Aparece en el panel de ventas.
- [ ] `/admin` entra con GitHub (callback nuevo).
- [ ] `aerodiverti.com` y `.mx` redirigen (o el .com despublicado).

## 8. Plan de reversa

Antes del corte, baja el TTL del DNS a 600. Si algo sale mal, revierte el
apuntamiento del dominio o restaura el sitio anterior. El correo no se ve
afectado en ningun caso porque nunca se tocan los `MX`.

## 9. Higiene de seguridad (hacer en el corte)

- Rotar `cron_token` (ya arriba) y el password del panel de ventas.
- Confirmar que el `github_client_secret` es uno vigente (rotar si se expuso).
- Borrar cualquier archivo de diagnostico que quede en el servidor.
