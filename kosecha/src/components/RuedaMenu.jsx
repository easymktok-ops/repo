import { useEffect, useRef, useState } from 'react';
import FotoPlatillo from './FotoPlatillo.jsx';
import { acentoDe } from '../data/platillos.js';
import { useReducedMotion } from '../lib/useReducedMotion.js';

/** Lleva cualquier diferencia de ángulo al rango (-180, 180]: camino más corto. */
const norm180 = (grados) => ((((grados + 180) % 360) + 360) % 360) - 180;

/**
 * La rueda de menú.
 *
 * Un único contenedor rota con transform: rotate(); los hijos se ubican con
 * rotate(i * paso) translateY(-radio) y cada foto contrarrota el mismo ángulo
 * para no quedar nunca de cabeza. El ángulo se acumula (nunca se aplica
 * módulo 360) para que siempre gire por el camino más corto.
 */
export default function RuedaMenu({ platillos, activo, onCambiar, onActivarCta }) {
  const n = platillos.length;
  const paso = 360 / n;
  const reducido = useReducedMotion();

  const refRueda = useRef(null);
  const anguloRef = useRef(-activo * paso);
  const arrastreRef = useRef(null);
  const bloquearClickRef = useRef(false);
  const [angulo, setAngulo] = useState(-activo * paso);
  const [arrastrando, setArrastrando] = useState(false);

  const fijarAngulo = (valor) => {
    anguloRef.current = valor;
    setAngulo(valor);
  };

  // El activo manda: la rueda gira hasta ponerlo arriba, por el camino corto.
  useEffect(() => {
    if (arrastreRef.current) return;
    fijarAngulo(anguloRef.current + norm180(-activo * paso - anguloRef.current));
  }, [activo, paso]);

  // Roving tabindex: si el foco ya estaba en la rueda, lo seguimos al nuevo activo.
  useEffect(() => {
    const rueda = refRueda.current;
    if (!rueda || !rueda.contains(document.activeElement)) return;
    rueda.querySelector('[data-activo="true"]')?.focus();
  }, [activo]);

  const anguloDelPuntero = (evento) => {
    const caja = refRueda.current.getBoundingClientRect();
    const cx = caja.left + caja.width / 2;
    const cy = caja.top + caja.height / 2;
    return (Math.atan2(evento.clientY - cy, evento.clientX - cx) * 180) / Math.PI;
  };

  const alPresionar = (evento) => {
    if (reducido || (evento.button !== undefined && evento.button > 0)) return;
    arrastreRef.current = { ultimo: anguloDelPuntero(evento), recorrido: 0, capturado: false };
    bloquearClickRef.current = false;
    setArrastrando(true);
  };

  const alMover = (evento) => {
    const arrastre = arrastreRef.current;
    if (!arrastre) return;
    const actual = anguloDelPuntero(evento);
    const delta = norm180(actual - arrastre.ultimo);
    arrastre.ultimo = actual;
    arrastre.recorrido += Math.abs(delta);
    // La captura de puntero recién arranca cuando el gesto es un arrastre real:
    // si capturamos en el pointerdown, el click se redirige a la rueda y el
    // botón del bowl nunca lo recibe (tap sin selección).
    if (arrastre.recorrido > 4 && !arrastre.capturado) {
      arrastre.capturado = true;
      bloquearClickRef.current = true;
      refRueda.current?.setPointerCapture?.(evento.pointerId);
    }
    fijarAngulo(anguloRef.current + delta);
  };

  const alSoltar = (evento) => {
    const arrastre = arrastreRef.current;
    if (!arrastre) return;
    arrastreRef.current = null;
    setArrastrando(false);
    if (arrastre.capturado && refRueda.current?.hasPointerCapture?.(evento.pointerId)) {
      refRueda.current.releasePointerCapture(evento.pointerId);
    }

    // snap al platillo más cercano
    const indice = ((Math.round(-anguloRef.current / paso) % n) + n) % n;
    fijarAngulo(anguloRef.current + norm180(-indice * paso - anguloRef.current));
    if (indice !== activo) onCambiar(indice);
  };

  const alTeclear = (evento) => {
    if (evento.key === 'ArrowRight' || evento.key === 'ArrowDown') {
      evento.preventDefault();
      onCambiar((activo + 1) % n);
    } else if (evento.key === 'ArrowLeft' || evento.key === 'ArrowUp') {
      evento.preventDefault();
      onCambiar((activo - 1 + n) % n);
    } else if (evento.key === 'Home') {
      evento.preventDefault();
      onCambiar(0);
    } else if (evento.key === 'End') {
      evento.preventDefault();
      onCambiar(n - 1);
    }
  };

  const clases = [
    'rueda',
    arrastrando ? 'rueda--arrastrando' : '',
    reducido ? 'rueda--sin-movimiento' : '',
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <div className="rueda-marco">
      <div
        ref={refRueda}
        className={clases}
        style={{ '--angulo': angulo, '--paso': `${paso}deg` }}
        role="group"
        aria-label="Selector de platillos. Usa las flechas para girar la rueda."
        onKeyDown={alTeclear}
        onPointerDown={alPresionar}
        onPointerMove={alMover}
        onPointerUp={alSoltar}
        onPointerCancel={alSoltar}
      >
        {platillos.map((platillo, i) => {
          const esActivo = i === activo;
          return (
            <div key={platillo.id} className="rueda__radio" style={{ '--i': i }}>
              <button
                type="button"
                className={`rueda__bowl ${esActivo ? 'rueda__bowl--activo' : ''}`}
                style={{ '--acento': acentoDe(platillo.categoria) }}
                aria-pressed={esActivo}
                data-activo={esActivo}
                aria-label={`${platillo.nombre}. $${platillo.precio} pesos.${
                  esActivo ? ' Seleccionado. Enter para pedirlo por WhatsApp.' : ''
                }`}
                tabIndex={esActivo ? 0 : -1}
                onClick={() => {
                  if (bloquearClickRef.current) return;
                  if (!esActivo) onCambiar(i);
                }}
                onKeyDown={(evento) => {
                  if (evento.key !== 'Enter' && evento.key !== ' ') return;
                  evento.preventDefault();
                  evento.stopPropagation();
                  if (esActivo) onActivarCta?.();
                  else onCambiar(i);
                }}
              >
                <span className="rueda__foto">
                  <FotoPlatillo
                    platillo={platillo}
                    tamano={260}
                    sizes="(min-width: 900px) 215px, 40vw"
                    prioridad={esActivo}
                  />
                </span>
              </button>
            </div>
          );
        })}
      </div>

      <div className="rueda__puntos" aria-hidden="true">
        {platillos.map((platillo, i) => (
          <span
            key={platillo.id}
            className={`rueda__punto ${i === activo ? 'rueda__punto--activo' : ''}`}
            style={{ '--acento': acentoDe(platillo.categoria) }}
          />
        ))}
      </div>
    </div>
  );
}
