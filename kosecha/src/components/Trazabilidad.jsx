import { trazabilidad } from '../data/contenido.js';

/** Texto verbatim del manual de marca: no editar acá. */
export default function Trazabilidad() {
  return (
    <section className="traza" id="trazabilidad">
      <div className="traza__interior">
        <header className="traza__cabecera">
          <h2 className="titulo-seccion titulo-seccion--crema">{trazabilidad.titulo}</h2>
          {trazabilidad.intro.map((parrafo) => (
            <p key={parrafo.slice(0, 24)}>{parrafo}</p>
          ))}
        </header>

        <ol className="traza__lista">
          {trazabilidad.puntos.map((punto, i) => (
            <li key={punto.id} className="traza__punto" style={{ '--acento': punto.acento }}>
              <span className="traza__numero" aria-hidden="true">
                {String(i + 1).padStart(2, '0')}
              </span>
              <h3>{punto.titulo}</h3>
              <p>{punto.texto}</p>
            </li>
          ))}
        </ol>

        <p className="traza__cierre">{trazabilidad.cierre}</p>
      </div>
    </section>
  );
}
