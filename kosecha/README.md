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
| `npm run optimizar` | Genera los `.webp` de `public/assets/` y avisa si una foto de platillo no tiene fondo transparente |
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
public/assets/      logo/ platillos/ fotos/   (ver ASSETS-TODO.md)
```

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

## Accesibilidad

WCAG 2.1 AA. Los acentos de marca no llegan a 4.5:1 sobre crema, así que para
texto y botones se usan `--kos-naranja-hondo` y `--kos-lima-hondo`; los acentos
originales solo pintan superficies. `npm run auditar` mide esto en el navegador
y tiene que dar cero en las tres métricas.

## Antes de publicar

Leé `ASSETS-TODO.md`: fotos de platillo, logo, tipografía, dirección y horarios.
