import { linkWhatsApp, linkWhatsAppGeneral } from '../lib/whatsapp.js';
import { eventoPedido } from '../lib/analytics.js';

/**
 * CTA único del sitio. `platillo` opcional: sin él genera el mensaje general.
 * `variante`: 'naranja' (sobre crema) | 'crema' (sobre verde) | 'linea'.
 */
export default function BotonWhatsApp({
  platillo = null,
  origen = 'general',
  variante = 'naranja',
  children = 'Pedir por WhatsApp',
  className = '',
  refBoton = null,
}) {
  const href = platillo ? linkWhatsApp(platillo) : linkWhatsAppGeneral();

  return (
    <a
      ref={refBoton}
      className={`btn-wa btn-wa--${variante} ${className}`}
      href={href}
      target="_blank"
      rel="noopener"
      onClick={() => eventoPedido(platillo, origen)}
    >
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" className="btn-wa__icono">
        <path
          fill="currentColor"
          d="M12.04 2c-5.5 0-9.96 4.46-9.96 9.96 0 1.76.46 3.48 1.34 5L2 22l5.19-1.36a9.9 9.9 0 0 0 4.85 1.24h.01c5.5 0 9.96-4.46 9.96-9.96 0-2.66-1.04-5.16-2.92-7.04A9.88 9.88 0 0 0 12.04 2Zm0 1.83c2.17 0 4.2.85 5.74 2.38a8.07 8.07 0 0 1 2.38 5.75c0 4.49-3.65 8.13-8.13 8.13a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.08.81.82-3-.19-.31a8.07 8.07 0 0 1-1.24-4.32c0-4.49 3.65-8.13 8.13-8.13Zm-2.6 4.1c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.34.99 2.5c.12.16 1.7 2.72 4.2 3.7 2.08.82 2.5.66 2.96.62.45-.04 1.46-.6 1.67-1.18.2-.58.2-1.08.14-1.18-.06-.1-.22-.16-.46-.28-.24-.12-1.46-.72-1.68-.8-.22-.08-.39-.12-.55.12-.16.24-.63.8-.77.96-.14.16-.28.18-.52.06-.24-.12-1.04-.38-1.98-1.22-.73-.65-1.23-1.46-1.37-1.7-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.32-.74-1.8-.2-.47-.4-.4-.55-.41h-.47Z"
        />
      </svg>
      <span>{children}</span>
    </a>
  );
}
