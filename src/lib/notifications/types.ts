/**
 * Contrato de notificaciones transaccionales (email + WhatsApp).
 *
 * DISENADO CONTRA EL BUG REAL DEL SITIO DE REFERENCIA: reservas marcadas como
 * "sin correo enviado" de forma recurrente. Aqui el envio NO es fire-and-forget:
 * cada intento produce un NotificationResult con exito/fallo, y el outbox
 * (ver outbox.ts) registra y reintenta. El estado de envio SIEMPRE queda
 * trazado; nunca se pierde silenciosamente.
 */

export type Channel = "email" | "whatsapp";

export type NotificationKind =
  | "booking_confirmation" // al pasajero
  | "booking_admin_alert" // al administrador
  | "payment_receipt"
  | "reschedule_notice";

export interface NotificationMessage {
  kind: NotificationKind;
  channel: Channel;
  to: string; // email o telefono E.164
  locale: "es" | "en";
  bookingId: string;
  /** Datos que rellenan la plantilla del proveedor. Sin HTML crudo del usuario. */
  data: Record<string, string | number>;
}

export interface NotificationResult {
  ok: boolean;
  channel: Channel;
  to: string;
  bookingId: string;
  providerMessageId?: string;
  error?: string; // mensaje de fallo, si ok=false
  attemptedAt: string; // ISO
}

/** Un canal concreto (Resend, SendGrid, Meta Cloud, Twilio, o "log"). */
export interface NotificationChannel {
  readonly channel: Channel;
  readonly providerName: string;
  send(message: NotificationMessage): Promise<NotificationResult>;
}
