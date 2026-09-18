/**
 * Convierte los OTF de Rustic Delight a woff2 para la web.
 *
 * Los OTF que entrega la fundición son formato de escritorio: pesan 145 KB
 * cada uno y ningún navegador los quiere. Este script los pasa a woff2 y de
 * paso los recorta al latín, que es lo único que el sitio escribe: queda un
 * archivo varias veces más chico sin perder una sola letra de las que se usan.
 *
 * Necesita Python con fonttools y brotli: pip install fonttools brotli
 * Uso: npm run display
 */
import { execFileSync } from 'node:child_process';
import { readdirSync, statSync } from 'node:fs';
import path from 'node:path';

const RAIZ = path.resolve(import.meta.dirname, '..');
const ORIGEN = path.join(RAIZ, 'assets-origen', 'fuentes');
const DESTINO = path.join(RAIZ, 'public', 'fonts');

// Latín básico y suplemento (acentos y ¿¡), comillas y guiones tipográficos,
// puntos suspensivos, € y ™. Todo lo que aparece en los títulos del sitio.
const RANGO = 'U+0020-00FF,U+0131,U+0152-0153,U+2010-2027,U+20AC,U+2122';

const SALIDAS = {
  'rustic-delight.otf': 'RusticDelight.woff2',
  'rustic-delight-italic.otf': 'RusticDelight-Italic.woff2',
};

const kb = (p) => `${(statSync(p).size / 1024).toFixed(0)} KB`;

const archivos = readdirSync(ORIGEN).filter((f) => /\.(otf|ttf)$/i.test(f));
if (archivos.length === 0) {
  console.log(`No hay fuentes en ${path.relative(RAIZ, ORIGEN)}`);
  process.exit(0);
}

for (const archivo of archivos) {
  const salida = SALIDAS[archivo];
  if (!salida) {
    console.log(`· ${archivo}: sin destino definido en el script, se omite`);
    continue;
  }
  const entrada = path.join(ORIGEN, archivo);
  const destino = path.join(DESTINO, salida);
  execFileSync('python3', [
    '-m',
    'fontTools.subset',
    entrada,
    `--unicodes=${RANGO}`,
    '--layout-features=kern,liga,calt',
    '--flavor=woff2',
    `--output-file=${destino}`,
  ]);
  console.log(`✓ ${archivo} (${kb(entrada)}) → ${salida} (${kb(destino)})`);
}
