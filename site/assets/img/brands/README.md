# Logos de marcas / clientes del carrusel de la home (`#marcas`)

El carrusel espera estos archivos. Si falta uno, muestra el nombre en texto
(fallback), nunca un logo falso.

## Estado actual — completo ✅
- `getnet.svg`   → Getnet (recoloreado a oscuro desde la versión blanca original).
- `bait.png`     → Bait (PNG transparente, a color).
- `framesi.svg`  → Framesi (wordmark horizontal, recoloreado a oscuro desde la versión blanca).

El carrusel aplica escala de grises y coloriza al hover (desktop). En reduced-motion
el marquee se detiene y los logos se centran.

## Para agregar/actualizar una marca
1. Sube el archivo (SVG preferido; si no, PNG con fondo transparente, buena resolución;
   nunca EPS: los navegadores no lo muestran). Evita versiones "en recuadro/badge".
2. Nómbralo en minúsculas: `<marca>.svg` o `<marca>.png`.
3. Si es blanco (para fondo oscuro), se recolorea a `#141A33` para que se vea en la tira clara.
4. Agrega un bloque `.brand-logo` en el grupo visible y su copia en el grupo `data-clone`
   de `index.html` (el segundo grupo es solo para el loop continuo).
