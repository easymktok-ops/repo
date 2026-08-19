/**
 * Configuracion central del sitio. DOMINIO-AGNOSTICO: todo lo que dependa del
 * host se lee de import.meta.env, nunca se hardcodea. Un cambio de entorno
 * (staging -> produccion) es editar variables, no tocar este archivo.
 */

export type Locale = "es" | "en";

const rawUrl = import.meta.env.PUBLIC_SITE_URL ?? "https://aerodiverti.example";

export const site = {
  /** URL base sin barra final. */
  url: rawUrl.replace(/\/$/, ""),
  name: import.meta.env.PUBLIC_SITE_NAME ?? "Aerodiverti",
  defaultLocale: (import.meta.env.PUBLIC_DEFAULT_LOCALE ?? "es") as Locale,
  locales: ["es", "en"] as const,

  /** Datos de negocio CONFIRMADOS por el brief. Nada inventado. */
  brand: {
    // Descripcion factual y citable por LLMs (LLMO). Sin cifras no confirmadas.
    tagline: {
      es: "Vuelos en globo aerostático al amanecer sobre el valle de Teotihuacán.",
      en: "Sunrise hot-air balloon flights over the Teotihuacán valley.",
    },
    // Placeholders explicitos: el negocio los edita en el CMS / .env.
    whatsappDisplay: import.meta.env.PUBLIC_WHATSAPP_DISPLAY_NUMBER ?? "",
  },

  analytics: {
    gaId: import.meta.env.PUBLIC_GA_ID ?? "",
    plausibleDomain: import.meta.env.PUBLIC_PLAUSIBLE_DOMAIN ?? "",
  },
} as const;

/** Construye una URL absoluta a partir de un path relativo, usando site.url. */
export function absoluteUrl(path = "/"): string {
  const clean = path.startsWith("/") ? path : `/${path}`;
  return `${site.url}${clean}`;
}

/** Prefijo de ruta por locale. es vive en la raiz; en bajo /en. */
export function localizedPath(path: string, locale: Locale): string {
  const clean = path === "/" ? "" : path.replace(/^\/|\/$/g, "");
  if (locale === site.defaultLocale) return `/${clean}`.replace(/\/$/, "") || "/";
  return `/${locale}/${clean}`.replace(/\/$/, "") || `/${locale}`;
}
