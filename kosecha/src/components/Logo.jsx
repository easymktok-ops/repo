import { useState } from 'react';

const VARIANTES = {
  completo: { archivo: 'kosecha', w: 640, h: 190, alt: 'Kosecha, comida sana' },
  crema: { archivo: 'kosecha-crema', w: 640, h: 190, alt: 'Kosecha, comida sana' },
  monograma: { archivo: 'kosecha-monograma', w: 360, h: 430, alt: 'Kosecha' },
};

/**
 * Logo de Kosecha. Si el archivo no está, cae en el logotipo tipográfico para
 * no romper el layout.
 */
export default function Logo({ variante = 'completo', alto = 40 }) {
  const [falla, setFalla] = useState(false);
  const { archivo, w, h, alt } = VARIANTES[variante] ?? VARIANTES.completo;
  const esMonograma = variante === 'monograma';

  if (falla) {
    return (
      <span
        className={`logo-texto ${esMonograma ? 'logo-texto--mono' : ''}`}
        style={{ '--alto': `${alto}px` }}
      >
        {esMonograma ? 'K' : 'Kosecha'}
      </span>
    );
  }

  return (
    <picture>
      <source srcSet={`/assets/logo/${archivo}.webp`} type="image/webp" />
      <img
        src={`/assets/logo/${archivo}.png`}
        alt={alt}
        width={w}
        height={h}
        style={{ height: alto, width: 'auto' }}
        onError={() => setFalla(true)}
      />
    </picture>
  );
}
