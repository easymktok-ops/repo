// Diccionario bilingüe (ES/EN). El copy de marca es TEXTO REAL del cliente:
// no se reescribe, sólo se adapta al layout donde hace falta.

export const languages = { es: 'Español', en: 'English' } as const;
export const defaultLang = 'es';
export type Lang = keyof typeof languages;

export const ui = {
  es: {
    // --- Navegación ---
    'nav.inicio': 'Inicio',
    'nav.servicios': 'Servicios',
    'nav.nosotros': 'Nosotros',
    'nav.galeria': 'Galería',
    'nav.contacto': 'Contacto',
    'nav.reservar': 'Reservar cita',

    // --- Menú lateral (pie) ---
    'side.tagline': 'Spa de lujo en Punta de Mita',
    'side.horario.label': 'Horario',
    'side.horario.valor': 'Lun–Dom · 9:00–20:00',
    'side.tel.label': 'Contacto',

    // --- Hero ---
    'hero.eyebrow': 'Punta de Mita · Nayarit',
    'hero.title': 'Un día para resaltar tu belleza y liberar el estrés',
    'hero.subtitle': 'Experiencias de spa personalizadas en el corazón de Bahía de Banderas.',
    'hero.cta': 'Reservar cita',
    'hero.cta2': 'Escríbenos por WhatsApp',

    // --- Intro (copy de marca) ---
    'intro.eyebrow': 'The Spa',
    'intro.p1': 'En Bahía de Banderas hay un lugar donde tus sentidos se agudizan, acompañado por las hermosas playas y resorts, se encuentra “The Spa”, en Punta de Mita el mejor lugar para que te tomes un día libre dedicado para resaltar tu belleza y liberar el estrés.',
    'intro.p2': 'En The Spa queremos satisfacer sus necesidades y expectativas. Es por eso que le brindamos servicios personalizados en función de su gusto, estado de ánimo y necesidades específicas. Puede elegir el color de la cabina (cromoterapia), la música (musicoterapia), el aroma (aromaterapia), el masaje a presión, el aceite o crema de masaje, y el sabor del té.',
    'intro.p3': 'Con estos elementos logramos crear un ambiente que te permita relajarte, rejuvenecer o equilibrarte. Todo depende de cómo se sienta y de lo que deba hacer después del tratamiento.',
    'intro.p4': 'Nuestras instalaciones cuentan con todas las comodidades para que te sientas como en casa, además de que cumplimos con todas las normas de sanitización y esterilización de nuestros instrumentos e instalaciones.',

    // --- Servicios ---
    'serv.eyebrow': 'Nuestros servicios',
    'serv.title': 'Tratamientos pensados para ti',
    'serv.1': 'Masajes',
    'serv.2': 'Faciales',
    'serv.3': 'Exfoliación corporal',
    'serv.4': 'Tratamientos corporales',
    'serv.5': 'Depilación',
    'serv.6': 'Manicure y pedicure',

    // --- Formulario ---
    'form.eyebrow': 'Contacto',
    'form.title': 'Reserva tu experiencia',
    'form.nombre': 'Nombre',
    'form.telefono': 'Teléfono / WhatsApp',
    'form.servicio': 'Servicio de interés',
    'form.servicio.placeholder': 'Selecciona un servicio',
    'form.servicio.otro': 'Otro / no estoy segura',
    'form.mensaje': 'Mensaje (opcional)',
    'form.fecha': 'Fecha y hora tentativa (opcional)',
    'form.primera': 'Es mi primera vez en The Spa Mita',
    'form.enviar': 'Enviar',
    'form.req': 'Este campo es obligatorio',

    // --- Thank you ---
    'ty.title': '¡Gracias! Hemos recibido tu mensaje.',
    'ty.body': 'Nuestro equipo te contactará muy pronto para confirmar tu cita.',
    'ty.wa': '¿Prefieres respuesta inmediata? Escríbenos por WhatsApp',
    'ty.back': 'Volver al inicio',

    // --- SEO ---
    'seo.home.title': 'The Spa Mita — Spa de lujo en Punta de Mita, Nayarit',
    'seo.home.desc': 'Day spa en Punta de Mita, Bahía de Banderas. Masajes, faciales y tratamientos personalizados con cromoterapia, aromaterapia y musicoterapia.',
  },
  en: {
    'nav.inicio': 'Home',
    'nav.servicios': 'Services',
    'nav.nosotros': 'About',
    'nav.galeria': 'Gallery',
    'nav.contacto': 'Contact',
    'nav.reservar': 'Book now',

    'side.tagline': 'Luxury spa in Punta de Mita',
    'side.horario.label': 'Hours',
    'side.horario.valor': 'Mon–Sun · 9:00–20:00',
    'side.tel.label': 'Contact',

    'hero.eyebrow': 'Punta de Mita · Nayarit',
    'hero.title': 'A day to enhance your beauty and release stress',
    'hero.subtitle': 'Personalized spa experiences in the heart of Bahía de Banderas.',
    'hero.cta': 'Book now',
    'hero.cta2': 'Message us on WhatsApp',

    'intro.eyebrow': 'The Spa',
    'intro.p1': 'In Bahía de Banderas there is a place where your senses become sharper, surrounded by beautiful beaches and resorts, you’ll find “The Spa”, in Punta de Mita the best place to take a day off dedicated to enhancing your beauty and releasing stress.',
    'intro.p2': 'At The Spa we want to satisfy your needs and expectations. That’s why we offer personalized services based on your taste, mood and specific needs. You can choose the color of the room (chromotherapy), the music (music therapy), the scent (aromatherapy), massage pressure, massage oil or cream, and the flavor of your tea.',
    'intro.p3': 'With these elements we create an environment that allows you to relax, rejuvenate or find balance. It all depends on how you feel and what you need to do after the treatment.',
    'intro.p4': 'Our facilities have all the comforts to make you feel at home, in addition to complying with all sanitation and sterilization standards for our instruments and facilities. At The Spa you will feel comfortable, safe and well cared for.',

    'serv.eyebrow': 'Our services',
    'serv.title': 'Treatments designed for you',
    'serv.1': 'Massages',
    'serv.2': 'Facials',
    'serv.3': 'Body exfoliation',
    'serv.4': 'Body treatments',
    'serv.5': 'Waxing',
    'serv.6': 'Manicure and pedicure',

    'form.eyebrow': 'Contact',
    'form.title': 'Book your experience',
    'form.nombre': 'Name',
    'form.telefono': 'Phone / WhatsApp',
    'form.servicio': 'Service of interest',
    'form.servicio.placeholder': 'Select a service',
    'form.servicio.otro': 'Other / not sure yet',
    'form.mensaje': 'Message (optional)',
    'form.fecha': 'Preferred date & time (optional)',
    'form.primera': 'This is my first time at The Spa Mita',
    'form.enviar': 'Send',
    'form.req': 'This field is required',

    'ty.title': 'Thank you! We received your message.',
    'ty.body': 'Our team will contact you shortly to confirm your appointment.',
    'ty.wa': 'Prefer an immediate reply? Message us on WhatsApp',
    'ty.back': 'Back to home',

    'seo.home.title': 'The Spa Mita — Luxury day spa in Punta de Mita, Nayarit',
    'seo.home.desc': 'Day spa in Punta de Mita, Bahía de Banderas. Massages, facials and personalized treatments with chromotherapy, aromatherapy and music therapy.',
  },
} as const;

export function useTranslations(lang: Lang) {
  return function t(key: keyof (typeof ui)['es']): string {
    return (ui[lang] as Record<string, string>)[key] ?? (ui.es as Record<string, string>)[key] ?? String(key);
  };
}
