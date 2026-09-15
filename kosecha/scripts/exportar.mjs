/**
 * Arma el paquete para subir a mano a un hosting.
 *
 * Compila el sitio, le suma el .htaccess y el instructivo, y deja un ZIP cuyo
 * contenido va directo a public_html. El build usa base relativa, así que el
 * mismo ZIP sirve en la raíz del dominio o en una subcarpeta de prueba.
 *
 * Uso: npm run exportar
 */
import { execFileSync } from 'node:child_process';
import { cp, mkdir, rm, readdir, stat } from 'node:fs/promises';
import path from 'node:path';

const RAIZ = path.resolve(import.meta.dirname, '..');
const DIST = path.join(RAIZ, 'dist');
const EXTRA = path.join(RAIZ, 'export-extra');
const SALIDA = path.join(RAIZ, 'export');
const CARPETA = 'kosecha-sitio';

const correr = (cmd, args, cwd = RAIZ) =>
  execFileSync(cmd, args, { cwd, stdio: 'inherit', shell: process.platform === 'win32' });

console.log('· Compilando…');
correr('npx', ['vite', 'build']);

console.log('· Armando el paquete…');
await rm(SALIDA, { recursive: true, force: true });
const destino = path.join(SALIDA, CARPETA);
await mkdir(destino, { recursive: true });
await cp(DIST, destino, { recursive: true });
await cp(EXTRA, destino, { recursive: true });

const zip = path.join(SALIDA, `${CARPETA}.zip`);
correr('zip', ['-r', '-q', zip, CARPETA], SALIDA);

const pesar = async (dir) => {
  let total = 0;
  for (const entrada of await readdir(dir, { withFileTypes: true })) {
    const p = path.join(dir, entrada.name);
    total += entrada.isDirectory() ? await pesar(p) : (await stat(p)).size;
  }
  return total;
};

const kb = (n) => `${(n / 1024).toFixed(0)} KB`;
console.log(`\n✓ ${path.relative(RAIZ, zip)}  ${kb((await stat(zip)).size)}`);
console.log(`  sin comprimir: ${kb(await pesar(destino))}`);
console.log('\n  Su contenido va directo a public_html/ (o a una subcarpeta para probar).');
