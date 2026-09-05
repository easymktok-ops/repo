// @ts-check
import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwind from '@astrojs/tailwind';

/**
 * Build domain-agnostic: la URL del sitio y el base path se leen de variables
 * de entorno para que el mismo build sirva en cualquier dominio (suempresa.com,
 * happypuerto.com, un subpath, etc.). Ver .env.example.
 *
 *   SITE_URL   -> URL canónica absoluta (opcional, para sitemap/OG)
 *   BASE_PATH  -> subpath de despliegue, por defecto "/"
 */
const SITE_URL = process.env.SITE_URL || undefined;
const BASE_PATH = process.env.BASE_PATH || '/';

export default defineConfig({
  site: SITE_URL,
  base: BASE_PATH,
  trailingSlash: 'ignore',
  integrations: [
    react(),
    // Aplicamos las capas de Tailwind manualmente en src/styles/global.css
    // para controlar el orden con nuestro CSS custom de animaciones.
    tailwind({ applyBaseStyles: false }),
  ],
  vite: {
    build: {
      assetsInlineLimit: 1024,
    },
  },
});
