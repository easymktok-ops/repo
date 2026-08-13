// Helper de rutas base-aware. Permite que el sitio funcione tanto en la raíz
// del dominio como en una subcarpeta (ej. /demo), según `base` en astro.config.
// Astro expone la base en import.meta.env.BASE_URL (con slash final).

const RAW = import.meta.env.BASE_URL || '/';
// Base sin slash final: '' en raíz, '/demo' en subcarpeta.
export const BASE = RAW.replace(/\/+$/, '');

/** Prefija una ruta interna con la base. wb('/es/') -> '/es/' o '/demo/es/'. */
export function wb(path = '/'): string {
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${BASE}${p}` || '/';
}
