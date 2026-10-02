# Despliegue automático (Fase 5)

Cómo el sitio se reconstruye y publica solo cuando cambia el contenido.

## Qué hace

`.github/workflows/deploy.yml` corre en cada push a `main` (incluidos los commits
que hace el panel de contenido `/admin` al **Publicar**). Reconstruye el sitio
estático (`npm run build` → `dist/`) y lo sube por **FTPS** al hosting.

**Salvaguarda:** el workflow **nunca** toca `server/**`, `data/**`, `.htaccess`
ni `.well-known/**`. Ahí viven el backend PHP y la **base de reservas** (SQLite).
Solo se publican los archivos del sitio estático.

## Lo que debes configurar una sola vez (en GitHub)

Repositorio → **Settings → Secrets and variables → Actions**.

### Secrets (privados)

| Secret | Qué es | De dónde sale |
| --- | --- | --- |
| `FTP_SERVER` | Host FTP del hosting (p.ej. `ftp.tudominio.com` o la IP) | wePanel → cuentas FTP |
| `FTP_USERNAME` | Usuario FTP | wePanel → cuentas FTP |
| `FTP_PASSWORD` | Contraseña de ese usuario FTP | wePanel → cuentas FTP |

### Variables (no secretas)

| Variable | Valor | Nota |
| --- | --- | --- |
| `PUBLIC_SITE_URL` | URL del entorno destino | Beta operativa: `https://beta.aerodiverti.mx` · Producción final: la URL real (`https://aerodiverti.mx`) |
| `FTP_SERVER_DIR` | Carpeta raíz del sitio | Normalmente `public_html/` (confírmalo con la ruta que ves en el Administrador de archivos) |

## Recomendación: probar primero en STAGING

Antes de apuntar a producción, prueba el pipeline contra el sitio de prueba:
pon en `PUBLIC_SITE_URL` y en las credenciales FTP los del staging, lanza el
workflow a mano (**Actions → Deploy sitio → Run workflow**) y verifica que el
sitio se actualiza y que **el panel y las reservas siguen intactos**. Recién
entonces se cambia a las credenciales/URL de producción.

## Panel de contenido `/admin` en vivo (OAuth de GitHub)

Para que el negocio edite contenido desde `/admin` en el sitio real (no solo en
dev), el login usa un proxy OAuth propio en PHP (`server/public/api/oauth/`).
Configuración de una sola vez:

1. **Ruta limpia `/api/`.** Copia el contenido de
   `server/deploy/public_html.htaccess.example` a un archivo `.htaccess` en la
   raíz `public_html/`. Deja los endpoints en `/api/...` (panel, checkout,
   webhook, oauth).
2. **GitHub OAuth App.** GitHub → Settings → Developer settings → OAuth Apps →
   New. En *Authorization callback URL* pon
   `https://TU-DOMINIO/api/oauth/callback.php`. Copia el **Client ID** y genera
   un **Client Secret**.
3. **Credenciales en el servidor.** En `server/config.php`, bloque `oauth`, pega
   `github_client_id` y `github_client_secret`. Viven solo en el servidor.
4. **Dominio en el CMS.** En `public/admin/config.yml`, cambia
   `base_url: https://REEMPLAZA-CON-TU-DOMINIO` por el dominio real (staging o
   producción). Es el único valor dependiente del dominio.
5. Abre `https://TU-DOMINIO/admin`, entra con GitHub y edita. Al **Publicar**,
   Decap hace commit a `main` y el pipeline de arriba reconstruye y despliega.

> Para probar en **staging** primero, usa la URL del staging en el paso 2 (callback),
> en el paso 4 (base_url) y en `PUBLIC_SITE_URL`.

## Tarifas por fecha (backend PHP: se sube a mano)

El pipeline solo publica `dist/` (la parte estática). **El backend PHP no se despliega solo**: los archivos de
`server/` se suben por el Administrador de archivos o FTP a `public_html/server/`. Nunca subir `server/dev/`
ni `server/tests/`, ni sobrescribir `server/config.php` ni `server/data/`.

Archivos de tarifas: `lib/pricing_rules.php`, `lib/pricing_store.php`, `lib/panel_auth.php`,
`lib/panel_pricing.php`, `public/api/prices.php`, más los modificados `lib/pricing.php`, `lib/schema.php`,
`public/api/panel.php`, `public/api/create-checkout-session.php` y `public/api/webhook.php`.

**Bandera de reversa** en `server/config.php`:

```php
'pricing' => [
    'rules_enabled'    => false, // true = precios por fecha; false = cobro de siempre
    'max_advance_days' => 365,
    'cache_seconds'    => 60,
],
```

- Se cambia editando `config.php`, sin redeploy. Sin el bloque, cuenta como `false`.
- **OPcache:** PHP puede seguir usando el `config.php` anterior unos segundos o minutos. Después de cambiar
  la bandera, espera y comprueba:
  `curl "https://aerodiverti.com.mx/api/prices.php?packageId=vuelo-compartido&from=AAAA-MM-DD&to=AAAA-MM-DD"`
  (`{"enabled":false}` = apagada). La respuesta pública se cachea `cache_seconds` (60 s).
- Un paquete sin % de anticipo (panel → Precios) se cobra como siempre aunque la bandera esté encendida.
- Antes de subir cambios del backend: respaldar `server/data/aerodiverti.sqlite`.
