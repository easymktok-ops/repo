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
          <h1 className="hero__titulo">
            <span className="hero__kicker">Comida sana en Libres, Puebla</span>
            Comer sano <em>sí</em> sabe deli
          </h1>
          <p className="hero__bajada">
            Gira la rueda, elige tu Kosecha y pídela por WhatsApp. Te
            confirmamos disponibilidad en el chat y te la preparamos.
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
            Toca un platillo para traerlo al frente · arrastra la rueda · flechas ← →
          </p>
        </div>
      </div>
    </section>
  );
}
