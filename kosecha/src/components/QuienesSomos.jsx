import { useState } from 'react';
import { quienesSomos } from '../data/contenido.js';
import { ruta } from '../lib/ruta.js';

/** Texto verbatim del manual de marca: no editar acá. */
export default function QuienesSomos() {
  const [sinFoto, setSinFoto] = useState(false);

  return (
    <section className="nosotros" id="nosotros">
      <div className="nosotros__interior">
        <figure className={`nosotros__foto ${sinFoto ? 'nosotros__foto--pendiente' : ''}`}>
          {sinFoto ? (
            <span className="nosotros__pendiente">Foto pendiente</span>
          ) : (
            <picture>
              <source srcSet={ruta('assets/fotos/fachada.webp')} type="image/webp" />
              <img
                src={ruta('assets/fotos/fachada.jpg')}
                alt="La fachada de Kosecha en Libres: toldo verde con el logotipo, puerta abierta y dos plantas en la entrada"
                width={900}
                height={1200}
                loading="lazy"
                decoding="async"
                onError={() => setSinFoto(true)}
              />
            </picture>
          )}
        </figure>

        <div className="nosotros__texto">
          <h2 className="titulo-seccion">{quienesSomos.titulo}</h2>
          {quienesSomos.parrafos.map((parrafo) => (
            <p key={parrafo.slice(0, 24)}>{parrafo}</p>
          ))}
        </div>
      </div>
    </section>
  );
}
