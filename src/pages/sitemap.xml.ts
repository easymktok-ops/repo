import type { APIRoute } from 'astro';
import { BASE } from '../lib/paths';

const SITE = 'https://thespamita.com';
const paths = ['', 'servicios', 'nosotros', 'galeria', 'contacto'];

export const GET: APIRoute = () => {
  const urls = paths
    .map((p) => {
      const es = `${SITE}${BASE}/es/${p}`.replace(/\/+$/, '/');
      const en = `${SITE}${BASE}/en/${p}`.replace(/\/+$/, '/');
      return [es, en]
        .map(
          (loc) => `  <url>
    <loc>${loc}</loc>
    <xhtml:link rel="alternate" hreflang="es" href="${es}"/>
    <xhtml:link rel="alternate" hreflang="en" href="${en}"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="${es}"/>
  </url>`
        )
        .join('\n');
    })
    .join('\n');

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
${urls}
</urlset>`;

  return new Response(xml, { headers: { 'Content-Type': 'application/xml' } });
};
