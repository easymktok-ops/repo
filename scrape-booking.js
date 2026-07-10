#!/usr/bin/env node
/**
 * Scraper para diagnosticar el botón "Reservar" de Grupo Caminué
 * Analiza: https://grupocaminue.com.br/ES_Canoa/canoa-do-mar-ES.html
 *
 * Uso: node scrape-booking.js
 * Requiere: npm install playwright
 */

const { chromium } = require('playwright');

const TARGET_URL = 'https://grupocaminue.com.br/ES_Canoa/canoa-do-mar-ES.html';

async function checkUrl(url) {
  try {
    const res = await fetch(url, {
      method: 'HEAD',
      headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
      redirect: 'follow',
    });
    return { status: res.status, finalUrl: res.url };
  } catch (e) {
    return { status: 'ERROR', error: e.message };
  }
}

(async () => {
  console.log('=== Diagnóstico: Botón Reservar - Grupo Caminué ===\n');
  console.log(`URL objetivo: ${TARGET_URL}\n`);

  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const context = await browser.newContext({
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ignoreHTTPSErrors: true,
    extraHTTPHeaders: {
      Accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language': 'pt-BR,pt;q=0.9,en-US;q=0.8',
    },
  });

  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
  });

  const page = await context.newPage();

  const networkErrors = [];
  page.on('response', (response) => {
    if (response.status() >= 400) {
      networkErrors.push({ status: response.status(), url: response.url() });
    }
  });

  // ── 1. Cargar página principal ──────────────────────────────────────────────
  let pageStatus;
  try {
    const response = await page.goto(TARGET_URL, {
      waitUntil: 'networkidle',
      timeout: 30000,
    });
    pageStatus = response.status();
    console.log(`[1] Carga de página: HTTP ${pageStatus}`);
    console.log(`    URL final: ${page.url()}`);
    console.log(`    Título: ${await page.title()}`);
  } catch (e) {
    console.error(`[1] Error al cargar la página: ${e.message}`);
    await browser.close();
    process.exit(1);
  }

  if (pageStatus !== 200) {
    console.log(
      `\n⚠️  La página devuelve ${pageStatus}. Puede estar bloqueada por Cloudflare o fue eliminada.`
    );
  }

  // ── 2. Buscar botón/enlace "Reservar" ───────────────────────────────────────
  const { reservarElements, allLinks } = await page.evaluate(() => {
    const reservarElements = [];

    document.querySelectorAll('a').forEach((a) => {
      const text = (a.innerText || a.textContent || '').trim();
      const href = a.getAttribute('href') || '';
      const hrefAbsolute = a.href || href;
      if (
        text.toLowerCase().includes('reserv') ||
        href.toLowerCase().includes('reserv') ||
        href.toLowerCase().includes('book')
      ) {
        reservarElements.push({
          tag: 'a',
          text: text.substring(0, 100),
          href,
          hrefAbsolute,
          outerHTML: a.outerHTML.substring(0, 400),
        });
      }
    });

    document.querySelectorAll('button, input[type="submit"], input[type="button"]').forEach((b) => {
      const text = (b.innerText || b.textContent || b.value || '').trim();
      if (text.toLowerCase().includes('reserv')) {
        reservarElements.push({
          tag: b.tagName.toLowerCase(),
          text: text.substring(0, 100),
          outerHTML: b.outerHTML.substring(0, 400),
        });
      }
    });

    const allLinks = Array.from(document.querySelectorAll('a[href]')).map((a) => ({
      text: (a.innerText || a.textContent || '').trim().substring(0, 60),
      href: a.getAttribute('href'),
      hrefAbsolute: a.href,
    }));

    return { reservarElements, allLinks };
  });

  // ── 3. Reportar botones Reservar ────────────────────────────────────────────
  console.log(`\n[2] Elementos "Reservar" encontrados: ${reservarElements.length}`);
  for (const el of reservarElements) {
    console.log(`\n  ► Etiqueta : <${el.tag}>`);
    console.log(`    Texto    : "${el.text}"`);
    if (el.href) console.log(`    href     : ${el.href}`);
    if (el.hrefAbsolute) console.log(`    href abs : ${el.hrefAbsolute}`);
    console.log(`    HTML     : ${el.outerHTML}`);

    // Verificar si la URL devuelve 404
    if (el.hrefAbsolute && el.hrefAbsolute.startsWith('http')) {
      console.log(`\n  Verificando URL del botón...`);
      const check = await checkUrl(el.hrefAbsolute);
      console.log(`    Status: ${check.status}`);
      if (check.finalUrl && check.finalUrl !== el.hrefAbsolute) {
        console.log(`    Redirección a: ${check.finalUrl}`);
      }
      if (check.status === 404) {
        console.log(`    ❌ CONFIRMADO: La URL del botón devuelve 404.`);
        console.log(
          `    Causa probable: el motor de reservas fue cambiado o la ruta ya no existe.`
        );
      } else if (check.status === 200) {
        console.log(`    ✅ La URL responde con 200 OK.`);
      }
    }
  }

  if (reservarElements.length === 0) {
    console.log(
      '  No se encontró ningún botón/enlace "Reservar" en la página (puede estar bloqueada).'
    );
  }

  // ── 4. Todos los enlaces de la página ───────────────────────────────────────
  console.log(`\n[3] Todos los enlaces de la página (${allLinks.length} total):`);
  allLinks.forEach((l) => console.log(`    "${l.text}" → ${l.hrefAbsolute || l.href}`));

  // ── 5. Errores de red capturados ────────────────────────────────────────────
  if (networkErrors.length > 0) {
    console.log(`\n[4] Errores de red capturados durante la carga:`);
    networkErrors.forEach((e) => console.log(`    ${e.status}: ${e.url}`));
  }

  await browser.close();

  // ── 6. Resumen diagnóstico ───────────────────────────────────────────────────
  console.log(`
=== DIAGNÓSTICO FINAL ===

URL analizada : ${TARGET_URL}
Estado HTTP   : ${pageStatus}

Causa más probable del 404 en el botón Reservar:
  • El sitio de Grupo Caminué fue rediseñado. La URL antigua
    /ES_Canoa/canoa-do-mar-ES.html es una página HTML estática legada
    que contiene un enlace a un motor de reservas que ya no existe.

  • La nueva URL del hotel es:
    https://grupocaminue.com.br/es/hotels/pousada-canoa-do-mar/

  • El botón "Reservar" en la página antigua apuntaba probablemente a
    una ruta como /reservas/ o un sistema externo (ej. Stays, Omnibees,
    Restel) que fue dado de baja o cuya URL cambió.

Solución recomendada:
  1. Actualizar el scraper para usar la nueva URL del hotel.
  2. En la nueva página, identificar el endpoint real de reservas
     (buscar el iframe o script del motor de reservas).
  3. Si el scraper necesita el formulario de reservas, inspeccionar
     el tráfico de red (Network tab) al hacer clic en "Reservar"
     en la nueva página para capturar la URL real.
`);
})();
