import { useState } from "react";
import type { Ymd } from "@lib/pricing/calendar";
import { formatPesos } from "@lib/pricing/calendar";
import { booking } from "@config/booking";
import PriceCalendar, { type DayQuote } from "./PriceCalendar";

/**
 * Vista previa del calendario para el panel de precios (se muestra en un
 * iframe de panel.php). Usa el MISMO componente que el sitio publico, pero en
 * modo "preview": pide /api/prices.php?preview=1 con la sesion del panel, asi
 * funciona aunque las tarifas por fecha sigan apagadas para el publico.
 * Solo cliente (client:only): depende de la fecha de hoy y de la sesion.
 */
export default function PricePreview() {
  const slug = new URLSearchParams(window.location.search).get("pkg") ?? "";
  const [picked, setPicked] = useState<{ date: Ymd; quote: DayQuote } | null>(null);
  const [disabled, setDisabled] = useState(false);

  if (!slug) return <p className="pv-msg">Elige un paquete en el panel.</p>;
  if (disabled) {
    return (
      <p className="pv-msg">
        Este paquete todavia no tiene anticipo configurado, asi que se cobra como antes y no hay
        calendario.
      </p>
    );
  }
  return (
    <div className="pv">
      <PriceCalendar
        locale="es"
        packageSlug={slug}
        value={picked?.date ?? ""}
        endpoint={booking.pricesEndpoint}
        mode="preview"
        onDisabled={() => setDisabled(true)}
        onChange={(date, quote) => setPicked({ date, quote })}
      />
      {picked && (
        <p className="pv-sel">
          {picked.date}: {formatPesos(picked.quote.price, "es")} por persona, anticipo{" "}
          {formatPesos(picked.quote.deposit, "es")}, saldo {formatPesos(picked.quote.balance, "es")}
          .
        </p>
      )}
    </div>
  );
}
