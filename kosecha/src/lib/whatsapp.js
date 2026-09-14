/**
 * Generación de enlaces de WhatsApp.
 *
 * El número vive acá y en ningún otro lado. Para cambiarlo sin tocar código,
 * definir VITE_WHATSAPP_NUMERO en el entorno de build (.env / panel del host).
 *
 * Formato: código de país + número, solo dígitos. México: 52 + 10 dígitos.
 * Fuente del número: documento MENÚ (WhatsApp: 276 112 0304).
 */
export const NUMERO_WHATSAPP =
  import.meta.env.VITE_WHATSAPP_NUMERO ?? '522761120304';

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
