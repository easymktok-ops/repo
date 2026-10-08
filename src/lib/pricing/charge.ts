/**
 * Montos de una reserva en CENTAVOS (enteros): el anticipo de una fecha puede
 * traer centavos (ej. $1,192.50), asi que nunca se multiplica en pesos con
 * decimales. Es solo para MOSTRAR: el servidor recalcula el cobro real.
 */

export type ChargeMode = "full" | "deposit";

export interface Charge {
  /** Total del vuelo. */
  total: number;
  /** Lo que se paga ahora (total o anticipo). */
  now: number;
  /** Saldo a pagar en sitio. */
  balance: number;
}

export function chargeCents(
  mode: ChargeMode,
  priceCents: number,
  depositCents: number,
  passengers: number,
): Charge {
  const total = priceCents * passengers;
  const now = mode === "deposit" ? depositCents * passengers : total;
  return { total, now, balance: total - now };
}
