import BotonWhatsApp from './BotonWhatsApp.jsx';
import { contacto, mapa } from '../data/contenido.js';
import { NUMERO_WHATSAPP } from '../lib/whatsapp.js';

export default function Ubicacion() {
  return (
    <section className="ubicacion" id="ubicacion">
      <div className="ubicacion__interior">
        <div className="ubicacion__datos">
          <h2 className="titulo-seccion">Dónde estamos</h2>

          <dl className="ubicacion__lista">
            <div>
              <dt>Zona</dt>
              <dd>{contacto.cobertura}</dd>
            </div>
            {contacto.direccion ? (
              <div>
                <dt>Dirección</dt>
                <dd>{contacto.direccion}</dd>
              </div>
            ) : null}
            {contacto.horarios ? (
              <div>
                <dt>Horarios</dt>
                <dd>{contacto.horarios}</dd>
              </div>
            ) : null}
            <div>
              <dt>WhatsApp</dt>
              <dd>
                <a href={`tel:+${NUMERO_WHATSAPP}`}>{contacto.whatsappVisible}</a>
              </dd>
            </div>
            <div>
              <dt>Instagram</dt>
              <dd>
                <a href={contacto.instagramUrl} target="_blank" rel="noopener">
                  {contacto.instagram}
                </a>
              </dd>
            </div>
          </dl>

          <div className="ubicacion__acciones">
            <BotonWhatsApp origen="ubicacion">Pedir por WhatsApp</BotonWhatsApp>
            <a
              className="btn-wa btn-wa--linea"
              href={mapa.comoLlegar}
              target="_blank"
              rel="noopener"
            >
              Cómo llegar
            </a>
          </div>
        </div>

        {/* El iframe es de Google: con loading="lazy" no pide nada hasta que la
            sección se acerca al viewport. */}
        <div className="ubicacion__mapa">
          <iframe
            src={mapa.embed}
            title="Kosecha en el mapa de Libres, Puebla"
            loading="lazy"
            referrerPolicy="strict-origin-when-cross-origin"
            allowFullScreen
          />
        </div>
      </div>
    </section>
  );
}
