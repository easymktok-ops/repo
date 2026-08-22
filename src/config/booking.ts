/**
 * Configuracion del motor de reservas (Fase 3). DOMINIO-AGNOSTICO: el endpoint
 * del backend es relativo por defecto, asi el sitio y el PHP conviven en el
 * mismo host (Webempresa) sin acoplar dominios. Todo lo sensible (llaves,
 * credenciales de BD) vive en el servidor PHP, nunca aqui.
 *
 * Modelo de cobro CONFIRMADO con el negocio (bases del sitio .mx):
 *  - "full"    -> paga el total del paquete x numero de pasajeros.
 *  - "deposit" -> aparta lugares con un anticipo por pasajero; el saldo se
 *                 liquida EN SITIO el dia del vuelo (sin segundo cobro en linea).
 * El precio de cada paquete es POR PERSONA (asi lo publica el negocio).
 */

export const booking = {
  /** Anticipo por pasajero para apartar lugares (MXN). Dato de negocio. */
  depositPerPassenger: 1000,
  currency: "MXN",
  /** Precio POR PERSONA en todos los paquetes. */
  pricingUnit: "person" as const,
  /**
   * Endpoint del backend que crea la sesion de Stripe. Relativo => mismo origen
   * en produccion. Override por env si el backend vive en otro host/subdominio.
   */
  checkoutEndpoint:
    import.meta.env.PUBLIC_CHECKOUT_ENDPOINT ?? "/api/create-checkout-session.php",
  minPassengers: 1,
} as const;

export type BookingMode = "full" | "deposit";

/** Calcula el monto (en pesos, no centavos) que se cobra AHORA para una reserva. */
export function amountDueNow(
  mode: BookingMode,
  pricePerPerson: number,
  passengers: number,
): number {
  if (mode === "deposit") return booking.depositPerPassenger * passengers;
  return pricePerPerson * passengers;
}
