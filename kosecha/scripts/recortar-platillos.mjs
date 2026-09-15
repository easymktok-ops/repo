/**
 * Recorta las fotos de platillo sobre su fondo blanco.
 *
 * Las de origen (assets-origen/platillos/) son tomas cenitales de 1080px sobre
 * blanco puro. El problema: el plato también es blanco, y del fondo solo lo
 * separa una sombra suave, así que no alcanza con borrar lo blanco ni con
 * inundar desde los bordes (la inundación se filtra y se come el plato).
 *
 * Dos caminos, decididos por la forma del objeto:
 *
 *  - Plato redondo: se busca, en 180 rayos desde el centro, dónde termina la
 *    sombra y empieza el filo del plato; con esos puntos se ajusta un círculo
 *    por mínimos cuadrados y ese círculo es la máscara. Lo que sobresale del
 *    plato (una hoja, una rodaja) se rescata aparte porque tiene color, cosa
 *    que la sombra gris no tiene.
 *  - Objeto irregular (wrap, sándwich): inundación desde los bordes con umbral
 *    de blanco puro, que ahí sí funciona porque el objeto tiene contorno con
 *    color. Después un cierre morfológico para recuperar el papel blanco que
 *    la inundación haya mordido.
 *
 * Salida: public/assets/platillos/<slug>.png con alfa + su .webp.
 * Uso: npm run recortar
 */
import { readdir, mkdir } from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const ORIGEN = path.resolve(import.meta.dirname, '..', 'assets-origen', 'platillos');
const DESTINO = path.resolve(import.meta.dirname, '..', 'public', 'assets', 'platillos');
const LADO = 900;
const LADO_CHICO = 480;

/** Archivo de origen → id de imagen en src/data/platillos.js */
const SLUGS = {
  mediterranea: 'mediterranea',
  bosque: 'bosque',
  surena: 'surena',
  asiatica: 'asiatica',
  coles: 'coles',
  kale: 'kale',
  personalizada: 'personalizada',
  sandiwch: 'sandwich',
  sandwich: 'sandwich',
  wrap: 'wrap',
};

const slugDe = (archivo) => {
  const base = archivo
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/\.[^.]+$/, '')
    .toLowerCase();
  return SLUGS[base] ?? base.replace(/[^a-z0-9]+/g, '-');
};

/** Ajuste algebraico de círculo (Kåsa) sobre los puntos del filo del plato. */
function ajustarCirculo(puntos) {
  let sx = 0, sy = 0, sxx = 0, syy = 0, sxy = 0, sxz = 0, syz = 0, sz = 0;
  const n = puntos.length;
  for (const [x, y] of puntos) {
    const z = x * x + y * y;
    sx += x; sy += y; sxx += x * x; syy += y * y; sxy += x * y;
    sxz += x * z; syz += y * z; sz += z;
  }
  const a = [[sxx, sxy, sx], [sxy, syy, sy], [sx, sy, n]];
  const b = [sxz, syz, sz];
  // eliminación gaussiana 3x3
  for (let i = 0; i < 3; i += 1) {
    let piv = i;
    for (let k = i + 1; k < 3; k += 1) if (Math.abs(a[k][i]) > Math.abs(a[piv][i])) piv = k;
    [a[i], a[piv]] = [a[piv], a[i]];
    [b[i], b[piv]] = [b[piv], b[i]];
    for (let k = i + 1; k < 3; k += 1) {
      const f = a[k][i] / a[i][i];
      for (let j = i; j < 3; j += 1) a[k][j] -= f * a[i][j];
      b[k] -= f * b[i];
    }
  }
  const sol = [0, 0, 0];
  for (let i = 2; i >= 0; i -= 1) {
    let s = b[i];
    for (let j = i + 1; j < 3; j += 1) s -= a[i][j] * sol[j];
    sol[i] = s / a[i][i];
  }
  const cx = sol[0] / 2;
  const cy = sol[1] / 2;
  const radio = Math.sqrt(sol[2] + cx * cx + cy * cy);
  return { cx, cy, radio };
}

/** Rayos desde el centro: dónde vuelve a aclarar después de la sombra. */
function filoDelPlato(lum, w, h) {
  const cx = w / 2;
  const cy = h / 2;
  const maxR = Math.min(cx, cy) - 2;
  const radios = [];
  const puntos = [];
  for (let a = 0; a < 360; a += 2) {
    const dx = Math.cos((a * Math.PI) / 180);
    const dy = Math.sin((a * Math.PI) / 180);
    let vioSombra = false;
    for (let r = maxR; r > 20; r -= 1) {
      const v = lum(Math.round(cx + dx * r), Math.round(cy + dy * r));
      if (!vioSombra) {
        if (v < 240) vioSombra = true;
        continue;
      }
      if (v >= 246 || v < 200) {
        radios.push(r);
        puntos.push([cx + dx * r, cy + dy * r, r]);
        break;
      }
    }
  }
  const orden = [...radios].sort((x, y) => x - y);
  const mediana = orden[Math.floor(orden.length / 2)] ?? 0;
  const p10 = orden[Math.floor(orden.length * 0.1)] ?? 0;
  const p90 = orden[Math.floor(orden.length * 0.9)] ?? 0;
  const dispersion = mediana ? (p90 - p10) / mediana : 1;
  return { puntos, mediana, dispersion };
}

