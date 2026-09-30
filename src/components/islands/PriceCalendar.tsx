import { useEffect, useId, useMemo, useRef, useState } from "react";
import type { KeyboardEvent } from "react";
import { CaretLeft, CaretRight } from "@phosphor-icons/react";
import type { Locale } from "@config/site";
import { getMonthPrices, peekMonthPrices, type PricesEnabled } from "@lib/pricing/api";
import {
  addDaysYmd,
  addMonths,
  dayAriaLabel,
  formatPesosShort,
  longDate,
  monthGrid,
  monthLabel,
  monthOf,
  monthRange,
  priceTiers,
  shiftMonthKeepingDay,
  todayMx,
  weekBounds,
  weekdayLabels,
  type DayInfo,
  type Tier,
  type Ymd,
} from "@lib/pricing/calendar";
import "./PriceCalendar.css";

/** Precio y anticipo POR PERSONA de la fecha elegida, en centavos. */
export interface DayQuote {
  price: number;
  deposit: number;
  balance: number;
  ruleLabel?: string | null;
}

interface Props {
  locale: Locale;
  packageSlug: string;
  /** Fecha elegida (YYYY-MM-DD) o "". */
  value: string;
  onChange: (date: Ymd, quote: DayQuote) => void;
  /** URL de /api/prices.php (sale de booking.pricesEndpoint). */
  endpoint: string;
  /** "preview" = vista previa del panel: usa la sesion del panel y muestra que tarifa aplica. */
  mode?: "public" | "preview";
  /** El servidor dijo enabled:false (bandera apagada o paquete sin anticipo). */
  onDisabled?: () => void;
}

const T = {
  es: {
    prev: "Mes anterior",
    next: "Mes siguiente",
    legend: "Precios por persona, MXN",
    low: "Más bajo",
    mid: "Normal",
    high: "Más alto",
    noFlight: "Sin vuelo",
    loading: "Cargando precios",
    error: "No pudimos cargar los precios.",
    retry: "Reintentar",
    emptyMonth: "Este mes no hay fechas disponibles.",
    nextMonth: "Ver mes siguiente",
    types: {
      base: "Precio base",
      season: "Temporada",
      date: "Fecha especial",
      weekday: "Días de la semana",
      blocked: "Sin vuelo",
    } as Record<string, string>,
  },
  en: {
    prev: "Previous month",
    next: "Next month",
    legend: "Prices per person, MXN",
    low: "Lower",
    mid: "Regular",
    high: "Higher",
    noFlight: "No flights",
    loading: "Loading prices",
    error: "We couldn't load the prices.",
    retry: "Try again",
    emptyMonth: "No dates available this month.",
    nextMonth: "See next month",
    types: {
      base: "Base price",
      season: "Season",
      date: "Special date",
      weekday: "Days of the week",
      blocked: "No flights",
    } as Record<string, string>,
  },
};

type Load =
  | { status: "loading" }
  | { status: "error" }
  | { status: "ready"; prices: PricesEnabled };

const monthKey = (y: number, m: number) => y * 12 + m;

function Pips({ tier }: { tier: Tier }) {
  return (
    <span className="pc-pips" data-tier={tier} aria-hidden="true">
      <i />
      <i />
      <i />
    </span>
  );
}

/**
 * Calendario con el precio de cada dia. NO calcula precios: muestra lo que
 * devuelve /api/prices.php. Se monta solo en el cliente (nunca se prerenderiza:
 * la fecha de hoy y los precios son dinamicos).
 */
