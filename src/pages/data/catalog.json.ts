import type { APIRoute } from "astro";
import { getCollection } from "astro:content";
import { booking } from "@config/booking";

/**
 * AUTORIDAD DE PRECIOS para el backend PHP.
 *
 * Se emite como archivo estatico (/data/catalog.json) en el build. El endpoint
 * PHP de checkout LEE este archivo y RECALCULA el monto del lado del servidor:
 * el cliente nunca envia precios, solo el slug del paquete + modo + pasajeros.
 * Asi, aunque alguien manipule el front, el cobro sale de aqui (fuente unica:
 * las Content Collections que edita el negocio en /admin).
 *
 * Precio POR PERSONA. bookable=false cuando priceFrom es null ("consultar"):
 * esos paquetes no se cobran en linea, se derivan a WhatsApp.
 */
export const GET: APIRoute = async () => {
  const pkgs = await getCollection(
    "packages",
    (p) => !p.data.draft && p.data.available,
  );

  const packages = pkgs
    .sort((a, b) => a.data.order - b.data.order)
    .map((p) => ({
      slug: p.data.slug,
      title: p.data.title,
      pricePerPerson: p.data.priceFrom,
      priceWas: p.data.priceWas,
      currency: p.data.currency,
      capacity: p.data.capacity ?? { max: 16 },
      durationMinutes: p.data.durationMinutes ?? null,
      bookable: p.data.priceFrom != null,
    }));

  const body = {
    generatedAt: new Date().toISOString(),
    currency: booking.currency,
    depositPerPassenger: booking.depositPerPassenger,
    pricingUnit: booking.pricingUnit,
    packages,
  };

  return new Response(JSON.stringify(body, null, 2), {
    headers: {
      "content-type": "application/json; charset=utf-8",
      "cache-control": "public, max-age=300",
    },
  });
};
