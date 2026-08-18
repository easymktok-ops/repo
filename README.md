# Aerodiverti · Plataforma

Sitio de marca + reservas para Aerodiverti (vuelos en globo al amanecer sobre
Teotihuacán). Astro (SSG/híbrido) con islas React, Motion, Tailwind v4 sobre
tokens OKLCH, y CMS git-based (Decap) para autoadministración.

## Stack

- **Astro 5** en `output: static` (cero JS en páginas de contenido; islas
  puntuales para selector, checkout y carrusel).
- **React 19** para las islas interactivas (una sola librería de UI, no se mezcla).
- **Motion** (`motion/react`) para movimiento continuo/físico.
- **Tailwind v4** vía `@tailwindcss/vite`, mapeado a los tokens OKLCH de
  `src/styles/tokens.css` con `@theme inline` (fuente de verdad única).
- **Phosphor Icons** para iconografía.
- **Decap CMS** (`/admin`) git-based para contenido autoadministrable.
- **Pagos**: Stripe (adapter en `src/lib/payments`).
- **Notificaciones**: email + WhatsApp con outbox de reintentos y logging de
  fallos (`src/lib/notifications`); proveedor concreto se define en Fase 3.

## Dominio-agnóstico (importante)

El dominio final NO está hardcodeado en ningún lado. Todo lo absoluto
(`canonical`, `hreflang`, OG, sitemap, robots) se construye desde
`PUBLIC_SITE_URL`. Cambiar de staging a producción es editar variables de entorno
(en Hostinger), nunca refactorizar código. Ver `.env.example`.

## Requisitos

- Node ≥ 20.11 (recomendado 22, ver `.nvmrc`).

## Arranque

```bash
cp .env.example .env      # rellena valores (nada se sube al repo)
npm install
npm run dev               # sitio en desarrollo
npm run cms:proxy         # (otra terminal) backend local de Decap para /admin
```

## Scripts

| Script              | Qué hace                                  |
| ------------------- | ----------------------------------------- |
| `npm run dev`       | Servidor de desarrollo                    |
| `npm run build`     | Build estático a `dist/`                  |
| `npm run preview`   | Sirve el build local                      |
| `npm run check`     | `astro check` (tipos + diagnósticos)      |
| `npm run cms:proxy` | Backend local de Decap CMS (dev)          |
| `npm run format`    | Prettier sobre el código                  |

## Estructura

```
src/
  config/         # site (dominio-agnóstico), navegación
  content.config.ts  # schema de las colecciones (contrato del CMS)
  content/        # contenido editable por el negocio (vía /admin)
  i18n/           # diccionario UI + utilidades es/en, hreflang
  layouts/        # BaseLayout (SEO/OG/hreflang)
  lib/
    payments/     # adapter de pasarela (Stripe)
    notifications/# outbox + reintentos + logging (email/WhatsApp)
    seo/          # (Fase 4) JSON-LD builders
  pages/          # rutas (es en raíz, en bajo /en)
  styles/         # tokens.css (OKLCH) + global.css + fonts.css
  components/     # ui / sections / islands (Fase 2)
public/
  admin/          # Decap CMS (panel privado, noindex)
```

## Roadmap

- **Fase 1** (esta entrega): entorno, arquitectura, tokens OKLCH, i18n, SEO base,
  contratos de pagos/notificaciones, CMS. ✅
- **Fase 2**: componentes y secciones (hero video, catálogo, boarding-pass, doble
  exposición, FAQ, menú lateral).
- **Fase 3**: motor de reservas (3 pasos), Stripe, email + WhatsApp reales.
- **Fase 4**: JSON-LD, sitemap/robots, auditoría Core Web Vitals.
- **Fase 5**: despliegue en Hostinger + checklist de corte de dominio (301 desde
  dominios secundarios).
