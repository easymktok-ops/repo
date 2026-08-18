import type { Locale } from "./site";

/**
 * Navegacion del menu lateral persistente (patron theenglishbus, elevado).
 * Estructura estable por SEO/memoria muscular. Los labels vienen del brief:
 * Inicio, Explorar vuelos, Globopuerto, Galeria, Favoritos, Contacto.
 * Los iconos se resuelven en el componente (Phosphor), no aqui.
 */

export interface NavItem {
  /** id estable (no cambia entre idiomas; sirve para tracking/anclas). */
  id: string;
  /** slug base en es; el helper localizedPath le antepone /en cuando toca. */
  path: string;
  label: Record<Locale, string>;
  icon: string; // nombre logico -> se mapea a un glifo Phosphor en el componente
}

export const primaryNav: NavItem[] = [
  {
    id: "home",
    path: "/",
    label: { es: "Inicio", en: "Home" },
    icon: "house",
  },
  {
    id: "flights",
    path: "/vuelos",
    label: { es: "Explorar vuelos", en: "Explore flights" },
    icon: "balloon",
  },
  {
    id: "globopuerto",
    path: "/globopuerto",
    label: { es: "Globopuerto", en: "The launch site" },
    icon: "compass",
  },
  {
    id: "gallery",
    path: "/galeria",
    label: { es: "Galeria", en: "Gallery" },
    icon: "images",
  },
  {
    id: "favorites",
    path: "/favoritos",
    label: { es: "Favoritos", en: "Favorites" },
    icon: "heart",
  },
  {
    id: "contact",
    path: "/contacto",
    label: { es: "Contacto", en: "Contact" },
    icon: "chat",
  },
];
