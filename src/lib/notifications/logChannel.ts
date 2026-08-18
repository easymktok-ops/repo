import type { NotificationChannel, NotificationMessage, NotificationResult } from "./types";

/**
 * Canal "log": no envia nada, pero SIEMPRE deja traza estructurada y devuelve
 * ok=true. Es el default en dev y el fallback seguro: garantiza que el flujo
 * de reserva nunca se quede sin registro de intento de notificacion.
 * Los proveedores reales (Resend/SendGrid/Meta/Twilio) implementan la misma
 * interfaz y se enchufan en Fase 3.
 */
export function createLogChannel(channel: "email" | "whatsapp"): NotificationChannel {
  return {
    channel,
    providerName: "log",
    async send(message: NotificationMessage): Promise<NotificationResult> {
      const attemptedAt = new Date().toISOString();
      // Traza estructurada: canal, tipo, destino enmascarado, booking.
      console.info(
        `[notify:log] ${attemptedAt} ${channel} kind=${message.kind} booking=${message.bookingId} to=${maskDestination(message.to)}`,
      );
      return {
        ok: true,
        channel,
        to: message.to,
        bookingId: message.bookingId,
        providerMessageId: `log-${message.bookingId}-${channel}`,
        attemptedAt,
      };
    },
  };
}

/** Oculta parte del destino en logs (privacidad). */
function maskDestination(to: string): string {
  if (to.includes("@")) {
    const [user, domain] = to.split("@");
    return `${user.slice(0, 2)}***@${domain}`;
  }
  return `${to.slice(0, 3)}***${to.slice(-2)}`;
}
