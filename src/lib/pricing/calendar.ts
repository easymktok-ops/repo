/**
 * Helpers PUROS del calendario de precios (sin DOM ni red). Todas las fechas
 * son "YYYY-MM-DD" en hora de Mexico. Prohibido `new Date("YYYY-MM-DD")`: se
 * usa aritmetica de enteros / Date.UTC, para que la zona horaria del navegador
 * nunca corra un dia.
 */

export type Ymd = string;
export type Locale = "es" | "en";

export type DayStatus = "available" | "blocked" | "unavailable";

/** Un dia tal como lo devuelve /api/prices.php (montos en centavos por persona). */
export interface DayInfo {
  status: DayStatus;
  price?: number;
  deposit?: number;
  balance?: number;
  /** Solo en vista previa del panel. */
  ruleLabel?: string | null;
  ruleType?: string;
  depositPercent?: number;
}

export type Tier = "low" | "mid" | "high";

const YMD_RE = /^(\d{4})-(\d{2})-(\d{2})$/;

export function parseYmd(s: Ymd): { y: number; m: number; d: number } | null {
  const m = YMD_RE.exec(s);
  if (!m) return null;
  const y = Number(m[1]);
  const mo = Number(m[2]);
  const d = Number(m[3]);
  const dt = new Date(Date.UTC(y, mo - 1, d));
  if (dt.getUTCFullYear() !== y || dt.getUTCMonth() !== mo - 1 || dt.getUTCDate() !== d)
    return null;
  return { y, m: mo, d };
}

export function isValidYmd(s: Ymd): boolean {
  return parseYmd(s) !== null;
}

