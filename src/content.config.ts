import { defineCollection, reference, z } from "astro:content";
import { glob } from "astro/loaders";

/**
 * Content Collections = fuente de contenido autoadministrable (Fase 1.2).
 * El dueno del negocio edita ESTO desde Decap (/admin) sin tocar maquetado:
 * precios, disponibilidad, fotos, promos, FAQ. El schema valida cada campo,
 * asi que es fisicamente imposible romper el HTML/CSS al editar contenido.
 *
 * REGLA: aqui va contenido, no diseno. Ningun campo permite HTML/estilos
 * arbitrarios que puedan reventar el layout.
 */

const localized = z.object({
  es: z.string(),
  en: z.string().optional(), // en cae a es si falta (ver capa i18n)
});

/* --- PAQUETES DE VUELO ---------------------------------------------------- */
const packages = defineCollection({
  loader: glob({ pattern: "**/*.{md,mdx}", base: "./src/content/packages" }),
  schema: ({ image }) =>
    z.object({
      title: localized,
      // slug estable para URL/tracking; no cambia al renombrar el titulo.
      slug: z.string(),
      summary: localized,
      // Precio EDITABLE por el negocio. Moneda MXN por defecto. price=null
      // significa "consultar" (no inventamos cifras no confirmadas).
      priceFrom: z.number().nonnegative().nullable().default(null),
      currency: z.string().default("MXN"),
      priceNote: localized.optional(), // ej. "vuelo compartido, 2 personas"
      durationMinutes: z.number().int().positive().optional(),
      capacity: z
        .object({ min: z.number().int().positive(), max: z.number().int().positive() })
        .optional(),
      // Disponibilidad editable: el negocio activa/desactiva sin tocar codigo.
      available: z.boolean().default(true),
      highlights: z.array(localized).default([]),
      includes: z.array(localized).default([]),
      heroImage: image(),
      heroImageAlt: localized,
      gallery: z.array(z.object({ image: image(), alt: localized })).default([]),
      order: z.number().int().default(0),
      featured: z.boolean().default(false),
      draft: z.boolean().default(false),
    }),
});

/* --- FAQ (acordeon, citable por LLMs) ------------------------------------- */
const faq = defineCollection({
  loader: glob({ pattern: "**/*.{md,mdx}", base: "./src/content/faq" }),
  schema: z.object({
    question: localized,
    // Respuesta autocontenida en una idea clara (LLMO): que responda la
    // pregunta completa en si misma, no dispersa en fragmentos de UI.
    answer: localized,
    category: z.enum(["reservas", "vuelo", "clima", "pagos", "seguridad", "general"]),
    order: z.number().int().default(0),
    draft: z.boolean().default(false),
  }),
});

/* --- TESTIMONIOS / RESEÑAS ------------------------------------------------ */
const testimonials = defineCollection({
  loader: glob({ pattern: "**/*.{md,mdx}", base: "./src/content/testimonials" }),
  schema: z.object({
    author: z.string(),
    // Cuerpo corto (<= 3 lineas al renderizar). Snippet, no resena completa.
    quote: localized,
    rating: z.number().min(1).max(5).default(5),
    source: z.enum(["google", "tripadvisor", "directo"]).default("google"),
    date: z.coerce.date().optional(),
    order: z.number().int().default(0),
    draft: z.boolean().default(false),
  }),
});

/* --- PROMOCIONES ---------------------------------------------------------- */
const promos = defineCollection({
  loader: glob({ pattern: "**/*.{md,mdx}", base: "./src/content/promos" }),
  schema: z.object({
    title: localized,
    body: localized,
    ctaLabel: localized.optional(),
    ctaHref: z.string().optional(),
    // Ventana de vigencia opcional; fuera de rango la promo no se muestra.
    startsAt: z.coerce.date().optional(),
    endsAt: z.coerce.date().optional(),
    active: z.boolean().default(false),
    relatedPackage: reference("packages").optional(),
  }),
});

/* --- GALERIA ------------------------------------------------------------- */
const gallery = defineCollection({
  loader: glob({ pattern: "**/*.{md,mdx}", base: "./src/content/gallery" }),
  schema: ({ image }) =>
    z.object({
      image: image(),
      alt: localized, // alt = parte de la voz de marca, descriptivo real
      caption: localized.optional(),
      order: z.number().int().default(0),
      draft: z.boolean().default(false),
    }),
});

/* --- PAGINAS DE CONTENIDO LARGO (globopuerto, legal, etc.) ---------------- */
const pages = defineCollection({
  loader: glob({ pattern: "**/*.{md,mdx}", base: "./src/content/pages" }),
  schema: z.object({
    title: localized,
    description: localized,
    draft: z.boolean().default(false),
  }),
});

export const collections = { packages, faq, testimonials, promos, gallery, pages };
