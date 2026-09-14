/**
 * Disparo de eventos. Funciona con gtag (GA4) o fbq (Meta) si están cargados;
 * si no hay ninguno, no hace nada y no rompe.
 *
 * PENDIENTE: ID de GA4 / Pixel de Meta — ver ASSETS-TODO.md.
 */
export function evento(nombre, datos = {}) {
  if (typeof window === 'undefined') return;
  try {
    window.gtag?.('event', nombre, datos);
    window.fbq?.('trackCustom', nombre, datos);
    window.dataLayer?.push({ event: nombre, ...datos });
  } catch {
    /* nunca bloquear el click por analytics */
  }
}

/** Evento estándar de conversión: click en "Pedir por WhatsApp". */
export function eventoPedido(platillo, origen) {
  evento('pedido_whatsapp', {
    id: platillo?.id ?? 'general',
    nombre: platillo?.nombre ?? 'General',
    precio: platillo?.precio ?? null,
    origen,
  });
}
