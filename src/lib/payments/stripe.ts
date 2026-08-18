import type {
  CreateCheckoutInput,
  CheckoutSession,
  NormalizedPaymentEvent,
  PaymentProvider,
} from "./types";

/**
 * Adapter de Stripe (esqueleto de Fase 1). La logica real (Stripe Checkout +
 * verificacion de firma del webhook) se cablea en Fase 3, cuando existan las
 * edge/serverless functions. Aqui dejamos el contrato tipado y los puntos de
 * integracion marcados, sin secretos ni llamadas de red.
 *
 * Claves via env (server-only): STRIPE_SECRET_KEY, STRIPE_WEBHOOK_SECRET.
 */
export function createStripeProvider(): PaymentProvider {
  return {
    name: "stripe",

    async createCheckout(input: CreateCheckoutInput): Promise<CheckoutSession> {
      // TODO(Fase 3): crear Stripe Checkout Session con line_items,
      // payment_method_types (card, oxxo, customer_balance/SPEI segun aplique),
      // locale, success_url/cancel_url absolutos y metadata.bookingId.
      void input;
      throw new Error(
        "[stripe] createCheckout no cableado todavia (Fase 3). El contrato existe; falta la function de servidor.",
      );
    },

    async parseWebhook(payload: string, signature: string): Promise<NormalizedPaymentEvent> {
      // TODO(Fase 3): stripe.webhooks.constructEvent(payload, signature,
      // STRIPE_WEBHOOK_SECRET) y mapear a NormalizedPaymentEvent.
      void payload;
      void signature;
      throw new Error("[stripe] parseWebhook no cableado todavia (Fase 3).");
    },
  };
}