async function recortar(archivo) {
  const entrada = path.join(ORIGEN, archivo);
  const { data, info } = await sharp(entrada).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const { width: w, height: h, channels } = info;

  const min = (i) => Math.min(data[i * channels], data[i * channels + 1], data[i * channels + 2]);
  const croma = (i) => {
    const p = i * channels;
    return Math.max(data[p], data[p + 1], data[p + 2]) - Math.min(data[p], data[p + 1], data[p + 2]);
  };
  const lum = (x, y) => min(y * w + x);

  const mascara = Buffer.alloc(w * h);
  const { puntos, mediana, dispersion } = filoDelPlato(lum, w, h);
  const redondo = dispersion < 0.2 && puntos.length > 120;
  let detalle;

  if (redondo) {
    // Solo los rayos que dieron con el filo, no con comida que sobresale.
    const buenos = puntos.filter(([, , r]) => r > mediana * 0.9 && r < mediana * 1.12).map(([x, y]) => [x, y]);
    const { cx, cy, radio } = ajustarCirculo(buenos.length > 40 ? buenos : puntos.map(([x, y]) => [x, y]));
    const rMax = (radio + 3) ** 2;
    for (let y = 0; y < h; y += 1) {
      for (let x = 0; x < w; x += 1) {
        const i = y * w + x;
        const dentro = (x - cx) ** 2 + (y - cy) ** 2 <= rMax;
        // Fuera del plato solo se rescata lo que tiene color: la sombra es gris.
        mascara[i] = dentro || croma(i) > 24 || min(i) < 140 ? 255 : 0;
      }
    }
    detalle = `plato r=${Math.round(radio)}`;
  } else {
    const fondo = new Uint8Array(w * h);
    const cola = new Int32Array(w * h);
    let fin = 0;
    const empujar = (i) => {
      if (fondo[i] || min(i) < 246 || croma(i) > 6) return;
      fondo[i] = 1;
      cola[fin] = i;
      fin += 1;
    };
    for (let x = 0; x < w; x += 1) { empujar(x); empujar((h - 1) * w + x); }
    for (let y = 0; y < h; y += 1) { empujar(y * w); empujar(y * w + w - 1); }
    for (let cursor = 0; cursor < fin; cursor += 1) {
      const i = cola[cursor];
      const x = i % w;
      const y = (i - x) / w;
      if (x > 0) empujar(i - 1);
      if (x < w - 1) empujar(i + 1);
      if (y > 0) empujar(i - w);
      if (y < h - 1) empujar(i + w);
    }
    for (let i = 0; i < w * h; i += 1) mascara[i] = fondo[i] ? 0 : 255;
    detalle = 'contorno libre';
  }

  // Cierre morfológico + antialias del borde, en una sola pasada de desenfoque.
  // Ojo: sharp devuelve el raw de una máscara de 1 canal expandido a 3, así que
  // hay que leerlo con su stride y no de a un byte por píxel.
  const suavizada = await sharp(mascara, { raw: { width: w, height: h, channels: 1 } })
    .blur(redondo ? 1.2 : 3)
    .linear(redondo ? 1 : 3, redondo ? 0 : -200)
    .blur(1.1)
    .raw()
    .toBuffer({ resolveWithObject: true });
  const paso = suavizada.info.channels;
  const alfa = (i) => suavizada.data[i * paso];

  const rgba = Buffer.alloc(w * h * 4);
  let opacos = 0;
  for (let i = 0; i < w * h; i += 1) {
    const p = i * channels;
    rgba[i * 4] = data[p];
    rgba[i * 4 + 1] = data[p + 1];
    rgba[i * 4 + 2] = data[p + 2];
    rgba[i * 4 + 3] = alfa(i);
    if (alfa(i) > 8) opacos += 1;
  }

  const slug = slugDe(archivo);
  const salida = path.join(DESTINO, `${slug}.png`);
  const lista = sharp(rgba, { raw: { width: w, height: h, channels: 4 } })
    .trim({ threshold: 1 })
    .resize({ width: LADO, height: LADO, fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } });

  // El PNG es solo respaldo para navegadores sin webp: no hace falta a 900px.
  await lista
    .clone()
    .resize({ width: LADO_CHICO, height: LADO_CHICO, fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
    .png({ compressionLevel: 9, quality: 90 })
    .toFile(salida);
  await lista.clone().webp({ quality: 86, effort: 5, alphaQuality: 90 }).toFile(salida.replace(/\.png$/, '.webp'));
  // Variante chica: en la rueda cada plato se ve a ~215px, pedir 900 es tirar
  // medio megabyte de más en el hero, que es justo lo primero que carga.
  await lista
    .clone()
    .resize({ width: LADO_CHICO, height: LADO_CHICO, fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
    .webp({ quality: 84, effort: 5, alphaQuality: 88 })
    .toFile(salida.replace(/\.png$/, '-480.webp'));

  const cobertura = (opacos / (w * h)) * 100;
  console.log(`✓ ${archivo.normalize('NFC').padEnd(20)} → ${slug}  ${detalle.padEnd(16)} ocupa ${cobertura.toFixed(1)}%`);
  return { cobertura, slug };
}

await mkdir(DESTINO, { recursive: true });
const archivos = (await readdir(ORIGEN)).filter((f) => /\.(png|jpe?g)$/i.test(f));
if (archivos.length === 0) {
  console.log(`No hay fotos en ${ORIGEN}`);
  process.exit(0);
}
const resultados = [];
for (const archivo of archivos) resultados.push(await recortar(archivo));

const raras = resultados.filter((r) => r.cobertura < 15 || r.cobertura > 92).length;
console.log(`\n${archivos.length} fotos recortadas.`);
if (raras) console.log(`${raras} con ocupación rara: revisar que no se haya comido el plato ni dejado fondo.`);
