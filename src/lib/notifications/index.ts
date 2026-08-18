import type { Channel, NotificationChannel } from "./types";
import { createLogChannel } from "./logChannel";

/**
 * Factory de canales de notificacion. Selecciona el proveedor por env
 * (server-only). Proveedor real por DEFINIR en Fase 3: hoy el default es "log",
 * que garantiza trazabilidad sin enviar. Cuando se confirme proveedor, se
 * agrega el adapter (Resend/SendGrid/Meta Cloud/Twilio) implementando
 * NotificationChannel y se enchufa aqui: cero cambios en el flujo de reserva.
 */
export function getChannel(channel: Channel): NotificationChannel {
  const provider =
    channel === "email"
      ? (import.meta.env.NOTIFICATIONS_EMAIL_PROVIDER ?? "log")
      : (import.meta.env.NOTIFICATIONS_WHATSAPP_PROVIDER ?? "log");

  switch (provider) {
    // TODO(Fase 3): case "resend" / "sendgrid" / "meta_cloud" / "twilio".
    case "log":
    default:
      return createLogChannel(channel);
  }
}

export { sendWithRetry } from "./outbox";
export type * from "./types";
