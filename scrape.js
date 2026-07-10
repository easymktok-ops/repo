const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({
    executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome',
    headless: false,
    args: [
      '--no-sandbox', '--disable-setuid-sandbox', '--no-proxy-server',
      '--disable-blink-features=AutomationControlled',
    ]
  });

  const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ignoreHTTPSErrors: true,
    extraHTTPHeaders: {
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
      'Accept-Language': 'pt-BR,pt;q=0.9,en-US;q=0.8',
    }
  });

  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
  });

  const page = await context.newPage();
  const failedRequests = [];

  page.on('response', response => {
    if (response.status() >= 400) {
      failedRequests.push({ url: response.url(), status: response.status() });
    }
  });

  const url = 'https://grupocaminue.com.br/ES_Canoa/canoa-do-mar-ES.html';
  console.log(`Loading: ${url}`);
  
  try {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    console.log('HTTP Status:', response.status());
    console.log('Final URL:', page.url());
    await page.waitForTimeout(4000);
    const title = await page.title();
    console.log('Title:', title);

    const content = await page.content();
    console.log('\n--- Page HTML (first 3000 chars) ---');
    console.log(content.substring(0, 3000));

    const reservarLinks = await page.evaluate(() => {
      const found = [];
      document.querySelectorAll('a').forEach(a => {
        const text = (a.innerText || a.textContent || '').trim();
        const href = a.href || a.getAttribute('href') || '';
        if (text.toLowerCase().includes('reserv') || href.toLowerCase().includes('reserv') || href.toLowerCase().includes('book')) {
          found.push({ type: 'link', text: text.substring(0, 100), href, outerHTML: a.outerHTML.substring(0, 300) });
        }
      });
      document.querySelectorAll('button').forEach(b => {
        const text = (b.innerText || b.textContent || '').trim();
        if (text.toLowerCase().includes('reserv')) {
          found.push({ type: 'button', text: text.substring(0, 100), outerHTML: b.outerHTML.substring(0, 300) });
        }
      });
      // Also get ALL links
      const allLinks = Array.from(document.querySelectorAll('a[href]')).map(a => ({
        text: (a.innerText || a.textContent || '').trim().substring(0, 60),
        href: a.href
      }));
      return { reservarLinks: found, allLinks };
    });

    console.log('\n=== RESERVAR ELEMENTS ===');
    console.log(JSON.stringify(reservarLinks.reservarLinks, null, 2));
    console.log('\n=== ALL LINKS ===');
    reservarLinks.allLinks.forEach(l => console.log(`  "${l.text}" -> ${l.href}`));

  } catch (e) {
    console.error('Error:', e.message);
  }

  if (failedRequests.length > 0) {
    console.log('\n=== 4xx/5xx ERRORS ===');
    failedRequests.forEach(r => console.log(`  ${r.status}: ${r.url}`));
  }

  await browser.close();
})();
