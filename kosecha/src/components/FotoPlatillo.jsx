import { useState } from 'react';
import { acentoDe } from '../data/platillos.js';

/**
 * Foto del platillo. Espera un PNG recortado con fondo transparente en
 * /assets/platillos/ (y su .webp hermano, que genera scripts/optimizar-imagenes.mjs).
 * Mientras el archivo no exista, dibuja un placeholder circular con el color
 * de la categoría — así el layout es real desde el día uno.
 */
export default function FotoPlatillo({ platillo, tamano = 320, prioridad = false, className = '' }) {
  const [falla, setFalla] = useState(false);
  const acento = acentoDe(platillo.categoria);

  if (falla || !platillo.imagen) {
    return (
      <span
        className={`foto-placeholder ${className}`}
        style={{ '--acento': acento, width: tamano, height: tamano }}
        role="img"
        aria-label={`${platillo.nombre} (foto pendiente)`}
      >
        <svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">
          <circle cx="50" cy="50" r="46" className="foto-placeholder__plato" />
          <path
            d="M50 22c12 0 22 6 26 15-8 5-17 7-26 7s-18-2-26-7c4-9 14-15 26-15Z"
            className="foto-placeholder__hoja"
          />
          <circle cx="36" cy="58" r="7" className="foto-placeholder__semilla" />
          <circle cx="58" cy="62" r="9" className="foto-placeholder__semilla" />
          <circle cx="68" cy="47" r="5" className="foto-placeholder__semilla" />
        </svg>
        <span className="foto-placeholder__inicial" aria-hidden="true">
          {(platillo.nombre.trim().split(/\s+/).pop() ?? '').charAt(0).toUpperCase()}
        </span>
      </span>
    );
  }

  const webp = platillo.imagen.replace(/\.png$/i, '.webp');

  return (
    <picture>
      <source srcSet={webp} type="image/webp" />
      <img
        className={className}
        src={platillo.imagen}
        alt={platillo.nombre}
        width={tamano}
        height={tamano}
        loading={prioridad ? 'eager' : 'lazy'}
        fetchPriority={prioridad ? 'high' : 'auto'}
        decoding={prioridad ? 'sync' : 'async'}
        onError={() => setFalla(true)}
      />
    </picture>
  );
}
