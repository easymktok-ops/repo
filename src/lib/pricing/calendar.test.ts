import { afterEach, describe, expect, it, vi } from "vitest";
import {
  addDaysYmd,
  addMonths,
  dayAriaLabel,
  daysBetweenYmd,
  daysInMonth,
  formatPesos,
  formatPesosShort,
  isValidYmd,
  monthGrid,
  monthLabel,
  monthRange,
  priceTiers,
  shiftMonthKeepingDay,
  todayMx,
  weekBounds,
  weekdayLabels,
  weekdayOf,
  type DayInfo,
} from "./calendar";
import { clearPriceCache, getMonthPrices, parsePrices, PriceApiError } from "./api";

const avail = (price: number): DayInfo => ({ status: "available", price, deposit: 0, balance: 0 });

describe("fechas sin depender de la zona horaria", () => {
  it("valida fechas reales, incluido 29 de febrero", () => {
    expect(isValidYmd("2028-02-29")).toBe(true);
    expect(isValidYmd("2026-02-29")).toBe(false);
    expect(isValidYmd("2026-13-01")).toBe(false);
    expect(isValidYmd("2026-1-1")).toBe(false);
    expect(isValidYmd("")).toBe(false);
  });

  it("suma dias cruzando el anio y febrero bisiesto", () => {
    expect(addDaysYmd("2026-12-31", 1)).toBe("2027-01-01");
    expect(addDaysYmd("2027-01-01", -1)).toBe("2026-12-31");
    expect(addDaysYmd("2028-02-28", 1)).toBe("2028-02-29");
    expect(addDaysYmd("2027-02-28", 1)).toBe("2027-03-01");
    expect(daysBetweenYmd("2026-12-15", "2027-01-06")).toBe(22);
  });

  it("dia de la semana correcto", () => {
    expect(weekdayOf("2026-12-19")).toBe(6); // sabado
    expect(weekdayOf("2027-01-01")).toBe(5); // viernes
    expect(weekdayOf("2028-02-29")).toBe(2); // martes
  });

  it("hoy se calcula en hora de Mexico", () => {
    // 05:30 UTC del 30 de septiembre = 23:30 del 29 en CDMX.
    expect(todayMx(new Date("2026-09-30T05:30:00Z"))).toBe("2026-09-29");
    expect(todayMx(new Date("2026-09-30T07:00:00Z"))).toBe("2026-09-30");
  });

  it("no cambia con la zona horaria del proceso", () => {
    const prev = process.env.TZ;
    for (const tz of ["Pacific/Kiritimati", "America/Los_Angeles", "UTC"]) {
      process.env.TZ = tz;
      expect(addDaysYmd("2026-12-31", 1)).toBe("2027-01-01");
      expect(weekdayOf("2026-12-19")).toBe(6);
    }
    process.env.TZ = prev;
  });
});

describe("meses y cuadricula", () => {
  it("navega meses cruzando el anio", () => {
    expect(addMonths(2026, 12, 1)).toEqual({ year: 2027, month: 1 });
    expect(addMonths(2027, 1, -1)).toEqual({ year: 2026, month: 12 });
    expect(addMonths(2026, 3, -14)).toEqual({ year: 2025, month: 1 });
  });

  it("dias del mes y rango", () => {
    expect(daysInMonth(2028, 2)).toBe(29);
    expect(daysInMonth(2027, 2)).toBe(28);
    expect(monthRange(2026, 12)).toEqual({ from: "2026-12-01", to: "2026-12-31" });
  });

  it("semana en lunes (es): diciembre 2026 empieza en martes", () => {
    const g = monthGrid(2026, 12, 1);
    expect(g).toHaveLength(6);
    expect(g.every((w) => w.length === 7)).toBe(true);
    expect(g[0]).toEqual([null, "2026-12-01", "2026-12-02", "2026-12-03", "2026-12-04", "2026-12-05", "2026-12-06"]);
    expect(g.flat().filter(Boolean)).toHaveLength(31);
  });

  it("semana en domingo (en): febrero 2026 empieza en domingo", () => {
    const g = monthGrid(2026, 2, 0);
    expect(g[0][0]).toBe("2026-02-01");
    expect(g.flat().filter(Boolean)).toHaveLength(28);
  });

  it("siempre 6 filas aunque el mes ocupe 4 o 5", () => {
    expect(monthGrid(2026, 2, 0)).toHaveLength(6);
    expect(monthGrid(2028, 2, 1).flat().filter(Boolean)).toHaveLength(29);
  });

  it("limites de semana y salto de mes con dia recortado", () => {
    expect(weekBounds("2026-12-19", 1)).toEqual({ start: "2026-12-14", end: "2026-12-20" });
    expect(weekBounds("2026-12-19", 0)).toEqual({ start: "2026-12-13", end: "2026-12-19" });
    expect(shiftMonthKeepingDay("2026-01-31", 1)).toBe("2026-02-28");
    expect(shiftMonthKeepingDay("2028-01-31", 1)).toBe("2028-02-29");
    expect(shiftMonthKeepingDay("2026-03-31", -1)).toBe("2026-02-28");
  });
});

