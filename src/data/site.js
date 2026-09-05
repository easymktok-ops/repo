/**
 * Datos centrales del sitio Happy Puerto (Fase 1).
 * Fuente única de verdad para contacto, paquetes, políticas y redes.
 * NO agregar destinos/URLs no validados por el cliente.
 */

// Prefijo de assets respetando el BASE_PATH del build (domain-agnostic).
const BASE = import.meta.env.BASE_URL || '/';
export const asset = (p) => `${BASE}${p}`.replace(/\/{2,}/g, '/');

// ── Contacto (único canal de cierre = WhatsApp) ───────────────────
export const contact = {
  whatsappPhone: '525656531771', // +52 56 56531771 (dato del cliente ✓)
  whatsappDisplay: '+52 56 5653 1771',
  // ⚠️ CONFIRMAR CON EL CLIENTE: el brief pide mostrar un email pero no dio uno.
  // Este es un placeholder — reemplazar por el correo real antes de publicar.
  email: 'hola@happypuerto.com',
  address: 'Camino Campestre S/N, San Martín de las Pirámides, Estado de México',
  mapsQuery: 'Camino Campestre S/N, San Martín de las Pirámides',
};

/**
 * Construye un link de WhatsApp con mensaje personalizado por paquete.
 * Usado tanto en Astro como en el island React (misma lógica).
 */
export function buildWALink(packageName, price, persons, date) {
  const phone = contact.whatsappPhone;
  const per = persons > 1 ? 's' : '';
  const priceTxt = typeof price === 'number' ? `$${price.toLocaleString('es-MX')}` : price;
  const msg = `Hola 😊 Me interesa el *Paquete ${packageName}* (${priceTxt} MXN) para *${persons} persona${per}* en una fecha aproximada de *${date || 'por definir'}*. ¿Tienen disponibilidad?`;
  return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
}

/** Link genérico de WhatsApp (CTAs de "Escríbenos"). */
export function waGeneral(text) {
  const msg = text || 'Hola 😊 Me gustaría más información sobre sus vuelos en globo sobre Teotihuacán. ¿Me ayudan?';
  return `https://wa.me/${contact.whatsappPhone}?text=${encodeURIComponent(msg)}`;
}

// ── Paquetes (6) ──────────────────────────────────────────────────
// accent = color terciario asignado para variar el look (tipo Starface).
// span    = ancho en la grid editorial asimétrica de desktop (de 12).
export const packages = [
  {
    id: 'smile',
    name: 'SMILE',
    emoji: '🎈',
    price: 2199,
    tagline: 'El clásico feliz para estrenarte en el cielo.',
    includes: [
      'Coffee Break',
      'Vuelo 35–50 min',
      'Certificado de vuelo',
      'Lona especial',
      'Seguro incluido',
      'Brindis',
    ],
    accent: '#F4B400',
    span: 'lg:col-span-8',
    badge: 'Más elegido',
  },
  {
    id: 'happy',
    name: 'HAPPY',
    emoji: '😄',
    price: 2399,
    tagline: 'Vuelo + experiencias culturales para un día completo.',
    includes: [
      'Todo lo de Smile',
      'Desayuno bufet',
      'Taller de Obsidiana y Maguey',
      'Degustación de pulque',
      'Taller Danzante',
    ],
    accent: '#6FD8A4',
    span: 'lg:col-span-4',
  },
  {
    id: 'cool',
    name: 'COOL',
    emoji: '😎',
    price: 2799,
    tagline: 'Sin preocuparte por el traslado: nosotros te llevamos.',
    includes: [
      'Todo lo de Happy',
      'Transporte a las Pirámides',
      'Transporte redondo CDMX–Teotihuacán',
    ],
    accent: '#7FC7E8',
    span: 'lg:col-span-4',
  },
  {
    id: 'star',
    name: 'STAR',
    emoji: '⭐',
    price: 2999,
    tagline: 'La experiencia estrella: desayuno en cueva y traslados.',
    includes: [
      'Coffee Break',
      'Vuelo 35–50 min',
      'Certificado · Lona · Seguro · Brindis',
      'Desayuno en cueva',
      'Transporte a las Pirámides',
      'Transporte redondo CDMX',
    ],
    accent: '#FF8A5B',
    span: 'lg:col-span-4',
  },
  {
    id: 'big-happy',
    name: 'BIG HAPPY',
    emoji: '🌟',
    price: 3199,
    tagline: 'Todo incluido + acceso a la Zona Arqueológica.',
    includes: [
      'Todo lo de Star',
      'Entrada a Zona Arqueológica de Teotihuacán',
    ],
    accent: '#B98BD6',
    span: 'lg:col-span-8',
    badge: 'Más completo',
  },
  {
    id: 'love',
    name: 'LOVE',
    emoji: '💛',
    price: 12000,
    tagline: 'Globo PRIVADO para 2. El cielo, solo de ustedes.',
    includes: [
      'Globo privado para 2 personas',
      'Coffee Break',
      'Vuelo 35–50 min',
      'Certificado · Lona · Seguro · Brindis',
      'Desayuno en cueva',
    ],
    accent: '#FF6FA5',
    span: 'lg:col-span-12',
    variant: 'love',
    image: 'assets/ocasiones/ocasion-pedida.jpg',
    imageAlt: 'Pareja celebrando una pedida de mano frente al globo de Happy Puerto',
  },
];

