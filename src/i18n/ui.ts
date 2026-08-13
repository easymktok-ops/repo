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
    'serv.7': 'Niños',
    'serv.8': 'Más servicios',

    // --- Formulario ---
    'form.eyebrow': 'Contacto',
    'form.title': 'Reserva tu experiencia',
    'form.intro': 'Cuéntanos qué buscas y te contactamos para confirmar tu cita.',
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
    'seo.contacto.title': 'Contacto y reservas — The Spa Mita, Punta de Mita',
    'seo.contacto.desc': 'Reserva tu experiencia en The Spa Mita, Punta de Mita. Escríbenos o agenda por WhatsApp.',

    // --- Thank you ---
    'ty.title': '¡Gracias! Hemos recibido tu mensaje.',
    'ty.body': 'Nuestro equipo te contactará muy pronto para confirmar tu cita.',
    'ty.wa': '¿Prefieres respuesta inmediata? Escríbenos por WhatsApp',
    'ty.back': 'Volver al inicio',

    // --- Páginas secundarias ---
    'serv.page.title': 'Servicios y precios',
    'serv.page.lead': 'Cada tratamiento se personaliza a tu gusto: color de cabina, música, aroma, presión y aceite. Precios en pesos mexicanos.',
    'serv.dur': 'Duración',
    'nosotros.eyebrow': 'Nosotras',
    'nosotros.title': 'Un espacio pensado para ti',
    'nosotros.p1': 'The Spa Mita nació con una idea sencilla: darte el tiempo y el trato que mereces. Somos un equipo que cuida cada detalle para que te sientas como en casa.',
    'nosotros.p2': 'Durante años, el estrés merma la salud de las personas. Al visitar The Spa Mita tendrás tiempo para ti y para enfocarte en tu cuerpo: olvídate del exterior y siéntete más relajado.',
    'nosotros.benefits.title': 'Algunos de los beneficios que obtendrás',
    'nosotros.b1': 'Aumento de energía física y mental',
    'nosotros.b2': 'Combate la ansiedad y los nervios',
    'nosotros.b3': 'Reducción del estrés',
    'nosotros.b4': 'Mayor autoestima',
    'nosotros.b5': 'Equilibrio emocional',
    'nosotros.b6': 'Mejor estado de ánimo',
    'galeria.eyebrow': 'Galería',
    'galeria.title': 'Nuestro espacio',
    'seo.servicios.title': 'Servicios y precios — The Spa Mita, Punta de Mita',
    'seo.servicios.desc': 'Masajes, faciales, exfoliación, tratamientos corporales, depilación y manicure en Punta de Mita. Consulta servicios y precios de The Spa Mita.',
    'seo.nosotros.title': 'Nosotras — The Spa Mita, Punta de Mita, Nayarit',
    'seo.nosotros.desc': 'Conoce The Spa Mita: un day spa en Punta de Mita dedicado a tu bienestar, relajación y belleza.',
    'seo.galeria.title': 'Galería — The Spa Mita, Punta de Mita',
    'seo.galeria.desc': 'Conoce nuestras instalaciones y tratamientos en Punta de Mita a través de nuestra galería.',

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

    // --- Secondary pages ---
    'serv.page.title': 'Services & prices',
    'serv.page.lead': 'Every treatment is personalized to your taste: room color, music, scent, pressure and oil. Prices in Mexican pesos.',
    'serv.dur': 'Duration',
    'nosotros.eyebrow': 'About us',
    'nosotros.title': 'A space designed for you',
    'nosotros.p1': 'The Spa Mita was born from a simple idea: to give you the time and care you deserve. We are a team that looks after every detail so you feel at home.',
    'nosotros.p2': 'For years, stress has undermined people’s health. At The Spa Mita you will have time for yourself and to focus on your body: forget the outside world and feel more relaxed.',
    'nosotros.benefits.title': 'Some of the benefits you will gain',
    'nosotros.b1': 'Increased physical and mental energy',
    'nosotros.b2': 'Eases anxiety and nerves',
    'nosotros.b3': 'Stress reduction',
    'nosotros.b4': 'Higher self-esteem',
    'nosotros.b5': 'Emotional balance',
    'nosotros.b6': 'A better mood',
    'galeria.eyebrow': 'Gallery',
    'galeria.title': 'Our space',
    'seo.servicios.title': 'Services & prices — The Spa Mita, Punta de Mita',
    'seo.servicios.desc': 'Massages, facials, body scrubs, body treatments, waxing and manicure in Punta de Mita. See The Spa Mita services and prices.',
    'seo.nosotros.title': 'About us — The Spa Mita, Punta de Mita, Nayarit',
    'seo.nosotros.desc': 'Discover The Spa Mita: a day spa in Punta de Mita dedicated to your wellness, relaxation and beauty.',
    'seo.galeria.title': 'Gallery — The Spa Mita, Punta de Mita',
    'seo.galeria.desc': 'Discover our facilities and treatments in Punta de Mita through our gallery.',

    'seo.home.title': 'The Spa Mita — Luxury day spa in Punta de Mita, Nayarit',
    'seo.home.desc': 'Day spa in Punta de Mita, Bahía de Banderas. Massages, facials and personalized treatments with chromotherapy, aromatherapy and music therapy.',
  },
} as const;

export function useTranslations(lang: Lang) {
  return function t(key: keyof (typeof ui)['es']): string {
    return (ui[lang] as Record<string, string>)[key] ?? (ui.es as Record<string, string>)[key] ?? String(key);
  };
}
