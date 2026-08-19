import type { Locale } from "./site";

/**
 * Navegacion del menu lateral persistente (patron theenglishbus, elevado).
 * Estructura estable por SEO/memoria muscular. Labels del brief + "Ocasiones"
 * (decision de negocio: el material del negocio esta organizado por ocasion, y
 * es el gancho de regalo/alto ticket). `icon` = nombre de glifo Phosphor.
 */

export interface NavItem {
  id: string; // id estable (no cambia entre idiomas; sirve para tracking/anclas)
  path: string; // slug base en es; localizedPath antepone /en cuando toca
  label: Record<Locale, string>;
  icon: string; // glifo Phosphor (src/assets/icons/phosphor/regular)
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
    icon: "path",
  },
  {
    id: "occasions",
    path: "/ocasiones",
    label: { es: "Ocasiones", en: "Occasions" },
    icon: "gift",
  },
  {
    id: "globopuerto",
    path: "/globopuerto",
    label: { es: "Globopuerto", en: "The launch site" },
    icon: "map-pin",
  },
  {
    id: "gallery",
    path: "/galeria",
    label: { es: "Galería", en: "Gallery" },
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
    icon: "chat-circle",
  },
];
