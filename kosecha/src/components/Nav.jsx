import BotonWhatsApp from './BotonWhatsApp.jsx';
import Logo from './Logo.jsx';

const enlaces = [
  { href: '#menu', texto: 'Menú' },
  { href: '#nosotros', texto: 'Quiénes somos' },
  { href: '#trazabilidad', texto: 'Trazabilidad' },
  { href: '#ubicacion', texto: 'Ubicación' },
];

export default function Nav() {
  return (
    <header className="nav">
      <a className="nav__saltar" href="#menu">
        Saltar al menú
      </a>
      <div className="nav__interior">
        <a className="nav__logo" href="#inicio" aria-label="Kosecha, inicio">
          <Logo alto={44} />
        </a>
        <nav aria-label="Secciones">
          <ul className="nav__lista">
            {enlaces.map((enlace) => (
              <li key={enlace.href}>
                <a href={enlace.href}>{enlace.texto}</a>
              </li>
            ))}
          </ul>
        </nav>
        <BotonWhatsApp origen="nav" variante="crema" className="nav__cta">
          Pedir
        </BotonWhatsApp>
      </div>
    </header>
  );
}
