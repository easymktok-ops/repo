import { useEffect, useState } from 'react';

/** true si el visitante pidió menos movimiento en su sistema. */
export function useReducedMotion() {
  const [reducido, setReducido] = useState(false);

  useEffect(() => {
    const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
    const actualizar = () => setReducido(mq.matches);
    actualizar();
    mq.addEventListener('change', actualizar);
    return () => mq.removeEventListener('change', actualizar);
  }, []);

  return reducido;
}
