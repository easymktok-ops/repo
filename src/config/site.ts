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

  /** Datos de negocio CONFIRMADOS (sitio actual del negocio). Nada inventado. */
  brand: {
    // Descripcion factual y citable por LLMs (LLMO).
    tagline: {
      es: "Vuelos en globo aerostático al amanecer sobre el valle de Teotihuacán.",
      en: "Sunrise hot-air balloon flights over the Teotihuacán valley.",
    },
    // WhatsApp real del negocio (digitos para wa.me). El display es editable por env.
    whatsapp: "5215535780223",
    whatsappDisplay: import.meta.env.PUBLIC_WHATSAPP_DISPLAY_NUMBER ?? "+52 55 3578 0223",
    // Reseñas verificadas (Google). Cifra real del negocio.
    reviews: { rating: 4.9, count: 2040, source: "Google" as const },
    // Ubicacion / globopuerto propio.
    location: {
      es: "San Martín de las Pirámides, a 2 km de las pirámides",
      en: "San Martín de las Pirámides, 2 km from the pyramids",
    },
    // Contacto real del negocio.
    phoneDisplay: "(55) 53 41 98 34",
    phone: "525553419834",
    email: "ventas@aerodiverti.com",
    address: {
      line1: "Álvaro Obregón 22",
      area: "San Martín de las Pirámides, Estado de México",
      zip: "C.P. 55850",
    },
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

/** URL de WhatsApp del negocio, con mensaje opcional prellenado. */
export function whatsappUrl(text?: string): string {
  const base = `https://wa.me/${site.brand.whatsapp}`;
  return text ? `${base}?text=${encodeURIComponent(text)}` : base;
}

/** Prefijo de ruta por locale. es vive en la raiz; en bajo /en. */
export function localizedPath(path: string, locale: Locale): string {
  const clean = path === "/" ? "" : path.replace(/^\/|\/$/g, "");
  if (locale === site.defaultLocale) return `/${clean}`.replace(/\/$/, "") || "/";
  return `/${locale}/${clean}`.replace(/\/$/, "") || `/${locale}`;
}
