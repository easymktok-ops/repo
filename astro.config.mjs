// @ts-check
import { defineConfig } from "astro/config";
import react from "@astrojs/react";
import sitemap from "@astrojs/sitemap";
import tailwindcss from "@tailwindcss/vite";

/**
 * DOMAIN-AGNOSTIC by contract (ver PRODUCT.md / brief seccion 1).
 * El dominio final (aerodiverti.mx u otro) esta pendiente de resolucion
 * administrativa y NO debe hardcodearse. `site` se toma de la variable de
 * entorno PUBLIC_SITE_URL. Cuando el dominio se confirme, el corte es UN
 * cambio de variable de entorno en el panel de Hostinger, nunca un refactor.
 *
 * En build sin variable, cae a un placeholder de trabajo que solo afecta la
 * generacion de URLs absolutas de sitemap/canonical durante desarrollo.
 */
const SITE_URL =
  process.env.PUBLIC_SITE_URL?.replace(/\/$/, "") ||
  "https://aerodiverti.example"; // placeholder de trabajo, se sobreescribe por env

export default defineConfig({
  site: SITE_URL,

  // SSG por defecto (cero JS en paginas de contenido). Las islas interactivas
  // (selector, checkout, carrusel) se hidratan con client:* de forma puntual.
  // Fase 3 puede promover rutas concretas a on-demand con un adapter de
  // Hostinger/Node si el checkout necesita logica de servidor.
  output: "static",

  i18n: {
    defaultLocale: "es",
    locales: ["es", "en"],
    routing: {
      // es se sirve en la raiz (/), en se sirve bajo /en/. hreflang se
      // construye en BaseLayout a partir de esta config.
      prefixDefaultLocale: false,
      redirectToDefaultLocale: false,
    },
  },

  integrations: [
    react(),
    sitemap({
      i18n: {
        defaultLocale: "es",
        locales: { es: "es-MX", en: "en" },
      },
    }),
  ],

  vite: {
    plugins: [tailwindcss()],
  },

  image: {
    // Formatos modernos por defecto; el pipeline de <Image /> emite AVIF/WebP
    // con srcset responsive. El LCP se precarga aparte con fetchpriority=high.
    responsiveStyles: true,
  },

  build: {
    // Hashea assets para cache-busting agresivo en Hostinger.
    assets: "_assets",
  },

  devToolbar: { enabled: false },
});
