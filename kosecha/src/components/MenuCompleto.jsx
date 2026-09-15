import { useMemo, useState } from 'react';
import FotoPlatillo from './FotoPlatillo.jsx';
import BotonWhatsApp from './BotonWhatsApp.jsx';
import { acentoDe, categorias, platillos, armador, smoothiesYPaquetes } from '../data/platillos.js';

export default function MenuCompleto() {
  const [filtro, setFiltro] = useState('todo');

  const visibles = useMemo(
    () => (filtro === 'todo' ? platillos : platillos.filter((p) => p.categoria === filtro)),
    [filtro],
  );

  const pills = [{ id: 'todo', nombre: 'Todo el menú', acento: 'var(--kos-verde)' }, ...categorias];

  return (
    <section className="menu" id="menu">
      <div className="menu__interior">
        <aside className="menu__filtros">
          <h2 className="titulo-seccion">Menú completo</h2>
          <p className="menu__nota">
            Todas las ensaladas se pueden hacer wrap o sándwich.
          </p>
          <ul className="menu__pills" role="list">
            {pills.map((pill) => (
              <li key={pill.id}>
                <button
                  type="button"
                  className={`pill ${filtro === pill.id ? 'pill--activa' : ''}`}
                  style={{ '--acento': pill.acento }}
                  aria-pressed={filtro === pill.id}
                  onClick={() => setFiltro(pill.id)}
                >
                  <span className="pill__marca" aria-hidden="true" />
                  {pill.nombre}
                  {pill.precioDesde ? <span className="pill__precio">${pill.precioDesde}</span> : null}
                </button>
              </li>
            ))}
          </ul>
        </aside>

        <div className="menu__grid">
          {visibles.map((platillo) => (
            <article
              key={platillo.id}
              className={`tarjeta ${platillo.destacado ? 'tarjeta--destacada' : ''}`}
              style={{ '--acento': acentoDe(platillo.categoria) }}
            >
              <div className="tarjeta__foto">
                <FotoPlatillo platillo={platillo} tamano={platillo.destacado ? 320 : 220} />
              </div>
              <div className="tarjeta__cuerpo">
                {platillo.destacado ? <p className="tarjeta__etiqueta">{platillo.etiqueta}</p> : null}
                <h3 className="tarjeta__nombre">{platillo.nombre}</h3>
                <p className="tarjeta__descripcion">{platillo.descripcion}</p>
                <p className="tarjeta__precio">${platillo.precio} MXN</p>
                <BotonWhatsApp platillo={platillo} origen="menu" variante="linea" />
              </div>
            </article>
          ))}
        </div>
      </div>

      <div className="armador" id="arma-tu-ensalada">
        <div className="armador__interior">
          <div className="armador__cabecera">
            <h3>{armador.titulo}</h3>
            <p className="armador__precio">${armador.precio} MXN</p>
            <p className="armador__instruccion">{armador.instruccion}</p>
          </div>
          <ol className="armador__pasos">
            {armador.pasos.map((paso) => (
              <li key={paso.n}>
                <span className="armador__n">{paso.n}</span>
                <div>
                  <h4>{paso.nombre}</h4>
                  <p>{paso.opciones.join(' · ')}</p>
                </div>
              </li>
            ))}
          </ol>
          <div className="armador__extras">
            <h4>Extras</h4>
            <ul>
              {armador.extras.map((extra) => (
                <li key={extra.nombre}>
                  <strong>{extra.nombre}:</strong> {extra.opciones.join(', ')}
                </li>
              ))}
            </ul>
          </div>
          <div className="armador__combos">
            <p>
              <strong>Smoothies.</strong> {smoothiesYPaquetes.smoothies}
            </p>
            <p>
              <strong>Paquetes.</strong> {smoothiesYPaquetes.paquetes}
            </p>
          </div>
          <BotonWhatsApp origen="armador">Armar mi ensalada por WhatsApp</BotonWhatsApp>
        </div>
      </div>
    </section>
  );
}
