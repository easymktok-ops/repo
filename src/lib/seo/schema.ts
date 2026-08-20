import { site, absoluteUrl, type Locale } from "@config/site";

/**
 * Constructores de JSON-LD. Datos reales del negocio. El nodo de negocio lleva
 * @id estable para que otros nodos (ofertas, reseñas) puedan referenciarlo.
 */

const businessId = `${site.url}/#business`;

/** Nodo de negocio (TravelAgency + LocalBusiness). Va en todas las paginas. */
export function businessSchema() {
  const c = site.brand;
  return {
    "@type": ["TravelAgency", "LocalBusiness"],
    "@id": businessId,
    name: site.name,
    url: site.url,
    image: absoluteUrl("/og/og-default.jpg"),
    logo: absoluteUrl("/favicon.svg"),
    telephone: `+${c.phone}`,
    email: c.email,
    priceRange: "$$",
    address: {
      "@type": "PostalAddress",
      streetAddress: c.address.line1,
      addressLocality: "San Martín de las Pirámides",
      addressRegion: "Estado de México",
      postalCode: "55850",
      addressCountry: "MX",
    },
    areaServed: "Teotihuacán, Estado de México",
    aggregateRating: {
      "@type": "AggregateRating",
      ratingValue: String(c.reviews.rating),
      reviewCount: String(c.reviews.count),
      bestRating: "5",
    },
  };
}

/** Nodo WebSite. */
export function websiteSchema() {
  return {
    "@type": "WebSite",
    "@id": `${site.url}/#website`,
    url: site.url,
    name: site.name,
    inLanguage: ["es-MX", "en"],
    publisher: { "@id": businessId },
  };
}

/** Grafo global para BaseLayout. */
export function siteGraph() {
  return {
    "@context": "https://schema.org",
    "@graph": [businessSchema(), websiteSchema()],
  };
}

interface PkgForSchema {
  name: string;
  description: string;
  price: number | null;
  currency: string;
  imageUrl: string; // absoluta
  url: string; // absoluta
}

/** ItemList de Product (para la pagina de catalogo /vuelos). */
export function catalogSchema(packages: PkgForSchema[]) {
  const c = site.brand;
  return {
    "@context": "https://schema.org",
    "@type": "ItemList",
    itemListElement: packages.map((p, i) => ({
      "@type": "ListItem",
      position: i + 1,
      item: {
        "@type": "Product",
        name: p.name,
        description: p.description,
        image: p.imageUrl,
        url: p.url,
        brand: { "@type": "Brand", name: site.name },
        ...(p.price != null
          ? {
              offers: {
                "@type": "Offer",
                price: String(p.price),
                priceCurrency: p.currency,
                availability: "https://schema.org/InStock",
                url: p.url,
              },
            }
          : {}),
        aggregateRating: {
          "@type": "AggregateRating",
          ratingValue: String(c.reviews.rating),
          reviewCount: String(c.reviews.count),
          bestRating: "5",
        },
      },
    })),
  };
}

export type { Locale };