export default function PriceCalendar({
  locale,
  packageSlug,
  value,
  onChange,
  endpoint,
  mode = "public",
  onDisabled,
}: Props) {
  const t = T[locale] ?? T.es;
  const weekStart: 0 | 1 = locale === "es" ? 1 : 0;
  const titleId = useId();

  const [visible, setVisible] = useState(() => monthOf(value || addDaysYmd(todayMx(), 1)));
  const [focusDate, setFocusDate] = useState<Ymd>(() => value || addDaysYmd(todayMx(), 1));
  const [load, setLoad] = useState<Load>({ status: "loading" });
  const [retry, setRetry] = useState(0);
  const [dir, setDir] = useState<1 | -1 | 0>(0);
  const [hover, setHover] = useState<Ymd | null>(null);

  const rootRef = useRef<HTMLDivElement>(null);
  const focusPending = useRef(false);
  const disabledCb = useRef(onDisabled);
  disabledCb.current = onDisabled;

  // Datos del mes visible (dia 1 al ultimo). Cache en memoria en api.ts.
  useEffect(() => {
    const ctrl = new AbortController();
    const cached = peekMonthPrices(endpoint, packageSlug, visible.year, visible.month, { preview: mode === "preview" });
    if (cached) {
      if (cached.enabled) setLoad({ status: "ready", prices: cached });
      else disabledCb.current?.();
      return;
    }
    setLoad({ status: "loading" });
    getMonthPrices(endpoint, packageSlug, visible.year, visible.month, {
      preview: mode === "preview",
      signal: ctrl.signal,
    })
      .then((r) => {
        if (!r.enabled) {
          disabledCb.current?.();
          return;
        }
        setLoad({ status: "ready", prices: r });
      })
      .catch((e: unknown) => {
        if (e instanceof DOMException && e.name === "AbortError") return;
        setLoad({ status: "error" });
      });
    return () => ctrl.abort();
  }, [endpoint, packageSlug, visible.year, visible.month, mode, retry]);

  // Tras navegar con teclado, lleva el foco a la celda nueva (ya renderizada).
  useEffect(() => {
    if (!focusPending.current) return;
    focusPending.current = false;
    rootRef.current?.querySelector<HTMLElement>(`[data-date="${focusDate}"]`)?.focus();
  });

  const grid = useMemo(() => monthGrid(visible.year, visible.month, weekStart), [visible, weekStart]);
  const monthDates = useMemo(() => grid.flat().filter((d): d is Ymd => d !== null), [grid]);
  const prices = load.status === "ready" ? load.prices : null;
  const days: Record<Ymd, DayInfo> = prices?.days ?? {};
  const tiers = useMemo(() => priceTiers(days, monthDates), [days, monthDates]);
  const hasAvailable = monthDates.some((d) => days[d]?.status === "available");

  const vk = monthKey(visible.year, visible.month);
  const minM = prices ? monthOf(prices.minDate) : null;
  const maxM = prices ? monthOf(prices.maxDate) : null;
  const prevDisabled = !!minM && vk <= monthKey(minM.year, minM.month);
  const nextDisabled = !!maxM && vk >= monthKey(maxM.year, maxM.month);

  function goMonth(n: number) {
    const next = addMonths(visible.year, visible.month, n);
    setDir(n > 0 ? 1 : -1);
    setVisible(next);
    setFocusDate(shiftMonthKeepingDay(focusDate, n));
  }

  function moveFocus(target: Ymd) {
    if (prices) {
      const lo = monthOf(prices.minDate);
      const hi = monthOf(prices.maxDate);
      if (target < monthRange(lo.year, lo.month).from || target > monthRange(hi.year, hi.month).to) return;
    }
    const m = monthOf(target);
    const n = monthKey(m.year, m.month) - vk;
    if (n !== 0) {
      setDir(n > 0 ? 1 : -1);
      setVisible(m);
    }
    setFocusDate(target);
    focusPending.current = true;
  }

  function pick(d: Ymd) {
    const info = days[d];
    setFocusDate(d);
    if (!info || info.status !== "available" || typeof info.price !== "number") return;
    onChange(d, {
      price: info.price,
      deposit: info.deposit ?? 0,
      balance: info.balance ?? 0,
      ruleLabel: info.ruleLabel,
    });
  }

  function onKeyDown(e: KeyboardEvent<HTMLDivElement>) {
    const map: Record<string, () => Ymd> = {
      ArrowLeft: () => addDaysYmd(focusDate, -1),
      ArrowRight: () => addDaysYmd(focusDate, 1),
      ArrowUp: () => addDaysYmd(focusDate, -7),
      ArrowDown: () => addDaysYmd(focusDate, 7),
      Home: () => weekBounds(focusDate, weekStart).start,
      End: () => weekBounds(focusDate, weekStart).end,
      PageUp: () => shiftMonthKeepingDay(focusDate, -1),
      PageDown: () => shiftMonthKeepingDay(focusDate, 1),
    };
    const fn = map[e.key];
    if (fn) {
      e.preventDefault();
      moveFocus(fn());
    } else if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      pick(focusDate);
    }
  }

  const headers = weekdayLabels(locale, weekStart, "short");
  const headersLong = weekdayLabels(locale, weekStart, "long");
  const loading = load.status === "loading";
  const previewInfo = mode === "preview" && hover ? days[hover] : undefined;

  return (
    <div className="pc" ref={rootRef} data-mode={mode}>
      <div className="pc-head">
        <button
          type="button"
          className="pc-nav"
          aria-label={t.prev}
          disabled={prevDisabled}
          onClick={() => goMonth(-1)}
        >
          <CaretLeft size={20} weight="bold" aria-hidden="true" />
        </button>
        <h3 className="pc-title" id={titleId} aria-live="polite">
          {monthLabel(visible.year, visible.month, locale)}
        </h3>
        <button
          type="button"
          className="pc-nav"
          aria-label={t.next}
          disabled={nextDisabled}
          onClick={() => goMonth(1)}
        >
          <CaretRight size={20} weight="bold" aria-hidden="true" />
        </button>
      </div>

      {load.status === "error" ? (
        <div className="pc-state" role="alert">
          <p>{t.error}</p>
          <button type="button" className="pc-btn" onClick={() => setRetry((n) => n + 1)}>
            {t.retry}
          </button>
        </div>
      ) : (
        <div
          key={`${visible.year}-${visible.month}`}
          className="pc-body"
          data-dir={dir}
          role="grid"
          aria-labelledby={titleId}
          aria-busy={loading}
          onKeyDown={onKeyDown}
        >
          <div className="pc-row pc-dow" role="row">
            {headers.map((h, i) => (
              <span key={i} role="columnheader" aria-label={headersLong[i]} className="pc-dowcell">
                {h}
              </span>
            ))}
          </div>
          {grid.map((week, wi) => (
            <div className="pc-row" role="row" key={wi}>
              {week.map((d, di) => {
                if (!d) return <span key={di} className="pc-pad" role="gridcell" aria-hidden="true" />;
                const info = days[d];
                const avail = info?.status === "available" && typeof info.price === "number";
                const blocked = info?.status === "blocked";
                const selected = d === value;
                const tier = tiers[d];
                return (
                  <button
                    key={d}
                    type="button"
                    role="gridcell"
                    className={`pc-day${selected ? " is-selected" : ""}${avail ? " is-avail" : ""}${
                      blocked ? " is-blocked" : ""
                    }${loading ? " is-loading" : ""}`}
                    data-date={d}
                    data-tier={avail ? tier : undefined}
                    tabIndex={d === focusDate ? 0 : -1}
                    aria-disabled={!avail}
                    aria-selected={selected}
                    aria-label={loading ? longDate(d, locale) : dayAriaLabel(d, info, tier, locale, selected)}
                    onClick={() => pick(d)}
                    onMouseEnter={() => setHover(d)}
                    onMouseLeave={() => setHover(null)}
                    onFocus={() => setHover(d)}
                    onBlur={() => setHover(null)}
                  >
                    <span className="pc-num">{Number(d.slice(8))}</span>
                    {loading && <span className="pc-skel" aria-hidden="true" />}
                    {avail && info && typeof info.price === "number" && (
                      <>
                        <span className="pc-price">{formatPesosShort(info.price, locale)}</span>
                        {tier && <Pips tier={tier} />}
                      </>
                    )}
                    {blocked && <span className="pc-note">{t.noFlight}</span>}
                  </button>
                );
              })}
            </div>
          ))}
        </div>
      )}

      {mode === "preview" && (
        <p className="pc-caption" aria-live="polite">
          {hover && previewInfo
            ? `${longDate(hover, locale)}: ${
                previewInfo.status === "available" || previewInfo.status === "blocked"
                  ? `${previewInfo.ruleLabel ?? t.types.base} · ${t.types[previewInfo.ruleType ?? "base"] ?? ""}`
                  : ""
              }`
            : ""}
        </p>
      )}

      {load.status === "ready" && !hasAvailable && (
        <div className="pc-state">
          <p>{t.emptyMonth}</p>
          {!nextDisabled && (
            <button type="button" className="pc-btn" onClick={() => goMonth(1)}>
              {t.nextMonth}
            </button>
          )}
        </div>
      )}

      <div className="pc-legend">
        <span className="pc-legend-title">{t.legend}</span>
        <ul className="pc-keys">
          {(["low", "mid", "high"] as Tier[]).map((k) => (
            <li key={k} className="pc-key">
              <Pips tier={k} />
              {t[k]}
            </li>
          ))}
        </ul>
      </div>
      {loading && <span className="pc-sr" role="status">{t.loading}</span>}
    </div>
  );
}
