/**
 * Copy institucional de Kosecha.
 *
 * VERBATIM: los textos de `quienesSomos` y `trazabilidad` están transcriptos
 * tal cual del moodboard de marca. No reescribir ni "mejorar" — cualquier
 * cambio tiene que venir aprobado por la marca.
 */

export const quienesSomos = {
  titulo: 'Quiénes somos',
  parrafos: [
    '¡Hola! En Kosecha somos apasionados del wellness, la nutrición y el fitness. Nacimos en Libres con una meta clarísima: demostrarte que comer sano no es pasar hambre ni comer lechuga sin chiste, sino darle a tu cuerpo energía de mejor calidad para rendir al 100%.',
    'Como nutriólogos y amantes del ejercicio, nos tomamos muy en serio cada ingrediente. Diseñamos nuestro menú de la mano de expertos en salud para asegurarnos de que cada platillo tenga el balance perfecto de macros, sea súper nutritivo y, obvio, sepa deli. Además, nos encanta generar un impacto positivo en nuestra comunidad, así que hacemos equipo con productores locales para que todo sea fresco y con impacto social real.',
    'Esta es apenas nuestra primera sucursal de un proyecto enorme que estaremos expandiendo en los próximos años. Queremos que tener un healthy lifestyle sea súper accesible, delicioso y cercano para todos en Libres. ¡Listos para desarrollar tu mejor versión!',
  ],
};

export const trazabilidad = {
  titulo: 'Trazabilidad',
  intro: [
    'En Kosecha, la trazabilidad no es solo una palabra fancy: es el mapa completo de cómo cuidamos lo que llega a tu plato y el amor que le tenemos a nuestra tierra. Nos importa demasiado saber de dónde viene cada ingrediente y el impacto que dejamos en el camino.',
    'Así es como hacemos magia con causa:',
  ],
  puntos: [
    {
      id: 'verdes-locales',
      titulo: 'Verdes 100% locales',
      texto: 'Nuestras hojas frescas vienen directo de los huertos de la zona. Las elegimos personalmente cada semana en el tradicional Miércoles de Agrotianquis de La Estación.',
      acento: 'var(--kos-lima)',
    },
    {
      id: 'huevo-organico',
      titulo: 'Huevo orgánico de la región',
      texto: 'Apoyamos a productores locales que garantizan un proceso ético, fresco y lleno de proteina de la mejor calidad.',
      acento: 'var(--kos-amarillo)',
    },
    {
      id: 'temporada',
      titulo: 'Fruits & veggies de temporada',
      texto: 'Nos adaptamos al ritmo de la naturaleza. Cosechamos lo que la tierra da en su mejor momento, trabajando hombro a hombro con agricultores librenses.',
      acento: 'var(--kos-naranja)',
    },
    {
      id: 'lacteos-apicultura',
      titulo: 'Lácteos y apicultura con causa',
      texto: 'Compramos la miel y quesos directo a productores  de la región, apoyando la economía local y cuidando el ecosistema de nuestras abejas.',
      acento: 'var(--kos-amarillo)',
    },
    {
      id: 'cero-desperdicio',
      titulo: 'Cero desperdicio (Zero Waste)',
      texto: 'Colaboramos de la mano con un banco de alimentos local para darle un destino con sentido a nuestros excedentes y reducir al mínimo nuestra huella de desperdicio.',
      acento: 'var(--kos-lima)',
    },
  ],
  cierre: 'Saber qué comes, de dónde viene y a quién apoyas al elegirnos: eso sí es un verdadero win-win para ti y para Libres.',
};

/**
 * Datos de contacto. Fuente: documento MENÚ (Drive › Kosecha) y la ficha de
 * Google Maps del local.
 * PENDIENTE: dirección en texto y horarios — ver ASSETS-TODO.md.
 */
export const contacto = {
  ciudad: 'Libres, Puebla',
  cobertura: 'Cobertura en Libres, Puebla',
  whatsappVisible: '276 112 0304',
  instagram: '@kosecha_mx',
  instagramUrl: 'https://instagram.com/kosecha_mx',
  web: 'www.kosecha.fit',
  webUrl: 'https://www.kosecha.fit',
  direccion: 'Calle Galeana, esquina Av. 16 de Septiembre',
  codigoPostal: '73782',
  coordenadas: { lat: 19.4582082, lng: -97.6876632 },
};

/**
 * Horario del local, en la hora de Libres (America/Mexico_City).
 * `dias` usa el día de la semana en inglés porque es lo que devuelve Intl y lo
 * que pide schema.org; la etiqueta que se muestra va aparte.
 */
export const horarios = {
  zona: 'America/Mexico_City',
  tramos: [
    {
      etiqueta: 'Lunes a viernes',
      dias: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
      abre: '09:00',
      cierra: '17:00',
    },
    {
      etiqueta: 'Sábado y domingo',
      dias: ['Saturday', 'Sunday'],
      abre: null,
      cierra: null,
    },
  ],
};

/** Cómo se recibe el pedido. */
export const entrega = {
  domicilio: { disponible: true, desde: 25, texto: 'Entrega a domicilio desde $25' },
  sucursal: { disponible: true, texto: 'Pick up en sucursal' },
};

/** El local en Google Maps. El embed sale de la ficha del negocio. */
export const mapa = {
  embed:
    'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3761.9387319904467!2d-97.6876632!3d19.458208199999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85daadc86ff41d9d%3A0xfe727428e98a8d5d!2sKosecha!5e0!3m2!1ses!2smx!4v1789681895239!5m2!1ses!2smx',
  ver: `https://www.google.com/maps/search/?api=1&query=${contacto.coordenadas.lat},${contacto.coordenadas.lng}`,
  comoLlegar: `https://www.google.com/maps/dir/?api=1&destination=${contacto.coordenadas.lat},${contacto.coordenadas.lng}`,
};
