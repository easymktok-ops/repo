/**
 * Contrato de pasarela de pago. El sitio depende de ESTA interfaz, no de
 * Stripe directamente: cambiar/anadir proveedor es escribir otro adapter, no
 * refactorizar el checkout. Proveedor confirmado para arrancar: Stripe.
 */

export interface BookingLineItem {
  packageSlug: string;
  title: string;
  unitAmount: number; // en centavos, moneda MXN
  quantity: number; // numero de pasajeros
}

export interface CreateCheckoutInput {
  bookingId: string; // id interno de la reserva (para conciliar webhooks)
  currency: string; // "MXN"
  lineItems: BookingLineItem[];
  customerEmail?: string;
  locale: "es" | "en";
  successUrl: string; // absoluto, construido con site.url (dominio-agnostico)
  cancelUrl: string;
  metadata?: Record<string, string>;
}

export interface CheckoutSession {
  id: string;
  /** URL a la que redirigimos al pasajero para pagar. */
  redirectUrl: string;
}

export type PaymentEventType =
  | "checkout.completed"
  | "checkout.expired"
  | "payment.failed"
  | "refund.created";

export interface NormalizedPaymentEvent {
  type: PaymentEventType;
  bookingId: string;
  amount: number;
  currency: string;
  raw: unknown;
}

export interface PaymentProvider {
  readonly name: string;
  createCheckout(input: CreateCheckoutInput): Promise<CheckoutSession>;
  /** Verifica firma y normaliza el webhook a un evento agnostico. */
  parseWebhook(payload: string, signature: string): Promise<NormalizedPaymentEvent>;
}
