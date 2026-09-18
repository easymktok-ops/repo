import { useEffect, useState } from 'react';
import BotonWhatsApp from './BotonWhatsApp.jsx';
import { contacto, entrega, horarios, mapa } from '../data/contenido.js';
import { NUMERO_WHATSAPP } from '../lib/whatsapp.js';
import { estadoDelLocal, formatoHora } from '../lib/horario.js';

export default function Ubicacion() {
  const [estado, setEstado] = useState(() => estadoDelLocal());

  // La página puede quedar abierta horas: el estado se refresca cada minuto.
  useEffect(() => {
    const id = setInterval(() => setEstado(estadoDelLocal()), 60_000);
    return () => clearInterval(id);
  }, []);

  return (
    <section className="ubicacion" id="ubicacion">
      <div className="ubicacion__interior">
        <div className="ubicacion__datos">
          <h2 className="titulo-seccion">Dónde estamos</h2>

          <p className={`estado ${estado.abierto ? 'estado--abierto' : 'estado--cerrado'}`}>
            <span className="estado__punto" aria-hidden="true" />
            {estado.abierto ? (
              <>
                <strong>Abierto ahora</strong> · cierra a las {formatoHora(estado.cierra)}
              </>
            ) : (
              <>
                <strong>Cerrado ahora</strong>
                {estado.proximo
                  ? ` · abre ${estado.proximo.cuando} a las ${formatoHora(estado.proximo.hora)}`
                  : null}
              </>
            )}
          </p>

          <dl className="ubicacion__lista">
            <div>
              <dt>Dirección</dt>
              <dd>
                {contacto.direccion}
                <br />
                {contacto.codigoPostal} {contacto.ciudad}
              </dd>
            </div>
            <div>
              <dt>Horario</dt>
              <dd>
                <ul className="ubicacion__horario">
                  {horarios.tramos.map((tramo) => (
                    <li key={tramo.etiqueta}>
                      <span>{tramo.etiqueta}</span>
                      <span>
                        {tramo.abre
                          ? `${formatoHora(tramo.abre)} a ${formatoHora(tramo.cierra)}`
                          : 'Cerrado'}
                      </span>
                    </li>
                  ))}
                </ul>
              </dd>
            </div>
            <div>
              <dt>Cómo lo recibes</dt>
              <dd>
                {entrega.domicilio.texto} o {entrega.sucursal.texto.toLowerCase()}.
              </dd>
            </div>
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