// ── Trust bar ─────────────────────────────────────────────────────
export const trustItems = [
  { icon: 'calendar', label: '7+ años volando' },
  { icon: 'shield', label: 'Certificación AFAC' },
  { icon: 'umbrella', label: 'Seguro incluido' },
  { icon: 'cheers', label: 'Brindis' },
  { icon: 'clock', label: 'Vuelo 35–50 min' },
];

// ── Cómo funciona ─────────────────────────────────────────────────
export const steps = [
  {
    n: 1,
    title: 'Reservas por WhatsApp',
    text: 'Eliges tu paquete y nos escribes. Te confirmamos disponibilidad y apartas tu fecha.',
    image: 'assets/galeria/galeria-01.jpg',
  },
  {
    n: 2,
    title: 'Punto de reunión 5:30 AM',
    text: 'Nos vemos antes del amanecer en San Martín de las Pirámides. Café calientito de bienvenida.',
    image: 'assets/galeria/galeria-04.jpg',
  },
  {
    n: 3,
    title: 'Vuela sobre Teotihuacán',
    text: 'Despegamos con el sol. 35 a 50 minutos flotando sobre las pirámides y el valle.',
    image: 'assets/galeria/galeria-03.jpg',
  },
  {
    n: 4,
    title: 'Brindis + certifícate',
    text: 'Aterrizamos, brindamos por la aventura y te entregamos tu certificado de vuelo.',
    image: 'assets/galeria/galeria-07.jpg',
  },
];

// ── Galería (fotos del cliente) ───────────────────────────────────
export const gallery = [
  { src: 'assets/galeria/galeria-02.jpg', alt: 'Globo Happy Puerto elevándose al amanecer', tall: true },
  { src: 'assets/galeria/galeria-01.jpg', alt: 'Vista del valle de Teotihuacán desde el globo' },
  { src: 'assets/galeria/galeria-03.jpg', alt: 'Globos multicolor sobre las pirámides' },
  { src: 'assets/galeria/galeria-06.jpg', alt: 'Pasajeros disfrutando el vuelo', tall: true },
  { src: 'assets/galeria/galeria-04.jpg', alt: 'Inflado del globo antes del despegue' },
  { src: 'assets/ocasiones/ocasion-cumpleanos.jpg', alt: 'Celebración de cumpleaños en globo' },
  { src: 'assets/galeria/galeria-07.jpg', alt: 'Canasta del globo lista para volar', tall: true },
  { src: 'assets/ocasiones/ocasion-aniversario.jpg', alt: 'Aniversario celebrado sobre Teotihuacán' },
  { src: 'assets/galeria/galeria-05.jpg', alt: 'Amanecer con decenas de globos en el cielo', tall: true },
  { src: 'assets/ocasiones/ocasion-pedida.jpg', alt: 'Pedida de mano frente al globo carita feliz' },
];

