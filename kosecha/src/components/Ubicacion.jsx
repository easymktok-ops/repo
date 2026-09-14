import BotonWhatsApp from './BotonWhatsApp.jsx';
import { contacto } from '../data/contenido.js';

const mapaUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
  'Kosecha Libres Puebla',
)}`;

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
              <dd>{contacto.whatsappVisible}</dd>
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

          <BotonWhatsApp origen="ubicacion">Pedir por WhatsApp</BotonWhatsApp>
        </div>

        <a className="ubicacion__mapa" href={mapaUrl} target="_blank" rel="noopener">
          <span className="ubicacion__mapa-pin" aria-hidden="true" />
          <span className="ubicacion__mapa-texto">
            Libres, Puebla
            <small>Abrir en Google Maps</small>
          </span>
        </a>
      </div>
    </section>
  );
}