export function toYmd(y: number, m: number, d: number): Ymd {
  return `${String(y).padStart(4, "0")}-${String(m).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
}

function utcMs(s: Ymd): number {
  const p = parseYmd(s);
  if (!p) throw new Error(`Fecha invalida: ${s}`);
  return Date.UTC(p.y, p.m - 1, p.d);
}

/**
 * Hoy en hora de la Ciudad de Mexico (UTC-6 fijo desde 2022, sin horario de
 * verano). Desfase fijo a proposito: no depende de la base de zonas del
 * dispositivo ni de la del hosting, asi navegador y servidor coinciden.
 */
export function todayMx(now: Date = new Date()): Ymd {
  const t = new Date(now.getTime() - 6 * 3_600_000);
  return toYmd(t.getUTCFullYear(), t.getUTCMonth() + 1, t.getUTCDate());
}

export function addDaysYmd(s: Ymd, days: number): Ymd {
  const dt = new Date(utcMs(s) + days * 86_400_000);
  return toYmd(dt.getUTCFullYear(), dt.getUTCMonth() + 1, dt.getUTCDate());
}

export function daysBetweenYmd(a: Ymd, b: Ymd): number {
  return Math.round((utcMs(b) - utcMs(a)) / 86_400_000);
}

/** 0 = domingo ... 6 = sabado. */
export function weekdayOf(s: Ymd): number {
  return new Date(utcMs(s)).getUTCDay();
}

export function monthOf(s: Ymd): { year: number; month: number } {
  const p = parseYmd(s);
  if (!p) throw new Error(`Fecha invalida: ${s}`);
  return { year: p.y, month: p.m };
}

export function addMonths(year: number, month: number, n: number): { year: number; month: number } {
  const idx = year * 12 + (month - 1) + n;
  return { year: Math.floor(idx / 12), month: (idx % 12) + 1 };
}

export function daysInMonth(year: number, month: number): number {
  return new Date(Date.UTC(year, month, 0)).getUTCDate();
}

/** Primer y ultimo dia del mes. */
export function monthRange(year: number, month: number): { from: Ymd; to: Ymd } {
  return { from: toYmd(year, month, 1), to: toYmd(year, month, daysInMonth(year, month)) };
}

/**
 * Semanas del mes, siempre 6 filas de 7 (altura estable al cambiar de mes).
 * Los dias fuera del mes son null. weekStart: 1 = lunes (es), 0 = domingo (en).
 */
export function monthGrid(year: number, month: number, weekStart: 0 | 1): (Ymd | null)[][] {
  const first = weekdayOf(toYmd(year, month, 1));
  const lead = (first - weekStart + 7) % 7;
  const total = daysInMonth(year, month);
  const cells: (Ymd | null)[] = [];
  for (let i = 0; i < lead; i++) cells.push(null);
  for (let d = 1; d <= total; d++) cells.push(toYmd(year, month, d));
  while (cells.length < 42) cells.push(null);
  const weeks: (Ymd | null)[][] = [];
  for (let i = 0; i < 42; i += 7) weeks.push(cells.slice(i, i + 7));
  return weeks;
}

/** Inicio / fin de la semana (segun weekStart) que contiene la fecha. */
export function weekBounds(s: Ymd, weekStart: 0 | 1): { start: Ymd; end: Ymd } {
  const offset = (weekdayOf(s) - weekStart + 7) % 7;
  const start = addDaysYmd(s, -offset);
  return { start, end: addDaysYmd(start, 6) };
}

/** Suma meses conservando el dia (recortado al ultimo dia del mes destino). */
export function shiftMonthKeepingDay(s: Ymd, n: number): Ymd {
  const p = parseYmd(s);
  if (!p) throw new Error(`Fecha invalida: ${s}`);
  const t = addMonths(p.y, p.m, n);
  return toYmd(t.year, t.month, Math.min(p.d, daysInMonth(t.year, t.month)));
}

/**
 * Nivel de precio de cada dia DISPONIBLE del mes visible, por tercios de los
 * precios distintos de ese mes (bajo / normal / alto). Si todos los precios son
 * iguales, todos son "mid" (normal). Los dias no disponibles no tienen nivel.
 */
export function priceTiers(days: Record<Ymd, DayInfo>, monthDates: Ymd[]): Record<Ymd, Tier> {
  const prices: number[] = [];
  for (const d of monthDates) {
    const info = days[d];
    if (info && info.status === "available" && typeof info.price === "number")
      prices.push(info.price);
  }
  const distinct = [...new Set(prices)].sort((a, b) => a - b);
  const k = distinct.length;
  const cut = Math.max(1, Math.floor(k / 3));
  const out: Record<Ymd, Tier> = {};
  for (const d of monthDates) {
    const info = days[d];
    if (!info || info.status !== "available" || typeof info.price !== "number") continue;
    if (k <= 1) {
      out[d] = "mid";
      continue;
    }
    const idx = distinct.indexOf(info.price);
    out[d] = idx < cut ? "low" : idx >= k - cut ? "high" : "mid";
  }
  return out;
}

const NUM_LOCALE: Record<Locale, string> = { es: "es-MX", en: "en-US" };

/** "2,650" (pesos enteros, sin simbolo): para la celda del calendario. */
export function formatPesosShort(cents: number, locale: Locale): string {
  return new Intl.NumberFormat(NUM_LOCALE[locale], { maximumFractionDigits: 0 }).format(
    Math.round(cents / 100),
  );
}

/** "$2,650" o "$1,192.50" (con centavos solo si hacen falta). */
export function formatPesos(cents: number, locale: Locale): string {
  const whole = cents % 100 === 0;
  return new Intl.NumberFormat(NUM_LOCALE[locale], {
    style: "currency",
    currency: "MXN",
    currencyDisplay: "narrowSymbol",
    minimumFractionDigits: whole ? 0 : 2,
    maximumFractionDigits: whole ? 0 : 2,
  }).format(cents / 100);
}

export function monthLabel(year: number, month: number, locale: Locale): string {
  return new Intl.DateTimeFormat(NUM_LOCALE[locale], {
    month: "long",
    year: "numeric",
    timeZone: "UTC",
  }).format(new Date(Date.UTC(year, month - 1, 1)));
}

/** Encabezados de columna cortos en el orden de la semana. */
export function weekdayLabels(
  locale: Locale,
  weekStart: 0 | 1,
  width: "narrow" | "short" | "long",
): string[] {
  const fmt = new Intl.DateTimeFormat(NUM_LOCALE[locale], { weekday: width, timeZone: "UTC" });
  // 2023-01-01 fue domingo.
  return Array.from({ length: 7 }, (_, i) =>
    fmt.format(new Date(Date.UTC(2023, 0, 1 + ((weekStart + i) % 7)))),
  );
}

export function longDate(s: Ymd, locale: Locale): string {
  const p = parseYmd(s);
  if (!p) return s;
  return new Intl.DateTimeFormat(NUM_LOCALE[locale], {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
    timeZone: "UTC",
  }).format(new Date(Date.UTC(p.y, p.m - 1, p.d)));
}

const ARIA = {
  es: {
    tier: { low: "precio bajo", mid: "precio normal", high: "precio alto" },
    price: (p: string) => `${p} pesos por persona`,
    blocked: "sin vuelo",
    unavailable: "no disponible",
    selected: "seleccionado",
  },
  en: {
    tier: { low: "lower price", mid: "regular price", high: "higher price" },
    price: (p: string) => `${p} pesos per person`,
    blocked: "no flights",
    unavailable: "unavailable",
    selected: "selected",
  },
} as const;

/** Etiqueta completa para lectores de pantalla, ej. "sabado, 20 de diciembre de 2026, 2,650 pesos por persona, precio alto". */
export function dayAriaLabel(
  s: Ymd,
  info: DayInfo | undefined,
  tier: Tier | undefined,
  locale: Locale,
  selected = false,
): string {
  const a = ARIA[locale];
  const parts: string[] = [longDate(s, locale)];
  if (!info) return parts.join(", ");
  if (info.status === "available" && typeof info.price === "number") {
    parts.push(a.price(formatPesosShort(info.price, locale)));
    if (tier) parts.push(a.tier[tier]);
  } else if (info.status === "blocked") {
    parts.push(a.blocked);
  } else {
    parts.push(a.unavailable);
  }
  if (selected) parts.push(a.selected);
  return parts.join(", ");
}
