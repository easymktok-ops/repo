# Kosecha — sitio web

Landing de una sola página para **Kosecha**, comida sana en Libres, Puebla.
Objetivo único: que el visitante elija un platillo y cierre el pedido por
WhatsApp. No hay carrito, checkout ni pasarela de pago.

## Arrancar

```bash
npm install
npm run dev        # http://localhost:5173
npm run build      # dist/
npm run preview
```

| Comando | Qué hace |
| --- | --- |
| `npm run dev` | Servidor de desarrollo |
| `npm run build` | Build de producción en `dist/` |
| `npm run lint` | oxlint |
| `npm run recortar` | Recorta las fotos de `assets-origen/platillos/` sobre su fondo blanco y genera webp de 480 y 900px con PNG de respaldo |
| `npm run optimizar` | Genera los `.webp` de `public/assets/` y avisa si una foto no tiene fondo transparente |
| `npm run fuentes` | Vuelve a bajar Poppins de Google Fonts a `public/fonts/` para auto-hospedarla |
| `npm run seo` | Regenera el JSON-LD, el sitemap y el robots desde `src/data/` (corre solo antes de cada build) |
| `npm run exportar` | Arma `export/kosecha-sitio.zip`, el paquete para subir a mano a un hosting |
| `npm run verificar` | Chequeo de humo en un navegador real (rueda, CTAs, teclado, reduced-motion) |
| `npm run auditar` | Contraste medido de cada texto, blancos de click de 44px y desborde horizontal de 320 a 1920 |

Los dos últimos necesitan el dev server en el puerto 5179 (`npx vite --port 5179`)
y el chromium de Playwright (`npx playwright install chromium`).

## Cómo está armado

```
PRODUCT.md          registro, usuarios, personalidad, anti-referencias
DESIGN.md           sistema visual: color, tipografía, componentes, motion
brand/
  BRAND.md          manual reconstruido desde el moodboard
  tokens.css        variables CSS (color, tipografía, espaciado, motion)
src/
  data/platillos.js menú completo: fuente única de platillos y precios
  data/contenido.js copy verbatim de marca + datos de contacto
  lib/whatsapp.js   el número vive acá y en ningún otro lado
  lib/analytics.js  evento pedido_whatsapp
  components/       Nav, Hero, RuedaMenu, PanelPlatillo, MenuCompleto, …
assets-origen/      fotos originales sin tocar, como llegaron de la marca
public/assets/      logo/ platillos/ fotos/   (generadas, ver ASSETS-TODO.md)
public/fonts/       Poppins auto-hospedada
reference/          el video de la referencia de interacción
```

### Las fotos

Las de origen son tomas cenitales sobre fondo blanco. `npm run recortar` las
deja con fondo transparente: el plato también es blanco, así que no alcanza con
borrar lo blanco ni con inundar desde los bordes (se filtra por la sombra y se
come el plato). Para los platos ajusta un círculo a su filo por mínimos
cuadrados; para el wrap y el sándwich, que no son redondos, inunda desde los
bordes con umbral de blanco puro. Si cambian las fotos, se vuelve a correr y
listo.

### La rueda de menú

`src/components/RuedaMenu.jsx`. Un único contenedor rota con
`transform: rotate()`; cada platillo se ubica con
`rotate(i · paso) translateY(-radio)` y **contrarrota** el mismo ángulo para no
quedar nunca de cabeza. El ángulo se acumula (nunca se le aplica módulo 360),
así siempre gira por el camino más corto. Soporta click, arrastre con snap al
platillo más cercano, flechas del teclado, Enter sobre el platillo activo para
abrir WhatsApp, y respeta `prefers-reduced-motion`.

Duración y easing salen de `--kos-dur-rueda` y `--kos-ease-rueda` en
`brand/tokens.css` — es lo que hay que ajustar contra el video de referencia.

### El CTA de WhatsApp

Cada platillo genera su propio enlace con el pedido ya escrito
(`src/lib/whatsapp.js`). El CTA vive dentro del panel del platillo activo y
cambia de destino con cada rotación. El número se puede pisar sin tocar código
con la variable de entorno `VITE_WHATSAPP_NUMERO` (ver `.env.example`).

## SEO local

El negocio vive de que lo encuentren en Libres, así que los datos estructurados
se generan desde `src/data/` en vez de escribirse a mano en el head: el menú,
los precios y el teléfono ya viven ahí, y un schema que miente sobre el precio
es peor que no tener schema.

`npm run seo` escribe en `index.html` un JSON-LD con:

- **Restaurant**: nombre, teléfono, coordenadas, mapa, Instagram, zona de
  cobertura, y el menú completo como `Menu` → `MenuSection` → `MenuItem` con
  precio en MXN.
- **FAQPage**: las mismas preguntas que se ven en la página (Google solo acepta
  el schema si el contenido está visible).
- **WebPage**.

Y genera `public/sitemap.xml` y `public/robots.txt`.

Lo que no está confirmado no se declara: sin horarios no hay
`openingHoursSpecification`, sin dirección en texto el schema lleva localidad y
coordenadas. Ver `ASSETS-TODO.md`.

## Accesibilidad

WCAG 2.1 AA. Los acentos de marca no llegan a 4.5:1 sobre crema, así que para
texto y botones se usan `--kos-naranja-hondo` y `--kos-lima-hondo`; los acentos
originales solo pintan superficies. `npm run auditar` mide esto en el navegador
y tiene que dar cero en las tres métricas.

## Subirlo a un hosting

`npm run exportar` deja un ZIP cuyo contenido va directo a `public_html/`. Es un
sitio estático: no necesita Node ni base de datos en el servidor. El build usa
`base: './'` y todas las rutas de assets pasan por `src/lib/ruta.js`, así que el
mismo paquete funciona en la raíz del dominio o en una subcarpeta de prueba.

Dentro del ZIP van también un `.htaccess` con compresión y caché para hostings
Apache (si el hosting usa nginx se ignora sin romper nada) y un `LEEME.txt` con
el paso a paso para quien lo suba.

El paquete no se versiona: se regenera cuando hace falta.

## Antes de publicar

Leé `ASSETS-TODO.md`: fotos de platillo, logo, tipografía, dirección y horarios.
