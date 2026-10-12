/**
 * Identificadores de clic de Google Ads (gclid / gbraid / wbraid) para guardarlos con la reserva.
 * Orden: URL actual (url_passthrough los conserva sin cookies) > lo guardado al llegar al sitio
 * (Analytics.astro, clave "aero:clickids", 90 dias) > cookies de Google (_gcl_aw / _gcl_gb).
 */
export type ClickIds = { gclid?: string; gbraid?: string; wbraid?: string };

const KEYS = ["gclid", "gbraid", "wbraid"] as const;
const MAX_AGE_MS = 90 * 24 * 60 * 60 * 1000;

function fromStorage(store: Storage | undefined): ClickIds {
  try {
    const raw = store?.getItem("aero:clickids");
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
  const m = document.cookie.match(new RegExp("(?:^|; )" + name + "=([^;]*)"));
  if (!m) return undefined;
  const parts = decodeURIComponent(m[1]).split(".");
  return parts.length >= 3 ? parts.slice(2).join(".") : undefined;
}

export function readClickIds(): ClickIds {
  if (typeof window === "undefined") return {};
  const qs = new URLSearchParams(location.search);
  const session = fromStorage(window.sessionStorage);
  let local: ClickIds = {};
  try {
    local = fromStorage(window.localStorage);
  } catch {
    /* almacenamiento bloqueado */
  }
  const cookie: ClickIds = { gclid: fromGclCookie("_gcl_aw"), gbraid: fromGclCookie("_gcl_gb") };
  const out: ClickIds = {};
  for (const k of KEYS) {
    const v = qs.get(k) || session[k] || local[k] || cookie[k];
    if (v) out[k] = v;
  }
  return out;
}
