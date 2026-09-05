import { useMemo, useState, useId } from 'react';

const PHONE = '525656531771'; // +52 56 56531771

function formatDateForMsg(iso) {
  if (!iso) return 'por definir';
  const [y, m, d] = iso.split('-');
  if (!y || !m || !d) return iso;
  return `${d}/${m}/${y}`;
}

function buildWALink(packageName, price, persons, isoDate) {
  const per = persons > 1 ? 's' : '';
  const priceTxt = typeof price === 'number' ? `$${price.toLocaleString('es-MX')}` : price;
  const date = formatDateForMsg(isoDate);
  const msg = `Hola 😊 Me interesa el *Paquete ${packageName}* (${priceTxt} MXN) para *${persons} persona${per}* en una fecha aproximada de *${date}*. ¿Tienen disponibilidad?`;
  return `https://wa.me/${PHONE}?text=${encodeURIComponent(msg)}`;
}

function todayISO() {
  const t = new Date();
  t.setMinutes(t.getMinutes() - t.getTimezoneOffset());
  return t.toISOString().slice(0, 10);
}

function Check({ color }) {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" className="mt-0.5 shrink-0">
      <circle cx="12" cy="12" r="11" fill={color} />
      <path d="M7 12.5l3.2 3.2L17 9" stroke="#1D1D1D" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" fill="none" />
    </svg>
  );
}

function Stepper({ persons, setPersons, accent, idBase, max = 8 }) {
  const dec = () => setPersons((p) => Math.max(1, p - 1));
  const inc = () => setPersons((p) => Math.min(max, p + 1));
  return (
    <div>
      <label id={`${idBase}-plabel`} className="mb-1.5 block font-sub text-sm font-medium text-ink/70">
        ¿Cuántas personas?
      </label>
      <div className="inline-flex items-center gap-1 rounded-full border-2 border-ink bg-white p-1">
        <button
          type="button"
          onClick={dec}
          disabled={persons <= 1}
          aria-label="Quitar una persona"
          className="grid h-9 w-9 place-items-center rounded-full font-display text-2xl leading-none text-ink transition-colors hover:bg-cream disabled:opacity-30"
          style={{ background: persons <= 1 ? undefined : 'transparent' }}
        >
          −
        </button>
        <span
          aria-live="polite"
          aria-labelledby={`${idBase}-plabel`}
          className="w-10 text-center font-display text-2xl leading-none text-ink"
        >
          {persons}
        </span>
        <button
          type="button"
          onClick={inc}
          disabled={persons >= max}
          aria-label="Agregar una persona"
          className="grid h-9 w-9 place-items-center rounded-full font-display text-2xl leading-none text-ink transition-colors disabled:opacity-30"
          style={{ background: 'transparent' }}
          onMouseEnter={(e) => (e.currentTarget.style.background = accent)}
          onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
        >
          +
        </button>
      </div>
    </div>
  );
}

