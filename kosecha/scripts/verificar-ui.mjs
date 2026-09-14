/**
 * Chequeo de humo del sitio con un navegador real.
 * Requiere el dev server corriendo (npm run dev) y chromium de Playwright.
 * Uso: npm run verificar   ·   CHROMIUM=/ruta/chrome npm run verificar
 */
import { mkdirSync } from 'node:fs';
import { chromium } from 'playwright';

const OUT = process.env.SHOTS_DIR ?? new URL('../capturas/', import.meta.url).pathname;
const SITIO = process.env.SITIO ?? 'http://127.0.0.1:5179/';
const fallos = [];
const ok = (cond, msg) => { console.log(`${cond ? 'PASS' : 'FAIL'}  ${msg}`); if (!cond) fallos.push(msg); };

const rotacionTotal = (page) => page.evaluate(() => {
  const ang = (el) => {
    const t = getComputedStyle(el).transform;
    if (!t || t === 'none') return 0;
    const m = new DOMMatrixReadOnly(t);
    return (Math.atan2(m.b, m.a) * 180) / Math.PI;
  };
  const rueda = document.querySelector('.rueda');
  const activo = document.querySelector('.rueda__bowl--activo');
  const radio = activo.closest('.rueda__radio');
  const total = ang(rueda) + ang(radio) + ang(activo);
  return { total: ((total % 360) + 360) % 360, activo: activo.getAttribute('aria-label') };
});

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ ...(process.env.CHROMIUM ? { executablePath: process.env.CHROMIUM } : {}), ignoreHTTPSErrors: true });

for (const [nombre, viewport] of [['movil', { width: 390, height: 844 }], ['desktop', { width: 1280, height: 900 }]]) {
  const page = await browser.newPage({ viewport, deviceScaleFactor: 2, ignoreHTTPSErrors: true });
  const errores = [];
  page.on('pageerror', (e) => errores.push(String(e)));
  page.on('console', (m) => { const t = m.text(); if (m.type() === 'error' && !t.includes('404') && !t.includes('ERR_CERT') && !t.includes('Failed to load resource')) errores.push(t); });
  await page.goto(SITIO, { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);

  // 1. sin scroll horizontal
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
  ok(overflow <= 1, `[${nombre}] sin scroll horizontal (desborde ${overflow}px)`);

  // 2. panel arranca con el primer platillo y su CTA
  const nombre0 = await page.textContent('.panel__nombre');
  const href0 = await page.getAttribute('.panel .btn-wa', 'href');
  ok(nombre0.includes('mediterránea'), `[${nombre}] panel inicia en Kosecha mediterránea (${nombre0})`);
  ok(href0.includes('wa.me/522761120304') && href0.includes('Kosecha%20mediterr%C3%A1nea'), `[${nombre}] CTA apunta al platillo activo con acentos codificados`);
  ok(decodeURIComponent(href0).includes('\n'), `[${nombre}] el mensaje conserva saltos de línea`);

  await page.screenshot({ path: `${OUT}/${nombre}-hero.png` });

  // 3. click en otro bowl → rota, cambia panel y CTA
  const bowls = page.locator('.rueda__bowl');
  await bowls.nth(3).click();
  await page.waitForTimeout(1000);
  const nombre1 = await page.textContent('.panel__nombre');
  const href1 = await page.getAttribute('.panel .btn-wa', 'href');
  ok(nombre1 !== nombre0, `[${nombre}] el panel cambió al hacer click (${nombre1})`);
  ok(href1 !== href0 && href1.includes(encodeURIComponent(nombre1).slice(0, 12)), `[${nombre}] el CTA cambió de destino con la rotación`);

  // 4. las fotos nunca quedan de cabeza (rotación compuesta ≈ 0)
  const r = await rotacionTotal(page);
  ok(r.total < 1 || r.total > 359, `[${nombre}] contrarrotación exacta: ${r.total.toFixed(2)}°`);

  // 5. teclado
  await page.locator('.rueda__bowl--activo').focus();
  await page.keyboard.press('ArrowRight');
  await page.waitForTimeout(1000);
  const nombre2 = await page.textContent('.panel__nombre');
  ok(nombre2 !== nombre1, `[${nombre}] flecha derecha gira un paso (${nombre2})`);
  const r2 = await rotacionTotal(page);
  ok(r2.total < 1 || r2.total > 359, `[${nombre}] contrarrotación exacta tras teclado: ${r2.total.toFixed(2)}°`);
  ok((await page.locator('.rueda__bowl--activo').evaluate((el) => el === document.activeElement)), `[${nombre}] el foco sigue al platillo activo`);

  // 6. camino más corto: del último al primero no da la vuelta larga
  const anguloAntes = await page.$eval('.rueda', (el) => parseFloat(el.style.getPropertyValue('--angulo')));
  await page.keyboard.press('ArrowLeft');
  await page.waitForTimeout(900);
  const anguloDespues = await page.$eval('.rueda', (el) => parseFloat(el.style.getPropertyValue('--angulo')));
  ok(Math.abs(anguloDespues - anguloAntes) <= 180.5, `[${nombre}] gira por el camino corto (${(anguloDespues - anguloAntes).toFixed(1)}°)`);

  // 7. secciones y CTAs
  const ctas = await page.locator('a.btn-wa').count();
  ok(ctas >= 10, `[${nombre}] hay CTA de WhatsApp en las tarjetas (${ctas} en total)`);
  for (const id of ['menu', 'nosotros', 'trazabilidad', 'ubicacion']) {
    ok(await page.locator(`#${id}`).count() === 1, `[${nombre}] existe la sección #${id}`);
  }
  const externos = await page.locator('a.btn-wa[target="_blank"]:not([rel*="noopener"])').count();
  ok(externos === 0, `[${nombre}] todos los CTA llevan rel="noopener"`);

  await page.locator('#menu').scrollIntoViewIfNeeded();
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${OUT}/${nombre}-menu.png` });
  await page.locator('#trazabilidad').scrollIntoViewIfNeeded();
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${OUT}/${nombre}-traza.png` });
  await page.screenshot({ path: `${OUT}/${nombre}-completa.png`, fullPage: true });

  ok(errores.length === 0, `[${nombre}] sin errores de consola${errores.length ? ': ' + errores[0] : ''}`);
  await page.close();
}

// reduced motion
const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' });
await page.goto(SITIO, { waitUntil: 'networkidle' });
await page.waitForTimeout(300);
const transicion = await page.$eval('.rueda', (el) => getComputedStyle(el).transitionDuration);
ok(parseFloat(transicion) < 0.05, `[reduced-motion] la rueda no anima (${transicion})`);
await page.locator('.rueda__bowl').nth(2).click();
await page.waitForTimeout(120);
const nombreRM = await page.textContent('.panel__nombre');
ok(nombreRM.includes('sureña') || nombreRM.includes('Sureña'), `[reduced-motion] el platillo cambia directo (${nombreRM})`);
await page.close();

await browser.close();
console.log(fallos.length ? `\n${fallos.length} FALLOS` : '\nTodo OK');
process.exit(fallos.length ? 1 : 0);
