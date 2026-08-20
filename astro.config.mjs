import { defineConfig } from 'astro/config';

// Sitio bilingüe con rutas separadas por idioma (/es, /en) + hreflang.
// El español es el idioma por defecto; la raíz redirige a /es.
export default defineConfig({
  site: 'https://thespamita.com',
  // Despliegue en la raíz del dominio (producción).
  // Para volver a una subcarpeta de prueba, usar base: '/demo'.
  base: '/',
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
