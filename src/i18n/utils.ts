import type { Locale } from "@config/site";
import { site } from "@config/site";

/** Extrae el locale de una URL de Astro. es por defecto (sin prefijo). */
export function getLocaleFromUrl(url: URL): Locale {
  const [, seg] = url.pathname.split("/");
  if (seg === "en") return "en";
  return "es";
}

/** El "otro" locale, para el conmutador de idioma. */
export function alternateLocale(locale: Locale): Locale {
  return locale === "es" ? "en" : "es";
}

/**
 * Mapa de alternativas hreflang para una ruta logica dada (sin prefijo).
 * Devuelve entradas absolutas listas para <link rel="alternate" hreflang>.
 * Incluye x-default apuntando al locale por defecto.
 */
export function hreflangAlternates(logicalPath: string) {
  const clean = logicalPath === "/" ? "" : logicalPath.replace(/^\/|\/$/g, "");
  const esHref = `${site.url}/${clean}`.replace(/\/$/, "") || site.url;
  const enHref = `${site.url}/en/${clean}`.replace(/\/$/, "") || `${site.url}/en`;
  return [
    { hreflang: "es-MX", href: esHref },
    { hreflang: "en", href: enHref },
    { hreflang: "x-default", href: esHref },
  ];
}
