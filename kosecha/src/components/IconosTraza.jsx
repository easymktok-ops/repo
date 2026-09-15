/**
 * Un glifo por pilar de trazabilidad. Dibujados a trazo, con el acento de cada
 * punto: reemplazan a la numeración 01/02/03, que no decía nada porque los
 * cinco puntos no son una secuencia.
 */
const trazos = {
  'verdes-locales': (
    <>
      <path d="M11 41c-2-16 8-30 28-32 2 18-9 31-28 32Z" />
      <path d="M11 41c4-10 10-17 20-22" />
    </>
  ),
  'huevo-organico': (
    <>
      <path d="M24 42c-6 0-11-4-11-11S18 10 24 10s11 14 11 21-5 11-11 11Z" />
      <path d="M19 26a5 5 0 0 1 5-5" />
    </>
  ),
  temporada: (
    <>
      <path d="M7 21h34l-4 21H11L7 21Z" />
      <path d="M16 21 24 8l8 13" />
      <path d="M18 28v7M24 28v7M30 28v7" />
    </>
  ),
  'lacteos-apicultura': (
    <>
      <path d="M24 7l14 8v16l-14 8-14-8V15l14-8Z" />
      <path d="M24 19l6 3.5v7l-6 3.5-6-3.5v-7L24 19Z" />
    </>
  ),
  'cero-desperdicio': (
    <>
      <path d="M38 24a14 14 0 1 1-5-10.7" />
      <path d="M38 8v8h-8" />
      <path d="M20 24l4 4 8-8" />
    </>
  ),
};

export default function IconoTraza({ id }) {
  const trazo = trazos[id];
  if (!trazo) return null;

  return (
    <svg className="traza__icono" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
      <g fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
        {trazo}
      </g>
    </svg>
  );
}
