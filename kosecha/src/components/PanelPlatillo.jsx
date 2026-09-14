import { useEffect, useRef, useState } from 'react';
import BotonWhatsApp from './BotonWhatsApp.jsx';
import { acentoDe, categorias } from '../data/platillos.js';
import { useReducedMotion } from '../lib/useReducedMotion.js';

/** El contenido cambia a mitad de la rotación, nunca de golpe. */
const RETARDO_SWAP = 320;

export default function PanelPlatillo({ platillo, refCta }) {
  const reducido = useReducedMotion();
  const [mostrado, setMostrado] = useState(platillo);
  const [visible, setVisible] = useState(true);
  const temporizador = useRef(null);

  // Con menos movimiento el cambio es directo: se muestra `platillo` sin
  // pasar por el cross-fade, así no hace falta sincronizar estado.
  const datos = reducido ? platillo : mostrado;

  useEffect(() => {
    if (reducido || platillo.id === mostrado.id) return undefined;
    // El fade se sincroniza con un temporizador (sistema externo): el texto
    // sale, el contenido cambia a mitad de la rotación y vuelve a entrar.
    // oxlint-disable-next-line react/set-state-in-effect
    setVisible(false);
    clearTimeout(temporizador.current);
    temporizador.current = setTimeout(() => {
      setMostrado(platillo);
      setVisible(true);
    }, RETARDO_SWAP);
    return () => clearTimeout(temporizador.current);
  }, [platillo, mostrado.id, reducido]);

  const acento = acentoDe(datos.categoria);
  const categoria = categorias.find((c) => c.id === datos.categoria);

  return (
    <div
      className={`panel ${visible ? 'panel--visible' : 'panel--saliendo'}`}
      style={{ '--acento': acento }}
    >
      <p className="panel__categoria">
        <span className="panel__punto" aria-hidden="true" />
        {datos.etiqueta ?? categoria?.nombre}
      </p>

      <div aria-live="polite" aria-atomic="true">
        <h2 className="panel__nombre">{datos.nombre}</h2>
        <p className="panel__descripcion">{datos.descripcion}</p>
      </div>

      <ul className="panel__ingredientes">
        {datos.ingredientes.map((ingrediente) => (
          <li key={ingrediente}>{ingrediente}</li>
        ))}
      </ul>

      <div className="panel__pie">
        <p className="panel__precio">
          <span className="panel__moneda">$</span>
          {datos.precio}
          <span className="panel__mxn">MXN</span>
        </p>
        <BotonWhatsApp platillo={datos} origen="rueda" refBoton={refCta} />
      </div>
    </div>
  );
}
