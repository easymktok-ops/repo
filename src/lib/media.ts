import type { ImageMetadata } from "astro";

/**
 * Resuelve imagenes de src/assets/uploads por nombre de archivo para usarlas
 * con <Image> de Astro (que las optimiza a AVIF/WebP responsive en build).
 * El negocio sube a esa carpeta; aqui se referencian por nombre.
 */
const uploads = import.meta.glob<ImageMetadata>(
  "/src/assets/uploads/*.{jpg,jpeg,png,webp,avif}",
  { eager: true, import: "default" },
);

export function img(name: string): ImageMetadata {
  const hit = Object.entries(uploads).find(([k]) => k.endsWith(`/${name}`));
  if (!hit) {
    throw new Error(
      `[media] no existe src/assets/uploads/${name}. Subelo al repo (o revisa el nombre).`,
    );
  }
  return hit[1];
}

/** true si la imagen existe (para render condicional sin lanzar). */
export function hasImg(name: string): boolean {
  return Object.keys(uploads).some((k) => k.endsWith(`/${name}`));
}
