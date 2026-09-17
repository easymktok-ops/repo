import { NUMERO_WHATSAPP_POR_DEFECTO } from '../data/negocio.js';

/**
 * Generación de enlaces de WhatsApp.
 *
 * El número sale de src/data/negocio.js, que también leen los scripts de SEO.
 * Para cambiarlo sin tocar código, definir VITE_WHATSAPP_NUMERO en el entorno
 * de build (.env o el panel del hosting).
 */
export const NUMERO_WHATSAPP =
  import.meta.env.VITE_WHATSAPP_NUMERO ?? NUMERO_WHATSAPP_POR_DEFECTO;

const base = (texto) =>
  `https://wa.me/${NUMERO_WHATSAPP}?text=${encodeURIComponent(texto)}`;

/** Enlace de pedido de un platillo puntual. */
export function linkWhatsApp(platillo) {
  const texto =
    `¡Hola Kosecha! Quiero pedir:\n\n` +
    `🥗 *${platillo.nombre}*\n` +
    `${platillo.descripcion}\n` +
    `Precio: $${platillo.precio} MXN\n\n` +
    `¿Me confirman disponibilidad?`;
  return base(texto);
}

/** Enlace general, sin platillo (nav, ubicación, footer). */
export function linkWhatsAppGeneral() {
  const texto =
    `¡Hola Kosecha! Vengo de la página web y quiero hacer un pedido. ` +
    `¿Me pasan el menú del día?`;
  return base(texto);
}
