/**
 * Genera los datos estructurados y los archivos de rastreo desde una sola
 * fuente: src/data/. Corre solo antes de cada build (`prebuild`).
 *
 * Escribe:
 *  - el bloque JSON-LD dentro de index.html, entre los marcadores seo:jsonld
 *  - public/sitemap.xml
 *  - public/robots.txt
 *
 * Por qué generarlo y no escribirlo a mano: el menú, los precios y el teléfono
 * ya viven en src/data. Copiarlos al head es garantizar que algún día digan
 * cosas distintas, y un schema que miente sobre el precio es peor que no tener
 * schema.
 *
 * Uso: npm run seo (o automático con npm run build)
 */
import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

import { categorias, platillos } from '../src/data/platillos.js';
import { contacto, mapa, quienesSomos } from '../src/data/contenido.js';
import { preguntas } from '../src/data/faq.js';
import { NUMERO_WHATSAPP_POR_DEFECTO, SITIO } from '../src/data/negocio.js';

const RAIZ = path.resolve(import.meta.dirname, '..');
const HOY = new Date().toISOString().slice(0, 10);

const NUMERO_WHATSAPP = process.env.VITE_WHATSAPP_NUMERO ?? NUMERO_WHATSAPP_POR_DEFECTO;
const telefono = `+${NUMERO_WHATSAPP.replace(/\D/g, '')}`;
const url = (camino = '') => `${SITIO}/${camino}`.replace(/\/$/, '/');

const seccionMenu = (categoria) => ({
  '@type': 'MenuSection',
  name: categoria.nombre,
  hasMenuItem: platillos
    .filter((p) => p.categoria === categoria.id)
    .map((p) => ({
      '@type': 'MenuItem',
      name: p.nombre,
      description: p.descripcion,
      offers: {
        '@type': 'Offer',
        price: String(p.precio),
        priceCurrency: 'MXN',
        availability: 'https://schema.org/InStock',
      },
      ...(p.imagen ? { image: url(p.imagen.replace(/^\//, '')) } : {}),
    })),
});

const restaurante = {
  '@context': 'https://schema.org',
  '@type': 'Restaurant',
  '@id': `${SITIO}/#kosecha`,
  name: 'Kosecha',
  alternateName: 'Kosecha Comida Sana',
  description:
    'Ensaladas, wraps y sándwiches saludables en Libres, Puebla. Se pide por WhatsApp. Verdes locales y fruta de temporada de productores de la región.',
  slogan: 'Comida Sana',
  url: url(),
  logo: url('assets/logo/kosecha.png'),
  image: [
    url('og.jpg'),
    url('assets/platillos/mediterranea.png'),
    url('assets/platillos/bosque.png'),
  ],
  telephone: telefono,
  priceRange: '$$',
  currenciesAccepted: 'MXN',
  servesCuisine: ['Comida saludable', 'Ensaladas', 'Sándwiches', 'Wraps'],
  address: {
    '@type': 'PostalAddress',
    addressLocality: 'Libres',
    addressRegion: 'Puebla',
    addressCountry: 'MX',
  },
  geo: {
    '@type': 'GeoCoordinates',
    latitude: contacto.coordenadas.lat,
    longitude: contacto.coordenadas.lng,
  },
  hasMap: mapa.ver,
  sameAs: [contacto.instagramUrl],
  areaServed: { '@type': 'City', name: 'Libres', containedInPlace: { '@type': 'State', name: 'Puebla' } },
  knowsLanguage: 'es-MX',
  potentialAction: {
    '@type': 'OrderAction',
    target: {
      '@type': 'EntryPoint',
      urlTemplate: `https://wa.me/${NUMERO_WHATSAPP}`,
      inLanguage: 'es-MX',
      actionPlatform: [
        'https://schema.org/DesktopWebPlatform',
        'https://schema.org/MobileWebPlatform',
      ],
    },
    deliveryMethod: 'https://schema.org/OnSitePickup',
  },
  hasMenu: {
    '@type': 'Menu',
    name: 'Menú Kosecha',
    inLanguage: 'es-MX',
    url: url('#menu'),
    hasMenuSection: categorias.map(seccionMenu),
  },
};

// Sin horarios confirmados no se declara openingHours: un horario inventado
// manda gente al local cuando está cerrado.
if (contacto.horarios) restaurante.openingHours = contacto.horarios;
if (contacto.direccion) restaurante.address.streetAddress = contacto.direccion;

const faq = {
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  '@id': `${SITIO}/#preguntas`,
  mainEntity: preguntas.map((p) => ({
    '@type': 'Question',
    name: p.pregunta,
    acceptedAnswer: { '@type': 'Answer', text: p.respuesta },
  })),
};

const pagina = {
  '@context': 'https://schema.org',
  '@type': 'WebPage',
  '@id': `${SITIO}/#pagina`,
  url: url(),
  name: 'Kosecha · Ensaladas y comida sana en Libres, Puebla',
  inLanguage: 'es-MX',
  about: { '@id': `${SITIO}/#kosecha` },
  primaryImageOfPage: url('og.jpg'),
  description: quienesSomos.parrafos[0].slice(0, 300),
};

const bloque = `    <script type="application/ld+json">
${JSON.stringify([restaurante, faq, pagina], null, 2)
  .split('\n')
  .map((l) => `      ${l}`)
  .join('\n')}
    </script>`;

const indice = path.join(RAIZ, 'index.html');
let html = await readFile(indice, 'utf8');
const inicio = html.indexOf('<!-- seo:jsonld:inicio');
const fin = html.indexOf('<!-- seo:jsonld:fin -->');
if (inicio === -1 || fin === -1) {
  throw new Error('Faltan los marcadores seo:jsonld en index.html');
}
html = `${html.slice(0, html.indexOf('-->', inicio) + 3)}\n${bloque}\n    ${html.slice(fin)}`;
await writeFile(indice, html);

await writeFile(
  path.join(RAIZ, 'public', 'sitemap.xml'),
  `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>${url()}</loc>
    <lastmod>${HOY}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
`,
);

await writeFile(
  path.join(RAIZ, 'public', 'robots.txt'),
  `User-agent: *
Allow: /

Sitemap: ${url('sitemap.xml')}
`,
);

const items = restaurante.hasMenu.hasMenuSection.reduce(
  (n, s) => n + (s.hasMenuItem?.length ?? 0),
  0,
);
console.log(`✓ JSON-LD en index.html: Restaurant (${items} platillos), FAQPage (${preguntas.length}), WebPage`);
console.log('✓ public/sitemap.xml y public/robots.txt');
if (!contacto.horarios) console.log('· sin horarios confirmados: no se declara openingHours');
if (!contacto.direccion) console.log('· sin dirección en texto: el schema lleva localidad y coordenadas');
