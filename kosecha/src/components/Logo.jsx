import { useState } from 'react';

/**
 * Logo de Kosecha. Usa /assets/logo/kosecha.png cuando exista; mientras tanto
 * cae en el monograma tipográfico para no romper el layout.
 */
export default function Logo({ monograma = false, alto = 34 }) {
  const [falla, setFalla] = useState(false);
  const archivo = monograma ? '/assets/logo/kosecha-monograma.png' : '/assets/logo/kosecha.png';

  if (falla) {
    return (
      <span className={`logo-texto ${monograma ? 'logo-texto--mono' : ''}`} style={{ '--alto': `${alto}px` }}>
        {monograma ? 'K' : 'Kosecha'}
      </span>
    );
  }

  return (
    <img
      src={archivo}
      alt={monograma ? 'Kosecha' : 'Kosecha'}
      height={alto}
      style={{ height: alto, width: 'auto' }}
      onError={() => setFalla(true)}
    />
  );
}
