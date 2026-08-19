import type { Locale } from "@config/site";

/**
 * Diccionario de UI. Copy corto y funcional (verbo + objeto en botones).
 * Nada de em-dash. Nada de buzzwords. Los textos largos de marketing/FAQ viven
 * en el CMS (Content Collections), no aqui: aqui solo lo estructural del chrome.
 */
export const ui = {
  "nav.book": { es: "Reservar vuelo", en: "Book a flight" },
  "nav.menu": { es: "Menu", en: "Menu" },
  "nav.close": { es: "Cerrar", en: "Close" },
  "nav.language": { es: "English", en: "Espanol" },

  "hero.scrollHint": { es: "", en: "" }, // sin scroll cues (regla anti-slop)

  "cta.book": { es: "Reservar vuelo", en: "Book a flight" },
  "cta.explore": { es: "Ver vuelos", en: "See flights" },
  "cta.whatsapp": { es: "Escribir por WhatsApp", en: "Message on WhatsApp" },

  "price.from": { es: "Desde", en: "From" },
  "price.perPerson": { es: "por persona", en: "per person" },

  "trust.afac": {
    es: "Pilotos certificados AFAC",
    en: "AFAC-certified pilots",
  },
  "trust.reschedule": {
    es: "Reprogramación sin costo",
    en: "Free rescheduling",
  },
  "trust.securePay": { es: "Pago seguro", en: "Secure payment" },

  "booking.step1": { es: "Elige tu vuelo", en: "Choose your flight" },
  "booking.step2": { es: "Datos del pasajero", en: "Passenger details" },
  "booking.step3": { es: "Pago", en: "Payment" },

  "faq.title": { es: "Preguntas frecuentes", en: "Frequently asked questions" },
  "footer.rights": {
    es: "Todos los derechos reservados.",
    en: "All rights reserved.",
  },

  "a11y.skip": { es: "Saltar al contenido", en: "Skip to content" },
} as const satisfies Record<string, Record<Locale, string>>;

export type UIKey = keyof typeof ui;

/** Devuelve un traductor ligado a un locale. */
export function useTranslations(locale: Locale) {
  return function t(key: UIKey): string {
    return ui[key][locale] ?? ui[key].es;
  };
}
