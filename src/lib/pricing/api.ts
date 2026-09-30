/**
 * Cliente de /api/prices.php. No calcula precios: solo pide y valida lo que
 * devuelve el servidor (unica implementacion de la resolucion de precio).
 * Cache en memoria por paquete y mes con TTL corto, para que un cambio hecho
 * en el panel se vea en segundos sin volver a pedir cada vez.
 */

import { monthRange, type DayInfo, type Ymd } from "./calendar";

export interface PricesEnabled {
  enabled: true;
  currency: string;
  pricingVersion: string;
  minDate: Ymd;
  maxDate: Ymd;
  days: Record<Ymd, DayInfo>;
}
export interface PricesDisabled {
  enabled: false;
}
export type MonthPrices = PricesEnabled | PricesDisabled;

export class PriceApiError extends Error {
  constructor(
    message: string,
    readonly status?: number,
  ) {
    super(message);
    this.name = "PriceApiError";
  }
}

interface Options {
  preview?: boolean;
  signal?: AbortSignal;
  /** Vida de la cache en ms (por defecto 60 s, igual que la cache publica del servidor). */
  ttlMs?: number;
  fetchImpl?: typeof fetch;
  now?: () => number;
}

const cache = new Map<string, { at: number; value: MonthPrices }>();

export function clearPriceCache(): void {
  cache.clear();
}

function isDayInfo(v: unknown): v is DayInfo {
  if (!v || typeof v !== "object") return false;
  const d = v as DayInfo;
  if (d.status !== "available" && d.status !== "blocked" && d.status !== "unavailable")
    return false;
  return d.status !== "available" || typeof d.price === "number";
}

/** Valida la forma de la respuesta. Lanza PriceApiError si no es lo esperado. */
export function parsePrices(json: unknown): MonthPrices {
  if (!json || typeof json !== "object") throw new PriceApiError("Respuesta invalida");
  const j = json as Record<string, unknown>;
  if (j.enabled === false) return { enabled: false };
  if (j.enabled !== true || typeof j.days !== "object" || j.days === null) {
    throw new PriceApiError("Respuesta invalida");
  }
  const days: Record<Ymd, DayInfo> = {};
  for (const [k, v] of Object.entries(j.days as Record<string, unknown>)) {
    if (!isDayInfo(v)) throw new PriceApiError("Dia invalido en la respuesta");
    days[k] = v;
  }
  return {
    enabled: true,
    currency: typeof j.currency === "string" ? j.currency : "MXN",
    pricingVersion: String(j.pricingVersion ?? ""),
    minDate: String(j.minDate ?? ""),
    maxDate: String(j.maxDate ?? ""),
    days,
  };
}

function cacheKey(
  endpoint: string,
  slug: string,
  year: number,
  month: number,
  preview: boolean,
): string {
  return [endpoint, preview ? "p" : "u", slug, monthRange(year, month).from].join("|");
}

/** Valor en cache (si sigue vigente) sin pedir nada: evita parpadeos al volver a un mes. */
export function peekMonthPrices(
  endpoint: string,
  packageSlug: string,
  year: number,
  month: number,
  opts: Pick<Options, "preview" | "ttlMs" | "now"> = {},
): MonthPrices | null {
  const hit = cache.get(cacheKey(endpoint, packageSlug, year, month, !!opts.preview));
  const now = opts.now ?? Date.now;
  return hit && now() - hit.at < (opts.ttlMs ?? 60_000) ? hit.value : null;
}

/** Precios del mes completo (dia 1 al ultimo) de un paquete. */
export async function getMonthPrices(
  endpoint: string,
  packageSlug: string,
  year: number,
  month: number,
  opts: Options = {},
): Promise<MonthPrices> {
  const { from, to } = monthRange(year, month);
  const preview = !!opts.preview;
  const key = cacheKey(endpoint, packageSlug, year, month, preview);
  const now = opts.now ?? Date.now;
  const ttl = opts.ttlMs ?? 60_000;

  const hit = cache.get(key);
  if (hit && now() - hit.at < ttl) return hit.value;

  const qs = new URLSearchParams({ packageId: packageSlug, from, to });
  if (preview) qs.set("preview", "1");
  const url = endpoint + (endpoint.includes("?") ? "&" : "?") + qs.toString();

  let res: Response;
  try {
    res = await (opts.fetchImpl ?? fetch)(url, {
      headers: { Accept: "application/json" },
      credentials: "same-origin",
      signal: opts.signal,
    });
  } catch (e) {
    if (e instanceof DOMException && e.name === "AbortError") throw e;
    throw new PriceApiError("No se pudo conectar");
  }
  if (!res.ok) throw new PriceApiError(`HTTP ${res.status}`, res.status);
  let json: unknown;
  try {
    json = await res.json();
  } catch {
    throw new PriceApiError("Respuesta no es JSON", res.status);
  }
  const value = parsePrices(json);
  cache.set(key, { at: now(), value });
  return value;
}
