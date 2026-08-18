import type { PaymentProvider } from "./types";
import { createStripeProvider } from "./stripe";

/**
 * Factory de pasarela. Hoy siempre Stripe (proveedor confirmado). Si mañana el
 * negocio cambia de proveedor, se agrega un case y un adapter nuevo; el
 * checkout no se entera.
 */
export function getPaymentProvider(): PaymentProvider {
  // Punto unico de seleccion. Preparado para leer un env si algun dia hay >1.
  return createStripeProvider();
}

export type * from "./types";
