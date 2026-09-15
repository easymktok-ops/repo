/**
 * Descarga Poppins de Google Fonts y la deja auto-hospedada en public/fonts/.
 *
 * Por qué: el <link> a fonts.googleapis.com es una petición que bloquea el
 * render, depende de un tercero y falla en redes que filtran ese dominio. Con
 * la fuente en el propio dominio, la página no depende de nadie más.
 *
 * Solo se guardan los subconjuntos latin y latin-ext, que es lo que usa el
 * español. Uso: npm run fuentes
 */
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const DESTINO = path.resolve(import.meta.dirname, '..', 'public', 'fonts');
const CSS = 'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap';
const NAVEGADOR = {
  'User-Agent':
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
};

await mkdir(DESTINO, { recursive: true });

const css = await (await fetch(CSS, { headers: NAVEGADOR })).text();
const bloques = css.split('/*').slice(1);
const piezas = [];

for (const bloque of bloques) {
  const subconjunto = bloque.slice(0, bloque.indexOf('*/')).trim();
  if (subconjunto !== 'latin' && subconjunto !== 'latin-ext') continue;

  const url = bloque.match(/url\((https:[^)]+\.woff2)\)/)?.[1];
  const peso = bloque.match(/font-weight:\s*(\d+)/)?.[1];
  const estilo = bloque.match(/font-style:\s*(\w+)/)?.[1] ?? 'normal';
  const rango = bloque.match(/unicode-range:\s*([^;]+);/)?.[1];
  if (!url || !peso) continue;

  const archivo = `poppins-${peso}${estilo === 'italic' ? '-italic' : ''}-${subconjunto}.woff2`;
  const datos = Buffer.from(await (await fetch(url, { headers: NAVEGADOR })).arrayBuffer());
  await writeFile(path.join(DESTINO, archivo), datos);
  piezas.push({ archivo, peso, estilo, rango, kb: (datos.length / 1024).toFixed(1) });
  console.log(`✓ ${archivo.padEnd(34)} ${datos.length.toLocaleString()} bytes`);
}

const declaraciones = piezas
  .map(
    (p) => `@font-face {
  font-family: 'Poppins';
  font-style: ${p.estilo};
  font-weight: ${p.peso};
  font-display: swap;
  src: url('./${p.archivo}') format('woff2');
  unicode-range: ${p.rango};
}`,
  )
  .join('\n\n');

await writeFile(
  path.join(DESTINO, 'poppins.css'),
  `/* Generado por scripts/traer-fuentes.mjs — no editar a mano.\n   Poppins, subconjuntos latin y latin-ext, auto-hospedada. */\n\n${declaraciones}\n`,
);
console.log(`\n${piezas.length} archivos + poppins.css en public/fonts/`);
