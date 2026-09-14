import { useRef, useState } from 'react';
import RuedaMenu from './RuedaMenu.jsx';
import PanelPlatillo from './PanelPlatillo.jsx';
import { platillosRueda } from '../data/platillos.js';

export default function Hero() {
  const [activo, setActivo] = useState(0);
  const refCta = useRef(null);

  return (
    <section className="hero" id="inicio">
      <div className="hero__interior">
        <div className="hero__texto">
          <p className="hero__kicker">Comida sana en Libres, Puebla</p>
          <h1 className="hero__titulo">
            Comer sano <em>sí</em> sabe deli
          </h1>
          <p className="hero__bajada">
            Girá la rueda, elegí tu Kosecha y cerrá el pedido por WhatsApp. Sin
            carrito, sin vueltas: lo preparamos y te lo confirmamos en el chat.
          </p>

          <PanelPlatillo platillo={platillosRueda[activo]} refCta={refCta} />
        </div>

        <div className="hero__rueda">
          <RuedaMenu
            platillos={platillosRueda}
            activo={activo}
            onCambiar={setActivo}
            onActivarCta={() => refCta.current?.click()}
          />
          <p className="hero__ayuda">
            Tocá un platillo para girar la rueda · deslizá con el dedo · flechas ← →
          </p>
        </div>
      </div>
    </section>
  );
}
