/**
 * Auditoría técnica: contraste real de cada texto contra su fondo, tamaño de
 * los blancos de click y desborde horizontal en 10 anchos.
 * Requiere el dev server en 5179 (npx vite --port 5179).
 * Uso: npm run auditar   ·   CHROMIUM=/ruta/chrome npm run auditar
 */
import { chromium } from 'playwright';
const SITIO = process.env.SITIO ?? 'http://127.0.0.1:5179/';
const b = await chromium.launch(process.env.CHROMIUM ? { executablePath: process.env.CHROMIUM } : {});

// --- contraste y touch targets a 1280 y 390 ---
for (const [etiqueta, vp] of [['desktop', { width: 1280, height: 900 }], ['movil', { width: 390, height: 844 }]]) {
  const p = await b.newPage({ viewport: vp, ignoreHTTPSErrors: true });
  await p.goto(SITIO, { waitUntil: 'networkidle' });
  await p.waitForTimeout(500);
  const r = await p.evaluate(() => {
    const lum = (c) => {
      const [r, g, bl] = c.map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; });
      return 0.2126 * r + 0.7152 * g + 0.0722 * bl;
    };
    const parse = (s) => {
      const n = (s.match(/[\d.]+/g) || []).map(Number);
      // color-mix() se resuelve como color(srgb 0.95 0.91 0.8 / 0.8): fracciones.
      if (s.startsWith('color(')) return [n[0] * 255, n[1] * 255, n[2] * 255, n[3] ?? 1];
      return n;
    };
    const mezcla = (fg, bg) => { const a = fg[3] ?? 1; return [0, 1, 2].map((i) => fg[i] * a + bg[i] * (1 - a)); };
    const fondoDe = (el) => {
      let n = el;
      while (n && n !== document.documentElement) {
        const c = parse(getComputedStyle(n).backgroundColor);
        if (c.length >= 3 && (c[3] === undefined || c[3] > 0.95)) return c.slice(0, 3);
        n = n.parentElement;
      }
      return [255, 255, 255];
    };
    const ratio = (a, b2) => { const [x, y] = [lum(a) + 0.05, lum(b2) + 0.05].sort((m, n) => n - m); return x / y; };

    const contrastes = [];
    const chicos = [];
    for (const el of document.querySelectorAll('body *')) {
      const cs = getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden') continue;
      const texto = [...el.childNodes].filter((n) => n.nodeType === 3 && n.textContent.trim()).map((n) => n.textContent.trim()).join(' ');
      if (texto) {
        const bg = fondoDe(el);
        const fg = mezcla(parse(cs.color), bg);
        const px = parseFloat(cs.fontSize);
        const peso = parseInt(cs.fontWeight, 10) || 400;
        const grande = px >= 24 || (px >= 18.66 && peso >= 700);
        const c = ratio(fg, bg);
        const min = grande ? 3 : 4.5;
        if (c < min) contrastes.push({ sel: el.className || el.tagName, texto: texto.slice(0, 40), px, peso, ratio: +c.toFixed(2), min });
      }
      if (el.matches('a, button, [role="button"], input, select')) {
        const caja = el.getBoundingClientRect();
        if (caja.width > 0 && (caja.width < 44 || caja.height < 44)) {
          chicos.push({ sel: el.className || el.tagName, w: Math.round(caja.width), h: Math.round(caja.height), txt: el.textContent.trim().slice(0, 24) });
        }
      }
    }
    const headings = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].map((h) => `${h.tagName} ${h.textContent.trim().slice(0, 34)}`);
    const sinDim = [...document.querySelectorAll('img')].filter((i) => !i.getAttribute('width') || !i.getAttribute('height')).length;
    return { contrastes, chicos, headings, sinDim, landmarks: [...document.querySelectorAll('header,nav,main,footer,aside,section')].length };
  });
  console.log(`\n===== ${etiqueta} =====`);
  console.log('CONTRASTE bajo mínimo:', r.contrastes.length);
  for (const c of r.contrastes) console.log(`  ${c.ratio} < ${c.min}  ${c.px}px/${c.peso}  .${String(c.sel).split(' ')[0]}  "${c.texto}"`);
  console.log('TOUCH TARGETS < 44px:', r.chicos.length);
  for (const c of r.chicos.slice(0, 12)) console.log(`  ${c.w}x${c.h}  .${String(c.sel).split(' ')[0]}  "${c.txt}"`);
  if (etiqueta === 'desktop') console.log('HEADINGS:', r.headings.join(' | '));
  console.log('img sin dimensiones:', r.sinDim);
  await p.close();
}

// --- overflow a varios anchos ---
const p2 = await b.newPage({ ignoreHTTPSErrors: true });
for (const w of [320, 360, 390, 430, 600, 768, 1024, 1280, 1440, 1920]) {
  await p2.setViewportSize({ width: w, height: 900 });
  await p2.goto(SITIO, { waitUntil: 'domcontentloaded' });
  await p2.waitForTimeout(250);
  const d = await p2.evaluate(() => {
    const desborde = document.documentElement.scrollWidth - window.innerWidth;
    const culpables = [...document.querySelectorAll('body *')].filter((el) => el.getBoundingClientRect().right > window.innerWidth + 1).map((el) => `${el.tagName}.${String(el.className).split(' ')[0]}`).slice(0, 4);
    return { desborde, culpables };
  });
  console.log(`ancho ${w}: desborde ${d.desborde}px ${d.desborde > 1 ? JSON.stringify(d.culpables) : ''}`);
}
await b.close();
