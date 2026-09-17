import { preguntas } from '../data/faq.js';

/**
 * FAQ con <details> nativo: se abre con teclado y con lector de pantalla sin
 * una línea de JavaScript. El schema FAQPage se genera desde estos mismos
 * datos en scripts/generar-seo.mjs.
 */
export default function Preguntas() {
  return (
    <section className="faq" id="preguntas">
      <div className="faq__interior">
        <h2 className="titulo-seccion">Preguntas frecuentes</h2>
        <ul className="faq__lista">
          {preguntas.map((p) => (
            <li key={p.id}>
              <details className="faq__item" name="faq">
                <summary>
                  {p.pregunta}
                  <span className="faq__cruz" aria-hidden="true" />
                </summary>
                <p>{p.respuesta}</p>
              </details>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
}