// ── Reseñas (PLACEHOLDER Fase 1) ──────────────────────────────────
// TODO Fase 2: reemplazar por reseñas reales (Google / Viator) vía API o feed.
export const reviews = [
  {
    name: 'María F.',
    initial: 'M',
    stars: 5,
    text: 'Una experiencia inolvidable. El equipo súper atento y la vista de las pirámides al amanecer no tiene comparación. ¡Volvería mil veces!',
  },
  {
    name: 'Carlos R.',
    initial: 'C',
    stars: 5,
    text: 'Reservamos por WhatsApp en minutos. Todo puntual, seguro y muy divertido. El brindis al final fue el broche perfecto.',
  },
  {
    name: 'Ana & Diego',
    initial: 'A',
    stars: 5,
    text: 'Elegimos el globo privado para nuestro aniversario. Mágico de principio a fin. Gracias Happy Puerto por hacerlo especial.',
  },
];
// Placeholder — link real de Viator pendiente para Fase 2.
export const viatorUrl = null; // TODO Fase 2: URL real de reseñas Viator.

// ── Políticas (accordion) ─────────────────────────────────────────
export const policies = [
  {
    id: 'vuelo',
    title: 'Restricciones de vuelo',
    body: [
      'No pueden abordar personas con problemas cardíacos, mujeres embarazadas ni personas bajo efectos de alcohol o sustancias.',
      'Menores de edad solo con autorización y acompañados de un adulto responsable.',
      'Es indispensable presentarse puntual al punto de reunión; el vuelo depende de la ventana de amanecer.',
    ],
  },
  {
    id: 'clima',
    title: 'Restricciones climáticas',
    body: [
      'El vuelo depende de las condiciones del clima y de la autoridad aeronáutica.',
      'Si por seguridad no es posible volar, se reagenda tu vuelo sin costo adicional en la siguiente fecha disponible.',
    ],
  },
  {
    id: 'cancelacion',
    title: 'Política de cancelación',
    body: [
      'Cancelaciones con 48 horas o más de anticipación: sin cargo.',
      'Cancelaciones con menos de 24 horas: cargo del 30% del total.',
    ],
  },
  {
    id: 'pago',
    title: 'Formas de pago',
    body: [
      'Efectivo y transferencia bancaria: sin cargo adicional.',
      'Tarjeta de crédito/débito: +5% de comisión.',
      'American Express: +6% de comisión.',
    ],
  },
  {
    id: 'extras',
    title: 'Cargos extra',
    body: [
      'Pasajeros con peso mayor a 100 kg: $30 MXN por cada kg adicional.',
    ],
  },
];

// ── Redes sociales (SVG lineales, sin emojis) ─────────────────────
export const socials = [
  { id: 'instagram', label: '@happypuerto_mexico', url: 'https://instagram.com/happypuerto_mexico' },
  { id: 'instagram', label: '@happypuerto', url: 'https://instagram.com/happypuerto' },
  { id: 'facebook', label: 'Facebook', url: 'https://www.facebook.com/share/1GTvPr1acr/' },
  { id: 'tiktok', label: '@happypuertomx', url: 'https://www.tiktok.com/@happypuertomx' },
];

// Texto de marca / SEO
export const siteMeta = {
  name: 'Happy Puerto',
  title: 'Happy Puerto · Vuelos en globo sobre Teotihuacán',
  description:
    'La experiencia más feliz del cielo mexicano. Vuela en globo aerostático al amanecer sobre las pirámides de Teotihuacán. Reserva por WhatsApp.',
  tagline: 'La experiencia más feliz del cielo mexicano.',
};
