import { defineConfig } from 'astro/config';

// Sitio bilingüe con rutas separadas por idioma (/es, /en) + hreflang.
// El español es el idioma por defecto; la raíz redirige a /es.
export default defineConfig({
  site: 'https://thespamita.com',
  i18n: {
    defaultLocale: 'es',
    locales: ['es', 'en'],
    routing: {
      prefixDefaultLocale: true, // /es y /en explícitos, mejor para hreflang
    },
  },
  build: {
    inlineStylesheets: 'auto',
  },
});
