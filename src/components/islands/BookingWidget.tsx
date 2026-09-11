import { useEffect, useMemo, useRef, useState } from "react";
import { WhatsappLogo } from "@phosphor-icons/react";
import type { Locale } from "@config/site";
import { booking, type BookingMode, amountDueNow } from "@config/booking";
import "./BookingWidget.css";

/** Paquete reservable que la pagina pasa desde las Content Collections. */
export interface BookingPackage {
  slug: string;
  title: string;
  pricePerPerson: number;
  priceWas: number | null;
  currency: string;
  capacity: { min?: number; max?: number };
  durationMinutes: number | null;
}

interface Props {
  locale: Locale;
  packages: BookingPackage[];
  whatsappUrl: string;
  /** Asesores para que el cliente elija a quien escribir por WhatsApp. */
  advisors: { name: string; href: string; display: string }[];
}

const T = {
  es: {
    steps: ["Elige tu vuelo", "Datos del pasajero", "Pago"],
    pickFlight: "Elige tu vuelo",
    passengers: "Pasajeros",
    dateLabel: "Fecha de tu vuelo",
    dateHint: "Sujeta a confirmación según clima y disponibilidad.",
    errDate: "Elige la fecha de tu vuelo.",
    availNote: "¿Dudas de disponibilidad? Escríbele a un asesor:",
    weightNote: "El precio aplica hasta 99 kg por pasajero. Cada kilo extra cuesta 50 pesos, que cobra el equipo el día del vuelo.",
    optional: "opcional",
    perPerson: "por persona",
    name: "Nombre completo",
    email: "Correo",
    phone: "Telefono o WhatsApp",
    notes: "Notas (ocasion, alergias, algo especial)",
    back: "Atras",
    next: "Continuar",
    payNow: "Pagas ahora",
    balance: "Saldo en sitio",
    total: "Total del vuelo",
    howToPay: "Como quieres pagar",
    payFull: "Pagar todo",
    payFullSub: "Liquidas el vuelo completo ahora.",
    payDeposit: "Apartar mis lugares",
    payDepositSub: "Anticipo por pasajero. El resto se paga en sitio el dia del vuelo.",
    goPay: "Ir a pagar",
    processing: "Conectando con el pago seguro...",
    securedBy: "Pago seguro procesado por Stripe.",
    summary: "Tu vuelo",
    people: (n: number) => (n === 1 ? "1 pasajero" : `${n} pasajeros`),
    deposit: "Anticipo",
    errName: "Escribe tu nombre.",
    errEmail: "Escribe un correo valido.",
    errPhone: "Escribe un telefono valido.",
    errGeneric: "No pudimos iniciar el pago. Intenta de nuevo o escribenos por WhatsApp.",
    noPrice: "Este vuelo se cotiza a la medida.",
    quoteWa: "Cotizar por WhatsApp",
    was: "antes",
    minMax: (min: number, max: number) => `De ${min} a ${max} personas`,
  },
  en: {
    steps: ["Choose your flight", "Passenger details", "Payment"],
    pickFlight: "Choose your flight",
    passengers: "Passengers",
    dateLabel: "Your flight date",
    dateHint: "Subject to confirmation based on weather and availability.",
    errDate: "Choose your flight date.",
    availNote: "Questions about availability? Message an advisor:",
    weightNote: "Price applies up to 99 kg per passenger. Each extra kilo is 50 pesos, collected by the crew on the day of the flight.",
    optional: "optional",
    perPerson: "per person",
    name: "Full name",
    email: "Email",
    phone: "Phone or WhatsApp",
    notes: "Notes (occasion, allergies, anything special)",
    back: "Back",
    next: "Continue",
    payNow: "You pay now",
    balance: "Balance on site",
    total: "Flight total",
    howToPay: "How do you want to pay",
    payFull: "Pay in full",
    payFullSub: "Settle the whole flight now.",
    payDeposit: "Reserve my spots",
    payDepositSub: "A deposit per passenger. The rest is paid on site the day of the flight.",
    goPay: "Go to payment",
    processing: "Connecting to secure payment...",
    securedBy: "Secure payment processed by Stripe.",
    summary: "Your flight",
    people: (n: number) => (n === 1 ? "1 passenger" : `${n} passengers`),
    deposit: "Deposit",
    errName: "Enter your name.",
    errEmail: "Enter a valid email.",
    errPhone: "Enter a valid phone.",
    errGeneric: "We couldn't start the payment. Try again or message us on WhatsApp.",
    noPrice: "This flight is quoted to measure.",
    quoteWa: "Quote on WhatsApp",
    was: "was",
    minMax: (min: number, max: number) => `From ${min} to ${max} people`,
  },
};

