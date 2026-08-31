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
| `PUBLIC_SITE_URL` | URL del entorno destino | Staging: `https://aerodive-cp5027.wordpresstemporal.com` · Producción: la URL real |
| `FTP_SERVER_DIR` | Carpeta raíz del sitio | Normalmente `public_html/` (confírmalo con la ruta que ves en el Administrador de archivos) |

## Recomendación: probar primero en STAGING

Antes de apuntar a producción, prueba el pipeline contra el sitio de prueba:
pon en `PUBLIC_SITE_URL` y en las credenciales FTP los del staging, lanza el
workflow a mano (**Actions → Deploy sitio → Run workflow**) y verifica que el
sitio se actualiza y que **el panel y las reservas siguen intactos**. Recién
entonces se cambia a las credenciales/URL de producción.

## Pendiente en esta fase (siguiente paso)

Login del panel de contenido en el servidor (proxy OAuth de GitHub), para que el
negocio pueda editar desde `/admin` en vivo sin depender de dev. Se documenta al
implementarlo.
