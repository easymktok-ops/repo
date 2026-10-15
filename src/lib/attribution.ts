/**
 * Atribucion de primer contacto: de donde llego el cliente la primera vez.
 *
 * captureAttribution() corre en todas las paginas (AttributionCapture.astro):
 *  - Lee UTM, gclid/gbraid/wbraid, document.referrer (sin Stripe ni nuestro dominio), la pagina de
 *    entrada, el idioma y la hora (America/Mexico_City, UTC-6 fijo como el resto del sitio).
 *  - Guarda en cookie propia "aero_ft" (90 dias, SameSite=Lax, Secure) Y en localStorage.
 *  - Primer contacto gana: si ya hay registro no se sobrescribe; solo un click ID NUEVO se guarda
 *    aparte como last_click_id.
 * readAttribution() lo entrega al checkout. Todo en try/catch: sin almacenamiento el sitio sigue igual
 * (queda una copia en memoria de la pagina actual).
 */

export type ClickType = "gclid" | "gbraid" | "wbraid";

export interface FirstTouch {
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
  gclid?: string;
  gbraid?: string;
  wbraid?: string;
  referrer?: string;
  landing_page?: string;
  idioma?: "es" | "en";
  first_touch_at?: string;
  last_click_id?: { type: ClickType; value: string; at: string };
}

const KEY = "aero:ft";
const COOKIE = "aero_ft";
const MAX_AGE_S = 90 * 24 * 60 * 60;
const UTM = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content"] as const;
const CLICK: ClickType[] = ["gclid", "gbraid", "wbraid"];

type Win = Window & { __aeroFt?: FirstTouch };

const cut = (v: string, n = 255) => v.trim().slice(0, n);

/** Hora local de Mexico en ISO 8601 (UTC-6 fijo). */
export function mexicoIso(d = new Date()): string {
  const local = new Date(d.getTime() - 6 * 60 * 60 * 1000);
  return local.toISOString().slice(0, 19) + "-06:00";
}

/** Referrer util: vacio si viene de Stripe o de nuestro propio dominio. */
export function cleanReferrer(ref: string, ownHost: string): string | undefined {
  if (!ref) return undefined;
  try {
    const host = new URL(ref).hostname.toLowerCase();
    const bare = (h: string) => h.replace(/^www\./, "");
    if (host === "stripe.com" || host.endsWith(".stripe.com")) return undefined;
    if (bare(host) === bare(ownHost.toLowerCase())) return undefined;
    return cut(ref);
  } catch {
    return undefined;
  }
}

function readStored(): FirstTouch | null {
  try {
    const raw = localStorage.getItem(KEY);
    if (raw) return JSON.parse(raw) as FirstTouch;
  } catch {
    /* almacenamiento bloqueado o dato corrupto */
  }
  try {
    const m = document.cookie.match(/(?:^|; )aero_ft=([^;]*)/);
    if (m) return JSON.parse(decodeURIComponent(m[1])) as FirstTouch;
  } catch {
    /* cookie bloqueada o corrupta */
  }
  return null;
}

function persist(ft: FirstTouch): void {
  const raw = JSON.stringify(ft);
  try {
    localStorage.setItem(KEY, raw);
  } catch {
    /* bloqueado */
  }
  try {
    const secure = location.protocol === "https:" ? "; Secure" : "";
    document.cookie = `${COOKIE}=${encodeURIComponent(raw)}; Max-Age=${MAX_AGE_S}; Path=/; SameSite=Lax${secure}`;
  } catch {
    /* bloqueado */
  }
}

export function captureAttribution(): void {
  try {
    const qs = new URLSearchParams(location.search);
    const stored = readStored();
    const clicks = CLICK.map((type) => ({ type, value: qs.get(type) })).filter(
      (c): c is { type: ClickType; value: string } => !!c.value,
    );

    if (stored) {
      // Primer contacto gana. Solo un click ID nuevo se anota aparte.
      const fresh = clicks.find(
        (c) => stored[c.type] !== c.value && stored.last_click_id?.value !== c.value,
      );
      if (fresh) {
        stored.last_click_id = { type: fresh.type, value: cut(fresh.value), at: mexicoIso() };
      }
      (window as Win).__aeroFt = stored;
      persist(stored); // re-sincroniza cookie y localStorage (y renueva los 90 dias)
      return;
    }

    const ft: FirstTouch = {};
    for (const k of UTM) {
      const v = qs.get(k);
      if (v) ft[k] = cut(v);
    }
    for (const c of clicks) ft[c.type] = cut(c.value);
    const ref = cleanReferrer(document.referrer, location.hostname);
    if (ref) ft.referrer = ref;
    ft.landing_page = cut(location.pathname + location.search);
    ft.idioma = /^\/en(\/|$)/.test(location.pathname) ? "en" : "es";
    ft.first_touch_at = mexicoIso();

    (window as Win).__aeroFt = ft;
    persist(ft);
  } catch {
    /* nunca romper la pagina por la atribucion */
  }
}

/** Registro de primer contacto (almacenado o, si no hay almacenamiento, el de esta pagina). */
export function readAttribution(): FirstTouch {
  if (typeof window === "undefined") return {};
  return readStored() ?? (window as Win).__aeroFt ?? {};
}

/** Campos que viajan al backend con la reserva (los click IDs van aparte, ver clickIds.ts). */
export function attributionPayload(): Record<string, string> {
  const ft = readAttribution();
  const out: Record<string, string> = {};
  for (const k of [...UTM, "referrer", "landing_page", "idioma", "first_touch_at"] as const) {
    const v = ft[k];
    if (typeof v === "string" && v) out[k] = v;
  }
  return out;
}
