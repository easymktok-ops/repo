#!/usr/bin/env bash
# Arma dist-aerodiverti-pruebas.zip para el ENTORNO DE PRUEBAS (subdominio).
# Uso: bash server/dev/pruebas/armar.sh [URL] [--actualizar]
#   --actualizar: sin server/config.php (no pisa el que ya se lleno en el hosting).
# - Compila el sitio SIN analitica (ningun ID de GA/Ads/Meta) y SIN /admin
#   (el CMS escribe en la rama de produccion: no debe existir en pruebas).
# - Agrega el backend PHP, .htaccess de pruebas (noindex, /api, carpetas
#   internas bloqueadas) y un config.php de pruebas con 3 valores por llenar.
set -euo pipefail
URL="https://pruebas.aerodiverti.com.mx"
ACTUALIZAR=0
for a in "$@"; do
  case "$a" in
    --actualizar) ACTUALIZAR=1 ;;
    *) URL="$a" ;;
  esac
done
ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
OUT="$(mktemp -d)"
cd "$ROOT"
PUBLIC_SITE_URL="$URL" PUBLIC_GTM_ID= PUBLIC_GA_ID= PUBLIC_GOOGLE_ADS_ID= PUBLIC_META_PIXEL_ID= \
  PUBLIC_PLAUSIBLE_DOMAIN= npx astro build --outDir "$OUT/site"
cd "$OUT/site"
rm -rf admin favicon-admin.svg collections content-assets.mjs content-modules.mjs
printf '# Entorno de pruebas: no indexar.\nUser-agent: *\nDisallow: /\n' > robots.txt
if grep -rqE "GTM-|G-DD2R37C1V9|141551953168954|AW-18320918955" . ; then
  echo "ERROR: el build de pruebas trae IDs de analitica" >&2; exit 1
fi
mkdir -p server/data server/lib server/public/api
cp "$ROOT"/server/lib/*.php server/lib/
cp "$ROOT"/server/public/api/{create-checkout-session,panel,prices,webhook}.php server/public/api/
cp "$ROOT"/server/config.example.php server/
cp "$ROOT"/server/dev/pruebas/htaccess .htaccess
cp "$ROOT"/server/dev/pruebas/htaccess-denegar server/lib/.htaccess
cp "$ROOT"/server/dev/pruebas/htaccess-denegar server/data/.htaccess
if [ "$ACTUALIZAR" = 0 ]; then
  sed "s#https://pruebas.aerodiverti.com.mx#$URL#" "$ROOT"/server/dev/pruebas/config.php > server/config.php
fi
rm -f "$ROOT/dist-aerodiverti-pruebas.zip"
zip -qr -X "$ROOT/dist-aerodiverti-pruebas.zip" .
rm -rf "$OUT"
echo "Listo: $ROOT/dist-aerodiverti-pruebas.zip"