describe("niveles de precio (tercios del mes visible)", () => {
  const dates = ["2026-12-01", "2026-12-02", "2026-12-03", "2026-12-04", "2026-12-05"];

  it("si todos los precios son iguales, todos son normales", () => {
    const days = Object.fromEntries(dates.map((d) => [d, avail(220000)]));
    expect(Object.values(priceTiers(days, dates))).toEqual(["mid", "mid", "mid", "mid", "mid"]);
  });

  it("dos precios: bajo y alto", () => {
    const days = {
      "2026-12-01": avail(220000),
      "2026-12-02": avail(265000),
    };
    expect(priceTiers(days, dates)).toEqual({ "2026-12-01": "low", "2026-12-02": "high" });
  });

  it("tres precios: bajo, normal, alto", () => {
    const days = {
      "2026-12-01": avail(220000),
      "2026-12-02": avail(250000),
      "2026-12-03": avail(265000),
      "2026-12-04": avail(220000),
    };
    expect(priceTiers(days, dates)).toEqual({
      "2026-12-01": "low",
      "2026-12-02": "mid",
      "2026-12-03": "high",
      "2026-12-04": "low",
    });
  });

  it("muchos precios distintos se reparten en tercios", () => {
    const ds = Array.from({ length: 9 }, (_, i) => `2026-12-0${i + 1}`);
    const days = Object.fromEntries(ds.map((d, i) => [d, avail(200000 + i * 10000)]));
    const t = priceTiers(days, ds);
    expect(ds.map((d) => t[d])).toEqual(["low", "low", "low", "mid", "mid", "mid", "high", "high", "high"]);
  });

  it("ignora bloqueados y no disponibles, y no los cuenta para los tercios", () => {
    const days: Record<string, DayInfo> = {
      "2026-12-01": avail(220000),
      "2026-12-02": { status: "blocked" },
      "2026-12-03": { status: "unavailable" },
    };
    expect(priceTiers(days, dates)).toEqual({ "2026-12-01": "mid" });
  });
});

describe("formato y accesibilidad", () => {
  it("formatea pesos", () => {
    expect(formatPesosShort(265000, "es")).toBe("2,650");
    expect(formatPesos(265000, "es")).toBe("$2,650");
    expect(formatPesos(119250, "es")).toBe("$1,192.50");
    expect(formatPesosShort(265000, "en")).toBe("2,650");
  });

  it("etiquetas de mes y de dias en orden de semana", () => {
    expect(monthLabel(2026, 12, "es")).toBe("diciembre de 2026");
    expect(monthLabel(2026, 12, "en")).toBe("December 2026");
    expect(weekdayLabels("es", 1, "short")[0].toLowerCase()).toMatch(/^lun/);
    expect(weekdayLabels("en", 0, "short")[0]).toBe("Sun");
    expect(weekdayLabels("es", 1, "long")[6]).toBe("domingo");
  });

  it("aria-label completo", () => {
    const label = dayAriaLabel("2026-12-19", avail(265000), "high", "es");
    expect(label).toContain("sábado");
    expect(label).toContain("19 de diciembre de 2026");
    expect(label).toContain("2,650 pesos por persona");
    expect(label).toContain("precio alto");
    expect(dayAriaLabel("2026-12-25", { status: "blocked" }, undefined, "es")).toContain("sin vuelo");
    expect(dayAriaLabel("2026-12-19", avail(265000), "low", "en", true)).toContain("lower price, selected");
    expect(dayAriaLabel("2026-12-19", { status: "unavailable" }, undefined, "en")).toContain("unavailable");
  });
});

