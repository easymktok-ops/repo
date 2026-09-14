/**
 * Prepara los assets que caen en public/assets/.
 *
 *  - genera el .webp hermano de cada .png / .jpg (lo que sirve <picture>)
 *  - avisa si una foto de platillo NO tiene canal alfa: la rueda necesita
 *    PNG recortados con fondo transparente
 *  - avisa si una imagen es exageradamente grande para web
 *
 * Uso: npm run optimizar
 */
import { readdir, stat } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const RAIZ = path.resolve(import.meta.dirname, '..', 'public', 'assets');
const CARPETAS = ['platillos', 'logo', 'fotos'];
const LADO_MAX = { platillos: 900, logo: 600, fotos: 1600 };

const avisos = [];
let generados = 0;

for (const carpeta of CARPETAS) {
  const dir = path.join(RAIZ, carpeta);
  if (!existsSync(dir)) continue;

  const archivos = (await readdir(dir)).filter((f) => /\.(png|jpe?g)$/i.test(f));
  if (archivos.length === 0) {
    avisos.push(`· ${carpeta}/ está vacía`);
    continue;
  }

  for (const archivo of archivos) {
    const entrada = path.join(dir, archivo);
    const imagen = sharp(entrada);
    const meta = await imagen.metadata();
    const { size } = await stat(entrada);

    if (carpeta === 'platillos' && !meta.hasAlpha) {
      avisos.push(
        `· platillos/${archivo}: sin canal alfa. La rueda necesita el bowl recortado sobre fondo transparente.`,
      );
    }
    if (size > 2_000_000) {
      avisos.push(`· ${carpeta}/${archivo}: ${(size / 1e6).toFixed(1)} MB en origen; conviene achicar.`);
    }

    const lado = LADO_MAX[carpeta];
    const salida = entrada.replace(/\.(png|jpe?g)$/i, '.webp');
    await imagen
      .resize({ width: lado, height: lado, fit: 'inside', withoutEnlargement: true })
      .webp({ quality: 82, effort: 5 })
      .toFile(salida);
    generados += 1;
    console.log(`✓ ${carpeta}/${path.basename(salida)}  (${meta.width}×${meta.height}${meta.hasAlpha ? ', con alfa' : ''})`);
  }
}

console.log(`\n${generados} archivos .webp generados.`);
if (avisos.length) {
  console.log('\nRevisar:');
  for (const aviso of avisos) console.log(aviso);
}
