import { useState } from 'react';
import { acentoDe } from '../data/platillos.js';
import { paletaDe, semilla } from '../lib/paleta.js';

/** Bowl dibujado con los colores de los ingredientes del platillo. */
function BowlDibujado({ platillo, tamano }) {
  const colores = paletaDe(platillo);
  const azar = semilla(platillo.id);
  const acento = acentoDe(platillo.categoria);

  // Dos anillos de ingredientes, sembrados con ruido determinístico.
  const trozos = [];
  const anillos = [
    { radio: 17, cantidad: 7, tamanoBase: 6.5 },
    { radio: 29, cantidad: 11, tamanoBase: 5.5 },
  ];
  for (const anillo of anillos) {
    for (let i = 0; i < anillo.cantidad; i += 1) {
      const angulo = (i / anillo.cantidad) * Math.PI * 2 + azar() * 0.5;
      const radio = anillo.radio + (azar() - 0.5) * 7;
      trozos.push({
        cx: 50 + Math.cos(angulo) * radio,
        cy: 50 + Math.sin(angulo) * radio,
        r: anillo.tamanoBase * (0.7 + azar() * 0.6),
        fill: colores[Math.floor(azar() * colores.length)],
        rotacion: azar() * 90,
      });
    }
  }

  return (
    <svg
      className="bowl"
      viewBox="0 0 100 100"
      width={tamano}
      height={tamano}
      role="img"
      aria-label={`${platillo.nombre}: ilustración provisional con los colores de sus ingredientes`}
    >
      <circle cx="50" cy="50" r="48" fill="#ffffff" />
      <circle cx="50" cy="50" r="48" fill="none" stroke={acento} strokeOpacity="0.35" strokeWidth="1.5" />
      <circle cx="50" cy="50" r="41" fill="#f7f2e4" />
      <circle cx="50" cy="50" r="38" fill={colores[0]} fillOpacity="0.22" />
      {trozos.map((t, i) => (
        <rect
          key={`${platillo.id}-${i}`}
          x={t.cx - t.r}
          y={t.cy - t.r}
          width={t.r * 2}
          height={t.r * 2}
          rx={t.r * 0.62}
          fill={t.fill}
          transform={`rotate(${t.rotacion} ${t.cx} ${t.cy})`}
        />
      ))}
    </svg>
  );
}

/**
 * Foto del platillo. Espera un PNG recortado con fondo transparente en
 * /assets/platillos/ (y su .webp hermano, que genera scripts/optimizar-imagenes.mjs).
 * Mientras el archivo no exista, dibuja el bowl de arriba.
 */
export default function FotoPlatillo({
  platillo,
  tamano = 320,
  prioridad = false,
  className = '',
  /* Tamaño real en pantalla, no el del atributo width: de esto depende qué
     variante baja el navegador. Mal puesto, se trae la de 900px al teléfono. */
  sizes = `${tamano}px`,
}) {
  const [falla, setFalla] = useState(false);

  if (falla || !platillo.imagen) {
    return <BowlDibujado platillo={platillo} tamano={tamano} />;
  }

  const base = platillo.imagen.replace(/\.png$/i, '');

  return (
    <picture>
      <source
        type="image/webp"
        srcSet={`${base}-480.webp 480w, ${base}.webp 900w`}
        sizes={sizes}
      />
      <img
        className={className}
        src={platillo.imagen}
        alt={platillo.nombre}
        width={tamano}
        height={tamano}
        loading={prioridad ? 'eager' : 'lazy'}
        fetchPriority={prioridad ? 'high' : 'low'}
        decoding={prioridad ? 'sync' : 'async'}
        onError={() => setFalla(true)}
      />
    </picture>
  );
}