describe("cliente de /api/prices", () => {
  afterEach(() => clearPriceCache());

  const ok = (body: unknown) =>
    vi.fn().mockImplementation(() => Promise.resolve(new Response(JSON.stringify(body), { status: 200 })));
  const body = {
    enabled: true,
    currency: "MXN",
    pricingVersion: "3-abc",
    minDate: "2026-10-01",
    maxDate: "2027-09-30",
    days: { "2026-12-19": { status: "available", price: 265000, deposit: 119250, balance: 145750 } },
  };

  it("pide el mes completo del paquete y cachea", async () => {
    const f = ok(body);
    const a = await getMonthPrices("/api/prices.php", "vuelo-compartido", 2026, 12, { fetchImpl: f });
    const b = await getMonthPrices("/api/prices.php", "vuelo-compartido", 2026, 12, { fetchImpl: f });
    expect(f).toHaveBeenCalledTimes(1);
    expect(a).toBe(b);
    const url = String(f.mock.calls[0][0]);
    expect(url).toBe("/api/prices.php?packageId=vuelo-compartido&from=2026-12-01&to=2026-12-31");
  });

  it("la cache expira y distingue paquete, mes y vista previa", async () => {
    const f = ok(body);
    let t = 0;
    const opts = { fetchImpl: f, now: () => t };
    await getMonthPrices("/api/prices.php", "a", 2026, 12, opts);
    t = 61_000;
    await getMonthPrices("/api/prices.php", "a", 2026, 12, opts);
    await getMonthPrices("/api/prices.php", "b", 2026, 12, opts);
    await getMonthPrices("/api/prices.php", "b", 2027, 1, opts);
    await getMonthPrices("/api/prices.php", "b", 2027, 1, { ...opts, preview: true });
    expect(f).toHaveBeenCalledTimes(5);
    expect(String(f.mock.calls[4][0])).toContain("preview=1");
  });

  it("enabled:false se devuelve tal cual", async () => {
    const r = await getMonthPrices("/api/prices.php", "a", 2026, 12, { fetchImpl: ok({ enabled: false }) });
    expect(r).toEqual({ enabled: false });
  });

  it("errores de red, HTTP y forma invalida lanzan PriceApiError", async () => {
    await expect(
      getMonthPrices("/x", "a", 2026, 12, { fetchImpl: vi.fn().mockRejectedValue(new TypeError("fail")) }),
    ).rejects.toBeInstanceOf(PriceApiError);
    await expect(
      getMonthPrices("/x", "a", 2026, 12, {
        fetchImpl: vi.fn().mockResolvedValue(new Response("{}", { status: 500 })),
      }),
    ).rejects.toMatchObject({ status: 500 });
    await expect(getMonthPrices("/x", "a", 2026, 12, { fetchImpl: ok({ hola: 1 }) })).rejects.toBeInstanceOf(
      PriceApiError,
    );
    await expect(
      getMonthPrices("/x", "a", 2026, 12, {
        fetchImpl: vi.fn().mockResolvedValue(new Response("<html>", { status: 200 })),
      }),
    ).rejects.toBeInstanceOf(PriceApiError);
    expect(() => parsePrices({ enabled: true, days: { "2026-12-01": { status: "available" } } })).toThrow(
      PriceApiError,
    );
  });
});
