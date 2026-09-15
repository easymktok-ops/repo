import Logo from './Logo.jsx';
import { contacto } from '../data/contenido.js';
import { linkWhatsAppGeneral } from '../lib/whatsapp.js';

export default function Footer() {
  return (
    <footer className="pie">
      <div className="pie__interior">
        <span className="pie__mono">
          <Logo monograma alto={56} />
        </span>
        <div className="pie__cols">
          <p className="pie__frase">De la tierra de Libres a tu plato.</p>
          <ul className="pie__enlaces">
            <li>
              <a href={linkWhatsAppGeneral()} target="_blank" rel="noopener">
                WhatsApp {contacto.whatsappVisible}
              </a>
            </li>
            <li>
              <a href={contacto.instagramUrl} target="_blank" rel="noopener">
                Instagram {contacto.instagram}
              </a>
            </li>
            <li>
              <a href={contacto.webUrl}>{contacto.web}</a>
            </li>
          </ul>
        </div>
        <p className="pie__legal">
          © {new Date().getFullYear()} Kosecha · {contacto.ciudad}
        </p>
      </div>
    </footer>
  );
}
