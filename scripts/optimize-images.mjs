// Optimiza las imágenes de assets-originales/ a WebP en 2 tamaños.
//   - full:  máx 1600px de ancho (hero / imágenes grandes)
//   - thumb: máx 640px de ancho (galería / miniaturas, srcset)
// Salida: public/img/optimized/<full|thumb>/<nombre>.webp
// Uso: node scripts/optimize-images.mjs
import { readdir, mkdir } from 'node:fs/promises';
import { join, parse } from 'node:path';
import sharp from 'sharp';

const SRC = 'assets-originales';
const OUT = 'public/img/optimized';
const SIZES = { full: 1600, thumb: 640 };
const RASTER = new Set(['.jpg', '.jpeg', '.png']);

const files = (await readdir(SRC)).filter((f) => RASTER.has(parse(f).ext.toLowerCase()));
for (const size of Object.keys(SIZES)) await mkdir(join(OUT, size), { recursive: true });

let ok = 0;
for (const f of files) {
  const name = parse(f).name;
  for (const [size, width] of Object.entries(SIZES)) {
    await sharp(join(SRC, f))
      .rotate()
      .resize({ width, withoutEnlargement: true })
      .webp({ quality: size === 'thumb' ? 72 : 80 })
      .toFile(join(OUT, size, `${name}.webp`));
  }
  ok++;
}
console.log(`Optimizadas ${ok} imágenes → ${OUT}/{full,thumb}/`);