export default function PackageCard(pkg) {
  const { name, emoji, price, tagline, includes = [], accent = '#F4B400', badge, variant, image, imageAlt } = pkg;
  const isLove = variant === 'love';
  const startPersons = isLove ? 2 : 1;
  const [persons, setPersons] = useState(startPersons);
  const [date, setDate] = useState('');
  const id = useId();
  const min = useMemo(() => todayISO(), []);
  const waLink = buildWALink(name, price, persons, date);

  const controls = (
    <div className="mt-6 space-y-4">
      {isLove ? (
        <p className="font-sub text-sm font-medium text-ink/70">
          Globo privado para 2 · precio total del paquete
        </p>
      ) : (
        <Stepper persons={persons} setPersons={setPersons} accent={accent} idBase={id} />
      )}

      <div>
        <label htmlFor={`${id}-date`} className="mb-1.5 block font-sub text-sm font-medium text-ink/70">
          Fecha tentativa
        </label>
        <input
          id={`${id}-date`}
          type="date"
          min={min}
          value={date}
          onChange={(e) => setDate(e.target.value)}
          className="w-full rounded-xl border-2 border-ink bg-white px-4 py-2.5 font-body text-ink outline-none focus:border-yellow-dk"
        />
      </div>

      <a
        href={waLink}
        target="_blank"
        rel="noopener"
        className="group/btn flex w-full items-center justify-center gap-2 rounded-full border-2 border-ink px-5 py-3.5 font-sub text-lg font-semibold text-ink shadow-pop transition-transform hover:-translate-y-0.5 active:translate-y-0"
        style={{ background: accent }}
      >
        ¡Quiero este vuelo!
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2Zm5.5 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1-1.4-1-2.6s.6-1.8.9-2.1c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5.2.5.8 1.9.8 2 .1.2.1.3 0 .5s-.2.4-.3.5l-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.2.4-.2.6-.1l1.9.9c.2.1.4.2.5.3.1.3.1.7-.1 1.3Z" />
        </svg>
      </a>
    </div>
  );

  const priceBlock = (
    <div className="flex items-end gap-2">
      <span className="font-display text-5xl leading-none text-ink sm:text-6xl">
        ${price.toLocaleString('es-MX')}
      </span>
      <span className="mb-1 font-sub text-sm font-medium text-ink/60">
        MXN {isLove ? 'total' : 'por persona'}
      </span>
    </div>
  );

  const includesList = (
    <ul className="mt-5 space-y-2">
      {includes.map((item, i) => (
        <li key={i} className="flex items-start gap-2 font-body text-[15px] text-ink/85">
          <Check color={accent} />
          <span>{item}</span>
        </li>
      ))}
    </ul>
  );

  // ── Variante LOVE: layout romántico a 2 columnas ──────────────
  if (isLove) {
    return (
      <article
        className="group relative overflow-hidden rounded-blob border-2 border-ink bg-white shadow-card transition-all duration-200 hover:-translate-y-1 hover:shadow-cardHover"
        style={{ '--accent': accent }}
      >
        <div className="grid md:grid-cols-2">
          <div
            className="relative min-h-[240px] overflow-hidden md:min-h-full"
            style={{ background: accent }}
          >
            {image && (
              <img
                src={`${import.meta.env.BASE_URL}${image}`.replace(/\/{2,}/g, '/')}
                alt={imageAlt || 'Paquete LOVE'}
                loading="lazy"
                className="h-full w-full object-cover"
              />
            )}
            <span className="absolute left-4 top-4 rounded-full bg-ink px-3 py-1 font-sub text-xs font-semibold text-white">
              Exclusivo · Privado
            </span>
          </div>

          <div className="p-6 sm:p-8">
            <div className="mb-2 flex items-center gap-2">
              <span className="text-3xl" aria-hidden="true">{emoji}</span>
              <h3 className="font-display text-4xl tracking-[0.04em] text-ink sm:text-5xl">{name}</h3>
            </div>
            <p className="font-sub text-lg font-medium" style={{ color: '#d63f7d' }}>{tagline}</p>
            <div className="mt-4">{priceBlock}</div>
            {includesList}
            {controls}
          </div>
        </div>
      </article>
    );
  }

  // ── Variante estándar ─────────────────────────────────────────
  return (
    <article
      className="group relative flex h-full flex-col rounded-blob border-2 border-ink bg-white p-6 shadow-card transition-all duration-200 hover:-translate-y-1 hover:scale-[1.02] hover:shadow-cardHover sm:p-7"
    >
      {/* franja de color superior */}
      <span
        className="absolute inset-x-0 top-0 h-2.5 rounded-t-blob"
        style={{ background: accent }}
        aria-hidden="true"
      />

      {badge && (
        <span className="absolute -right-2 -top-3 rotate-3 rounded-full border-2 border-ink bg-yellow px-3 py-1 font-sub text-xs font-bold text-ink shadow-pop">
          {badge}
        </span>
      )}

      <div className="mt-2 flex items-center gap-2">
        <span className="text-2xl" aria-hidden="true">{emoji}</span>
        <h3 className="font-display text-3xl tracking-[0.04em] text-ink sm:text-4xl">{name}</h3>
      </div>
      <p className="mt-1 font-sub text-[15px] font-medium text-ink/70">{tagline}</p>

      <div className="mt-4">{priceBlock}</div>
      {includesList}

      <div className="mt-auto">{controls}</div>
    </article>
  );
}