function fmt(currency: string, locale: Locale, amount: number): string {
  try {
    return new Intl.NumberFormat(locale === "en" ? "en-US" : "es-MX", {
      style: "currency",
      currency: currency || "MXN",
      maximumFractionDigits: 0,
    }).format(amount);
  } catch {
    return "$" + amount.toLocaleString();
  }
}

/** Empuja un evento a dataLayer si existe (consent-gated en Analytics.astro). */
function track(event: string, data: Record<string, unknown>): void {
  const w = window as unknown as { dataLayer?: unknown[] };
  if (Array.isArray(w.dataLayer)) w.dataLayer.push({ event, ...data });
}

export default function BookingWidget({ locale, packages, whatsappUrl, advisors }: Props) {
  const t = T[locale] ?? T.es;
  const [step, setStep] = useState(1);
  const [slug, setSlug] = useState<string>(packages[0]?.slug ?? "");
  const [passengers, setPassengers] = useState(1);
  const [flightDate, setFlightDate] = useState("");
  const [mode, setMode] = useState<BookingMode>("full");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [notes, setNotes] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [touched, setTouched] = useState(false);
  const detailsRef = useRef<HTMLDivElement>(null);

  // Al elegir un vuelo: feedback visible. El resumen y los controles de
  // Pasajeros/Fecha quedan mas abajo, asi que llevamos la vista hacia ellos
  // (scroll suave) y resaltamos el bloque un instante. Sin esto, en movil se
  // siente que el sitio "no responde". Respeta prefers-reduced-motion.
  function pickFlight(next: string) {
    setSlug(next);
    const el = detailsRef.current;
    if (!el) return;
    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const rect = el.getBoundingClientRect();
    const fullyVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
    if (!fullyVisible) {
      el.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "center" });
    }
    if (!reduce) {
      // Reinicia la animacion aunque ya estuviera activa (reflow forzado).
      el.classList.remove("bk-cue");
      void el.offsetWidth;
      el.classList.add("bk-cue");
    }
  }

  // Preselecciona el paquete desde ?pkg= (viene del catalogo).
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const pkg = params.get("pkg");
    if (pkg && packages.some((p) => p.slug === pkg)) setSlug(pkg);
    if (params.get("canceled")) setError(null);
  }, [packages]);

  const selected = useMemo(
    () => packages.find((p) => p.slug === slug) ?? packages[0],
    [slug, packages],
  );

  const min = selected?.capacity.min ?? 1;
  const max = selected?.capacity.max ?? 16;

  // Reajusta pasajeros a la capacidad del paquete elegido.
  useEffect(() => {
    setPassengers((n) => Math.min(Math.max(n, min), max));
  }, [min, max]);

  if (!selected) return null;

  const currency = selected.currency || "MXN";
  const totalFull = selected.pricePerPerson * passengers;
  const now = amountDueNow(mode, selected.pricePerPerson, passengers);
  const balance = totalFull - now;

  const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

  const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
  const phoneOk = phone.replace(/\D+/g, "").length >= 8;
  const nameOk = name.trim().length >= 2;

  function goStep2() {
    setTouched(true);
    if (!flightDate) return setError(t.errDate);
    setError(null);
    setStep(2);
  }

  function goStep3() {
    setTouched(true);
    if (!nameOk) return setError(t.errName);
    if (!emailOk) return setError(t.errEmail);
    if (!phoneOk) return setError(t.errPhone);
    setError(null);
    setStep(3);
    track("begin_checkout", {
      currency,
      value: now,
      items: [{ item_id: selected.slug, item_name: selected.title, quantity: passengers }],
    });
  }

  async function submit() {
    setSubmitting(true);
    setError(null);
    try {
      const res = await fetch(booking.checkoutEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({
          packageSlug: selected.slug,
          passengers,
          mode,
          flightDate,
          name: name.trim(),
          email: email.trim(),
          phone: phone.trim(),
          notes: notes.trim(),
          locale,
        }),
      });

      // Leemos como texto y parseamos nosotros: si el backend devuelve una
      // pagina (404/500 en HTML, ruta /api mal enrutada), NO usamos res.json()
      // porque Safari lanza un error cripto ("The string did not match the
      // expected pattern"). Asi mostramos un mensaje claro con salida a WhatsApp.
      const raw = await res.text();
      let data: { url?: string; error?: string } | null = null;
      try {
        data = raw ? JSON.parse(raw) : null;
      } catch {
        data = null;
      }

      if (!res.ok || !data || !data.url) {
        if (import.meta.env.DEV) {
          console.error("checkout fallo", res.status, raw.slice(0, 300));
        }
        // Solo reusamos el mensaje del backend si vino como JSON (es en espanol
        // y seguro). Si no hubo JSON, mensaje generico con salida a WhatsApp.
        throw new Error((data && data.error) || t.errGeneric);
      }
      window.location.href = data.url;
    } catch (e) {
      const msg = e instanceof Error ? e.message : "";
      // Nunca mostramos errores crudos del navegador/red al cliente.
      const isBrowserNoise =
        !msg || /expected pattern|JSON|Unexpected|Load failed|Failed to fetch|NetworkError|fetch/i.test(msg);
      setError(isBrowserNoise ? t.errGeneric : msg);
      setSubmitting(false);
    }
  }

  return (
    <div className="bk">
      {/* Stepper */}
      <ol className="bk-steps" aria-label={t.steps.join(", ")}>
        {t.steps.map((label, i) => {
          const n = i + 1;
          const state = n === step ? "current" : n < step ? "done" : "todo";
          return (
            <li
              key={label}
              className={`bk-step is-${state}`}
              aria-current={n === step ? "step" : undefined}
            >
              <span className="bk-step-n">{n < step ? "✓" : n}</span>
              <span className="bk-step-l">{label}</span>
            </li>
          );
        })}
      </ol>

      <div className="bk-grid">
        {/* Panel del paso */}
        <div className="bk-panel">
          {step === 1 && (
            <div className="bk-fields">
              <fieldset className="bk-fs">
                <legend className="bk-legend">{t.pickFlight}</legend>
                <div className="bk-pkgs" role="radiogroup" aria-label={t.pickFlight}>
                  {packages.map((p) => {
                    const active = p.slug === slug;
                    return (
                      <button
                        type="button"
                        key={p.slug}
                        role="radio"
                        aria-checked={active}
                        className={`bk-pkg${active ? " is-active" : ""}`}
                        onClick={() => pickFlight(p.slug)}
                      >
                        <span className="bk-pkg-title">{p.title}</span>
                        <span className="bk-pkg-price">
                          {p.priceWas ? (
                            <s className="bk-pkg-was">{fmt(p.currency, locale, p.priceWas)}</s>
                          ) : null}
                          <strong>{fmt(p.currency, locale, p.pricePerPerson)}</strong>
                          <span className="bk-pkg-unit">{t.perPerson}</span>
                        </span>
                      </button>
                    );
                  })}
                </div>
              </fieldset>

              <div className="bk-row" ref={detailsRef}>
                <div className="bk-field">
                  <label className="bk-label" id="lbl-pax">
                    {t.passengers}
                  </label>
                  <div className="bk-stepper" role="group" aria-labelledby="lbl-pax">
                    <button
                      type="button"
                      className="bk-round"
                      aria-label="-1"
                      disabled={passengers <= min}
                      onClick={() => setPassengers((n) => Math.max(min, n - 1))}
                    >
                      &minus;
                    </button>
                    <output className="bk-count">{passengers}</output>
                    <button
                      type="button"
                      className="bk-round"
                      aria-label="+1"
                      disabled={passengers >= max}
                      onClick={() => setPassengers((n) => Math.min(max, n + 1))}
                    >
                      +
                    </button>
                  </div>
                  <p className="bk-hint">{t.minMax(min, max)}</p>
                </div>

                <div className="bk-field">
                  <label className="bk-label" htmlFor="bk-date">
                    {t.dateLabel}{" "}
                    <span className="bk-req" aria-hidden="true">
                      *
                    </span>
                  </label>
                  <input
                    id="bk-date"
                    className={`bk-input${touched && !flightDate ? " is-invalid" : ""}`}
                    type="date"
                    required
                    min={tomorrow}
                    value={flightDate}
                    onChange={(e) => setFlightDate((e.target as HTMLInputElement).value)}
                  />
                  <p className="bk-hint">{t.dateHint}</p>
                </div>
              </div>

              <div className="bk-advisors">
                <span className="bk-advisors-note">{t.availNote}</span>
                <div className="bk-advisors-row">
                  {advisors.map((a) => (
                    <a
                      key={a.name}
                      className="bk-wa"
                      href={a.href}
                      target="_blank"
                      rel="noopener"
                    >
                      <WhatsappLogo size={22} weight="fill" aria-hidden="true" />
                      <span className="bk-wa-text">
                        <span className="bk-wa-name">{a.name}</span>
                        <span className="bk-wa-num">{a.display}</span>
                      </span>
                    </a>
                  ))}
                </div>
              </div>

              <p className="bk-note">{t.weightNote}</p>

              <div className="bk-actions">
                <button type="button" className="bk-btn bk-primary" onClick={goStep2}>
                  {t.next}
                </button>
              </div>
            </div>
          )}

          {step === 2 && (
            <div className="bk-fields">
              <div className="bk-field">
                <label className="bk-label" htmlFor="bk-name">
                  {t.name}
                </label>
                <input
                  id="bk-name"
                  className={`bk-input${touched && !nameOk ? " is-invalid" : ""}`}
                  type="text"
                  autoComplete="name"
                  value={name}
                  onInput={(e) => setName((e.target as HTMLInputElement).value)}
                />
              </div>
              <div className="bk-row">
                <div className="bk-field">
                  <label className="bk-label" htmlFor="bk-email">
                    {t.email}
                  </label>
                  <input
                    id="bk-email"
                    className={`bk-input${touched && !emailOk ? " is-invalid" : ""}`}
                    type="email"
                    autoComplete="email"
                    value={email}
                    onInput={(e) => setEmail((e.target as HTMLInputElement).value)}
                  />
                </div>
                <div className="bk-field">
                  <label className="bk-label" htmlFor="bk-phone">
                    {t.phone}
                  </label>
                  <input
                    id="bk-phone"
                    className={`bk-input${touched && !phoneOk ? " is-invalid" : ""}`}
                    type="tel"
                    autoComplete="tel"
                    value={phone}
                    onInput={(e) => setPhone((e.target as HTMLInputElement).value)}
                  />
                </div>
              </div>
              <div className="bk-field">
                <label className="bk-label" htmlFor="bk-notes">
                  {t.notes} <span className="bk-opt">{t.optional}</span>
                </label>
                <textarea
                  id="bk-notes"
                  className="bk-input bk-textarea"
                  rows={3}
                  value={notes}
                  onInput={(e) => setNotes((e.target as HTMLTextAreaElement).value)}
                />
              </div>

              <div className="bk-actions">
                <button type="button" className="bk-btn bk-ghost" onClick={() => setStep(1)}>
                  {t.back}
                </button>
                <button type="button" className="bk-btn bk-primary" onClick={goStep3}>
                  {t.next}
                </button>
              </div>
            </div>
          )}

          {step === 3 && (
            <div className="bk-fields">
              <fieldset className="bk-fs">
                <legend className="bk-legend">{t.howToPay}</legend>
                <div className="bk-modes" role="radiogroup" aria-label={t.howToPay}>
                  <button
                    type="button"
                    role="radio"
                    aria-checked={mode === "full"}
                    className={`bk-mode${mode === "full" ? " is-active" : ""}`}
                    onClick={() => setMode("full")}
                  >
                    <span className="bk-mode-head">
                      <span className="bk-mode-title">{t.payFull}</span>
                      <span className="bk-mode-amt">{fmt(currency, locale, totalFull)}</span>
                    </span>
                    <span className="bk-mode-sub">{t.payFullSub}</span>
                  </button>
                  <button
                    type="button"
                    role="radio"
                    aria-checked={mode === "deposit"}
                    className={`bk-mode${mode === "deposit" ? " is-active" : ""}`}
                    onClick={() => setMode("deposit")}
                  >
                    <span className="bk-mode-head">
                      <span className="bk-mode-title">{t.payDeposit}</span>
                      <span className="bk-mode-amt">
                        {fmt(currency, locale, booking.depositPerPassenger * passengers)}
                      </span>
                    </span>
                    <span className="bk-mode-sub">{t.payDepositSub}</span>
                  </button>
                </div>
              </fieldset>

              {error && (
                <p className="bk-error" role="alert">
                  {error}
                </p>
              )}

              <div className="bk-actions">
                <button
                  type="button"
                  className="bk-btn bk-ghost"
                  onClick={() => setStep(2)}
                  disabled={submitting}
                >
                  {t.back}
                </button>
                <button
                  type="button"
                  className="bk-btn bk-primary bk-pay"
                  onClick={submit}
                  disabled={submitting}
                >
                  {submitting ? t.processing : `${t.goPay} · ${fmt(currency, locale, now)}`}
                </button>
              </div>
              <p className="bk-secure">{t.securedBy}</p>
              <p className="bk-alt">
                <a href={whatsappUrl} rel="noopener">
                  {t.quoteWa}
                </a>
              </p>
            </div>
          )}

          {error && step !== 3 && (
            <p className="bk-error" role="alert">
              {error}
            </p>
          )}
        </div>

        {/* Resumen boarding-pass (sticky) */}
        <aside className="bk-summary" aria-label={t.summary}>
          <div className="bk-ticket">
            <div className="bk-ticket-top">
              <span className="bk-ticket-eyebrow">{t.summary}</span>
              <h3 className="bk-ticket-title">{selected.title}</h3>
              <p className="bk-ticket-meta">
                {t.people(passengers)}
                {flightDate ? ` · ${flightDate}` : ""}
              </p>
            </div>
            <div className="bk-ticket-perf" aria-hidden="true"></div>
            <dl className="bk-ticket-lines">
              <div className="bk-line">
                <dt>{t.total}</dt>
                <dd>{fmt(currency, locale, totalFull)}</dd>
              </div>
              {mode === "deposit" && (
                <div className="bk-line bk-line-muted">
                  <dt>{t.balance}</dt>
                  <dd>{fmt(currency, locale, balance)}</dd>
                </div>
              )}
              <div className="bk-line bk-line-total">
                <dt>{t.payNow}</dt>
                <dd>{fmt(currency, locale, now)}</dd>
              </div>
            </dl>
          </div>
        </aside>
      </div>
    </div>
  );
}
