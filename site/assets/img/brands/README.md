# Logos de marcas / clientes del carrusel de la home (`#marcas`)

El carrusel espera estos archivos. Si falta uno, muestra el **nombre en texto**
(fallback), nunca un logo falso. Al soltar el archivo correcto, el logo aparece.

## Estado actual
- `getnet.svg`  → ✅ LISTO. (Se recoloreó a oscuro desde la versión blanca original,
  para que se vea sobre el fondo claro del carrusel.)
- `bait.svg`    → ⛔ FALTA. El archivo enviado era `.eps` (5.8 MB), formato que los
  navegadores NO pueden mostrar. Sube `bait.svg` o `bait.png` (fondo transparente).
- `framesi.svg` → ⛔ FALTA. El archivo enviado era un **badge oscuro** (el logo dentro
  de un cuadro sólido, para fondos oscuros); a tamaño de carrusel se ve como una caja.
  Sube la versión **horizontal del logo con fondo transparente** (sin el cuadro).

## Requisitos
- SVG preferido; si no, PNG con fondo transparente, buena resolución.
- Idealmente el logo "suelto" (sin recuadro de fondo), en oscuro o a color.
  El carrusel aplica escala de grises y coloriza al hover.
- Nombres en minúsculas y exactos: `bait.svg`, `getnet.svg`, `framesi.svg`
  (o `.png` si es el caso; avísame para ajustar la referencia).
- Para agregar más marcas: duplica un bloque `.brand-logo` en el grupo visible y su
  copia en el grupo `data-clone` de `index.html`.
