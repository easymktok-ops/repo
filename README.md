# The Spa Mita — sitio web bilingüe (ES/EN)

Sitio de marketing para **The Spa Mita**, day spa de lujo en Punta de Mita, Nayarit.
Construido con [Astro](https://astro.build) (salida HTML estática), bilingüe con rutas
separadas `/es` y `/en` + `hreflang`.

## Requisitos

- Node.js ≥ 18

## Comandos

```bash
npm install            # instalar dependencias
npm run dev            # servidor de desarrollo (http://localhost:4321)
npm run build          # build de producción -> dist/
npm run preview        # previsualizar el build
node scripts/optimize-images.mjs   # regenerar WebP desde assets-originales/
```

## Estructura

```
assets-originales/          Imágenes originales del cliente (fuente)
public/img/optimized/       WebP generados (full 1600px / thumb 640px)
src/
  i18n/ui.ts                Diccionario bilingüe (copy de marca real)
  i18n/services.ts          Servicios y lista de precios (datos reales)
  i18n/site.ts              Constantes: WhatsApp, correo, backend del form
  layouts/BaseLayout.astro  <head> SEO, schema.org, sidebar, WhatsApp
  components/               Sidebar, Home, ServiceList, ContactForm, Gallery…
  pages/es|en/              Páginas por idioma
  pages/sitemap.xml.ts      Sitemap con alternates hreflang
```

## Formulario de contacto (Web3Forms)

El formulario usa [Web3Forms](https://web3forms.com) (gratis, sin cuenta ni dashboard).

**Para activarlo:**
1. Entra a https://web3forms.com y escribe `info@thespamita.com`.
2. Te llega un *access key* por correo.
3. Pégalo en `src/i18n/site.ts` → `web3formsKey`.

Los envíos llegan a `info@thespamita.com` e incluyen: nombre, teléfono, servicio,
fecha tentativa, mensaje, si es primera vez, idioma y página de origen. Tras el envío
redirige a la thank-you page (`/es/gracias/` o `/en/gracias/`).

## Idioma

- El toggle ES/EN cambia de ruta y guarda la preferencia en `localStorage`.
- Todo el copy vive en `src/i18n/ui.ts`. Para editar textos, cambia ahí (ambos idiomas).

## Imágenes

- Coloca originales en `assets-originales/` y corre `node scripts/optimize-images.mjs`.
- Genera 2 tamaños WebP servidos con `srcset` + `loading="lazy"` (excepto el hero).

## Despliegue

El sitio es estático (`dist/`). Opciones:
- **Netlify / Vercel / Cloudflare Pages**: conecta el repo, build `npm run build`, publish `dist/`.
- **Hosting compartido / FTP**: sube el contenido de `dist/` a la raíz pública.

## SEO

- `schema.org` tipo `DaySpa` con `areaServed` (Punta de Mita, Bahía de Banderas,
  Sayulita, Nuevo Vallarta, Puerto Vallarta), Open Graph, `hreflang`, `sitemap.xml`,
  `robots.txt` y `font-display: swap`.
- El dominio del `site` está en `astro.config.mjs` (`https://thespamita.com`).
