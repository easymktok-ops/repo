// Datos de servicios y precios — contenido REAL del cliente (lista de precios
// actual de thespamita.com). Los nombres de tratamiento se conservan tal cual.
// Nota de tipo de cambio se muestra por categoría.

export type Lang = 'es' | 'en';
type L = Record<Lang, string>;

export interface ServiceItem {
  name: string;          // igual en ambos idiomas en la lista original
  price: string;         // MXN
  duration?: string;
  desc?: L;
}
export interface ServiceCategory {
  id: string;
  slug: string;
  name: L;
  intro?: L;
  fragrances?: L;
  items: ServiceItem[];
}

const NOTE_FX: L = {
  es: 'Aceptamos dólares americanos; se toma según el tipo de cambio del día. Sujeto a cambios sin previo aviso.',
  en: 'We accept US dollars; taken according to the current exchange rate. Subject to change without notice.',
};

export const fxNote = NOTE_FX;

export const services: ServiceCategory[] = [
  {
    id: 'masajes', slug: 'masajes',
    name: { es: 'Masajes', en: 'Massages' },
    intro: {
      es: 'Nuestros masajes combinan técnicas que actúan sobre tus músculos y tus sentidos: un equilibrio entre la paz y la relajación que tu cuerpo necesita.',
      en: 'Our massages combine techniques that work on your muscles and your senses: a balance between peace and the relaxation your body needs.',
    },
    items: [
      { name: 'Hot stones', price: '$1,800', duration: '90 min' },
      { name: 'Deep tissue', price: '$1,200 / $1,600', duration: '60 / 90 min' },
      { name: 'Therapeutic', price: '$1,200 / $1,600', duration: '60 / 90 min' },
      { name: 'Sport', price: '$1,200 / $1,600', duration: '60 / 90 min' },
      { name: 'Relaxing', price: '$1,000 / $1,400', duration: '60 / 90 min' },
      { name: 'Mita', price: '$2,000', duration: '90 min' },
      { name: 'Reflexology', price: '$1,200 / $1,600', duration: '60 / 90 min' },
      { name: 'Prenatal', price: '$1,200 / $1,600', duration: '60 / 90 min' },
      { name: 'Lymphatic drainage', price: '$2,000', duration: '90 min' },
      { name: 'Shiatsu', price: '$2,000', duration: '90 min' },
    ],
  },
  {
    id: 'faciales', slug: 'faciales',
    name: { es: 'Faciales', en: 'Facials' },
    intro: {
      es: 'Facial diseñado para brindar una experiencia de relajación y confort a la piel, así como limpieza profunda, hidratación y cuidado anti-edad.',
      en: 'Facial designed to provide relaxation and comfort to the skin, as well as deep cleansing, hydration and anti-aging care.',
    },
    items: [
      { name: 'Deep cleanse', price: '$1,600', duration: '90 min' },
      { name: 'Anti age (African violets)', price: '$1,400', duration: '60 min' },
      { name: 'Mature skin (African violets)', price: '$1,400', duration: '60 min' },
      { name: 'Against pimples', price: '$1,400', duration: '60 min' },
      { name: 'Devitalized skin (Multivitamins)', price: '$1,400', duration: '60 min' },
      { name: 'After the sun (Aloe vera, tepezcohuite)', price: '$1,200', duration: '60 min' },
      { name: 'Mixed skin (Strawberry)', price: '$1,200', duration: '60 min' },
      { name: 'For men (Vetiver)', price: '$1,200', duration: '60 min' },
      { name: 'Blotchy skin (Rosehip)', price: '$1,600', duration: '60 min' },
      { name: 'Sagging skin (Iris of Florence)', price: '$1,400', duration: '60 min' },
      { name: 'Sensitive skin (Chamomile)', price: '$1,200', duration: '60 min' },
      { name: 'Relaxing facial (Lavender, white tea)', price: '$1,200', duration: '60 min' },
    ],
  },
  {
    id: 'exfoliacion', slug: 'exfoliacion-corporal',
    name: { es: 'Exfoliación corporal', en: 'Body scrub' },
    intro: {
      es: 'Para desintoxicar, eliminar células muertas, hidratar, suavizar, regenerar y activar la circulación.',
      en: 'To detoxify, remove dead cells, hydrate, soften, regenerate and boost circulation.',
    },
    items: [
      { name: 'Lavender (Relaxing)', price: '$1,000', duration: '60 min' },
      { name: 'Coconut (Moisturizing)', price: '$1,200', duration: '60 min' },
      { name: 'Mango (Antioxidant)', price: '$1,200', duration: '60 min' },
      { name: 'Coffee (Antioxidant)', price: '$1,000', duration: '60 min' },
      { name: 'Tropicalm (Antioxidant)', price: '$1,000', duration: '60 min' },
      { name: 'Chocolate (Antioxidant, moisturizing)', price: '$1,000', duration: '60 min' },
      { name: 'Honey yogurt (Deep hydration)', price: '$1,200', duration: '60 min' },
      { name: 'Oatmeal Shea Cream (Moisturizing, oxygenating)', price: '$1,400', duration: '60 min' },
      { name: 'Tepezcohuite (Soothing, hydrating)', price: '$1,000', duration: '60 min' },
      { name: 'Green Tea (Soothing, hydrating)', price: '$1,000', duration: '60 min' },
    ],
  },
  {
    id: 'corporales', slug: 'tratamientos-corporales',
    name: { es: 'Tratamientos corporales', en: 'Body treatments' },
    intro: {
      es: 'Para embellecer, aromatizar, suavizar, hidratar, regenerar, relajar y refrescar el cuerpo.',
      en: 'To beautify, scent, soften, hydrate, regenerate, relax and refresh the body.',
    },
    items: [
      { name: 'Tepezcohuite (After the sun)', price: '$1,300', duration: '80 min' },
      { name: 'Honey yogurt', price: '$1,400', duration: '90 min' },
      { name: 'Chocolate (Antioxidant, hydration)', price: '$1,400', duration: '80 min' },
      { name: 'Mango (Hydration)', price: '$1,400', duration: '90 min' },
      { name: 'Tropical — mango, pineapple, coffee (Detoxifying)', price: '$1,400', duration: '80 min' },
      { name: 'Green Tea (Antioxidant, vitamins, minerals)', price: '$1,300', duration: '80 min' },
      { name: 'Tepezcohuite (Regenerating, moisturizing)', price: '$1,300', duration: '80 min' },
      { name: 'Egyptian roses (Deep hydration)', price: '$1,400', duration: '80 min' },
      { name: 'Lavender (Relaxing)', price: '$1,300', duration: '80 min' },
    ],
  },
  {
    id: 'depilacion', slug: 'depilacion',
    name: { es: 'Depilación', en: 'Waxing' },
    items: [
      { name: 'Bikini', price: '$400', duration: '25 min' },
      { name: 'Brazilian', price: '$800', duration: '35 min' },
      { name: 'Half leg', price: '$600', duration: '40 min' },
      { name: 'Full leg', price: '$800', duration: '55 min' },
      { name: 'Armpit', price: '$300', duration: '15 min' },
      { name: 'Above the lip', price: '$180', duration: '15 min' },
      { name: 'Eyebrow (cleaning)', price: '$200', duration: '20 min' },
      { name: 'Eyebrow (design)', price: '$400', duration: '35 min' },
      { name: 'Back', price: '$600', duration: '40 min' },
      { name: 'Face', price: '$800', duration: '40 min' },
      { name: 'Nose', price: '$180', duration: '15 min' },
      { name: 'Ears', price: '$180', duration: '15 min' },
    ],
  },
  {
    id: 'mani-pedi', slug: 'manicure-y-pedicure',
    name: { es: 'Manicure y pedicure', en: 'Manicure and pedicure' },
    fragrances: {
      es: 'Fragancias: manzanilla, lavanda, mojito cubano, cítricos, té verde, chocolate, maracuyá.',
      en: 'Fragrances: chamomile, lavender, Cuban mojito, citrus, green tea, chocolate, passion fruit.',
    },
    items: [
      {
        name: 'Classic pedicure', price: '$500', duration: '45 min',
        desc: {
          es: 'Lavado de pies con sales marinas y champú, corte y diseño de uñas, eliminación de callos y cutículas, bálsamo y esmalte clásico.',
          en: 'Foot wash with sea salts and shampoo, nail cutting and design, callus and cuticle removal, balm and classic enamel.',
        },
      },
      {
        name: 'Spa pedicure', price: '$800', duration: '60 min',
        desc: {
          es: 'Lavado con sales marinas y champú, limpieza profunda, eliminación de callos y cutículas, corte y diseño, exfoliación, mascarilla, bálsamo y esmalte clásico.',
          en: 'Sea-salt wash, deep cleaning, callus and cuticle removal, cutting and design, exfoliation, mask, balm and classic enamel.',
        },
      },
      {
        name: 'Deluxe pedicure', price: '$1,200', duration: '90 min',
        desc: {
          es: 'Reflexología 20 min, lavado con sales marinas, corte y diseño, limpieza profunda, eliminación de callos y cutículas, bálsamo y esmalte clásico o gelish.',
          en: 'Reflexology 20 min, sea-salt wash, cutting and design, deep cleaning, callus and cuticle removal, balm and classic or gel enamel.',
        },
      },
    ],
  },
  {
    id: 'ninos', slug: 'ninos',
    name: { es: 'Niños', en: 'Kids' },
    intro: { es: 'Menores de 11 años.', en: 'Under 11 years old.' },
    items: [
      { name: 'Manicure & pedicure', price: '$600', duration: '60 min' },
      { name: 'Manicure', price: '$300', duration: '30 min' },
      { name: 'Pedicure', price: '$400', duration: '35 min' },
      { name: 'Nail polish', price: '$160', duration: '20 min' },
      { name: 'Facial', price: '$600', duration: '35 min' },
      { name: 'Massage', price: '$700', duration: '40 min' },
    ],
  },
  {
    id: 'mas', slug: 'mas-servicios',
    name: { es: 'Más servicios', en: 'More services' },
    items: [
      { name: 'Reflexology', price: '$600', duration: '30 min' },
      { name: 'Nail polish remover', price: '$140', duration: '20 min' },
      { name: 'Regular nail polish', price: '$140', duration: '20 min' },
      { name: 'Gel nail polish', price: '$300', duration: '35 min' },
      { name: 'French nail polish', price: '$300', duration: '40 min' },
      { name: 'Nail polish hardener', price: '$140', duration: '10 min' },
    ],
  },
];
