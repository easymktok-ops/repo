# Happy Puerto — Sitio web (Fase 1)

Sitio de **cotización por WhatsApp** para Happy Puerto, vuelos en globo aerostático
sobre Teotihuacán. Divertido, dinámico y lleno de energía (inspirado en el look de
Starface). **No hay checkout ni pagos en línea**: todo el cierre de venta ocurre por
WhatsApp mediante enlaces `wa.me` con mensaje personalizado por paquete.

Hecho por **easy mkt** para Happy Puerto México.

## Stack

- **Astro 5** con **React islands** donde hay interactividad.
- **Tailwind CSS** + CSS custom para animaciones (`src/styles/global.css`).
- **Motion** (`motion`) para los scroll reveals (import dinámico con fallback).
- **JavaScript puro** (sin TypeScript en Fase 1).
- Fuentes Google: **Bangers** (display), **Fredoka** (subheadings) e **Inter** (body).

> Nota tipográfica: el brief pedía "Ahkio" para subheadings, pero no está en Google
> Fonts. Se sustituyó por **Fredoka** (redondeada, amistosa, con carácter), respetando
> el máximo de 3 familias.

## Requisitos

- Node.js ≥ 18.20

## Comandos

```bash
npm install       # instalar dependencias
npm run dev       # servidor de desarrollo (http://localhost:4321)
npm run build     # build de producción -> ./dist
npm run preview   # previsualizar el build
```

## Build domain-agnostic

La URL y el subpath se leen de variables de entorno (ver `.env.example`):

- `SITE_URL` — URL canónica absoluta (opcional; para `<link canonical>` / OG).
- `BASE_PATH` — subpath de despliegue (por defecto `/`).

Todos los assets se referencian con `import.meta.env.BASE_URL`, así el mismo build
funciona en cualquier dominio o subcarpeta.

## Estructura

```
public/
  assets/
    logo-happy-puerto.svg     # sello circular (SVG de marca)
    balloon-cursor.svg        # mascota del cursor (globo carita)
    hero-valle.jpg            # poster del hero
    galeria/                  # fotos de galería (cliente)
    ocasiones/                # fotos por ocasión (cliente)
    videos/hero-loop.mp4      # video de fondo del hero
  favicon.svg
src/
  components/                 # Nav, Hero, TrustBar, PackageCard(.jsx), etc.
  data/site.js               # datos centrales (contacto, paquetes, políticas, redes)
  layouts/Layout.astro
  pages/index.astro
  styles/global.css
```

## Secciones (Fase 1)

Nav · Hero (video) · Trust bar · Paquetes (6, layout editorial) · Cómo funciona ·
Galería (masonry + lightbox) · Quiénes somos · Reseñas (placeholder) · Políticas
(accordion) · Contacto (mapa) · Footer · Botón flotante de WhatsApp · Cursor mascota.

## Datos que se editan en un solo lugar

Casi todo el contenido vive en `src/data/site.js`: teléfono de WhatsApp, paquetes y
precios, pasos, galería, reseñas, políticas y redes sociales.

## Pendientes para Fase 2

- Reseñas reales y **URL de Viator** (hoy es placeholder, marcado con `TODO Fase 2`).
- Video/gráficos finales definitivos y textos legales completos si aplica.
- Versiones en otros idiomas (Fase 1 es solo español).
