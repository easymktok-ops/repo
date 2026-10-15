/**
 * Identificadores de clic de Google Ads (gclid / gbraid / wbraid) para guardarlos con la reserva.
 * Orden: URL actual (url_passthrough los conserva sin cookies) > lo guardado al llegar al sitio
 * (Analytics.astro, clave "aero:clickids", 90 dias) > cookies de Google (_gcl_aw / _gcl_gb)
 * > registro de primer contacto (attribution.ts: last_click_id y luego el click ID original).
 */
import { readAttribution } from "./attribution";
export type ClickIds = { gclid?: string; gbraid?: string; wbraid?: string };

const KEYS = ["gclid", "gbraid", "wbraid"] as const;
const MAX_AGE_MS = 90 * 24 * 60 * 60 * 1000;

/** Recibe una funcion: con cookies bloqueadas, el solo hecho de LEER window.localStorage lanza error. */
function fromStorage(store: () => Storage): ClickIds {
  try {
    const raw = store().getItem("aero:clickids");
    if (!raw) return {};
    const v = JSON.parse(raw) as ClickIds & { ts?: number };
    if (typeof v.ts === "number" && Date.now() - v.ts > MAX_AGE_MS) return {};
    return v;
  } catch {
    return {};
  }
}

/** Valor de una cookie _gcl_* con formato "GCL.<timestamp>.<id>". */
function fromGclCookie(name: string): string | undefined {
  try {
    const m = document.cookie.match(new RegExp("(?:^|; )" + name + "=([^;]*)"));
    if (!m) return undefined;
    const parts = decodeURIComponent(m[1]).split(".");
    return parts.length >= 3 ? parts.slice(2).join(".") : undefined;
  } catch {
    return undefined;
  }
}

export function readClickIds(): ClickIds {
  if (typeof window === "undefined") return {};
  const qs = new URLSearchParams(location.search);
  const session = fromStorage(() => window.sessionStorage);
  const local = fromStorage(() => window.localStorage);
  const cookie: ClickIds = { gclid: fromGclCookie("_gcl_aw"), gbraid: fromGclCookie("_gcl_gb") };
  const ft = readAttribution();
  const last: ClickIds = ft.last_click_id
    ? { [ft.last_click_id.type]: ft.last_click_id.value }
    : {};
  const out: ClickIds = {};
  for (const k of KEYS) {
    const v = qs.get(k) || session[k] || local[k] || cookie[k] || last[k] || ft[k];
    if (v) out[k] = v;
  }
  return out;
}
