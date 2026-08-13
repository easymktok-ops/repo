import { defineConfig } from 'astro/config';

// Sitio bilingüe con rutas separadas por idioma (/es, /en) + hreflang.
// El español es el idioma por defecto; la raíz redirige a /es.
export default defineConfig({
  site: 'https://thespamita.com',
  // Subcarpeta de despliegue. Para la demo: '/demo'. Para producción en la
  // raíz del dominio, cambiar a '/' (o eliminar esta línea).
  base: '/demo',
  i18n: {
    defaultLocale: 'es',
    locales: ['es', 'en'],
    routing: {
      prefixDefaultLocale: true, // /es y /en explícitos, mejor para hreflang
      redirectToDefaultLocale: false, // usamos nuestro index.astro (redirect relativo)
    },
  },
  build: {
    inlineStylesheets: 'auto',
  },
});
