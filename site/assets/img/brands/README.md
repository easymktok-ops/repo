# Logos de marcas / clientes del carrusel de la home

El carrusel de la home (`index.html`, sección `#marcas`) espera estos archivos.
Mientras no existan, el carrusel muestra el **nombre de la marca en texto** (fallback),
no un logo falso. Al soltar el archivo real con el nombre exacto, el logo reemplaza al texto.

Nombres esperados (SVG preferido; si no, PNG con fondo transparente):
- `bait.svg`      → Bait (telefonía celular de Walmart México · mibait.com)
- `getnet.svg`    → Getnet (terminales de pago de Santander · getnet.mx)
- `framesi.svg`   → Framesi (productos profesionales de salón · framesimexico.com.mx)

Requisitos:
- Usa la versión oficial del logo (brand kit / sitio oficial), buena resolución.
- Fondo transparente. El carrusel aplica escala de grises y coloriza al hover, así que
  el logo debe verse bien en monocromo.
- Alto de referencia ~44px; el ancho se ajusta solo (los `width/height` del `<img>` son
  solo hint de proporción, puedes ajustarlos al logo real).
- Para agregar MÁS marcas: duplica un bloque `.brand-logo` en el grupo visible y su copia
  en el grupo `data-clone` (el segundo grupo es solo para el loop continuo).
