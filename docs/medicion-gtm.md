# Medición: GTM, Google Ads y GA4

Contenedor `GTM-KGHQBRLX` · Google Ads `AW-18320918955`.

## Cómo carga GTM en el sitio

- `src/components/analytics/Analytics.astro`, incluido por `BaseLayout` en TODAS las páginas (es y `/en/`).
  Solo `panel-vista-previa` lo apaga (página interna).
- Consent Mode v2 avanzado: consentimiento denegado por defecto, GTM se carga SIEMPRE (después de
  `window.load`). Ningún consentimiento ni condición impide que GTM cargue; sin consentimiento las
  etiquetas de Google mandan pings sin cookies (conversiones modeladas).
- No hay `<noscript>` de GTM: con Consent Mode no se puede respetar el consentimiento sin JS.

## Eventos que empuja el sitio al dataLayer

| Evento | Dónde | Datos |
|---|---|---|
| `purchase` | `/reserva-confirmada/` y `/en/reserva-confirmada/` | `transaction_id`, `value`, `currency`, `payment_type` (nivel superior) y `ecommerce.{transaction_id,value,currency,items}` |
| `whatsapp_click` | cualquier clic a `wa.me` / `api.whatsapp.com` en todo el sitio, y el formulario de contacto | `placement` (bubble, booking_advisors, contact_form, link), `link_url`, `page_path` |
| `begin_checkout` | al pulsar "Ir a pagar" | `currency`, `value`, `items` |
| `consent_granted` | al aceptar cookies | - |

`purchase` se empuja UNA vez por reserva: solo si `/api/confirm-purchase.php` confirma el pago
(reserva + `session_id` de Stripe + pago `paid`), con el monto de la base (no de la URL), y el folio
enviado queda en localStorage para no repetirlo al recargar.

## Configuración en GTM

Variables (Variable de capa de datos, versión 2):
- `DLV - transaction_id` → `transaction_id`
- `DLV - value` → `value`
- `DLV - currency` → `currency`

Activadores (Evento personalizado):
- `CE - purchase` → nombre del evento `purchase`
- `CE - whatsapp_click` → nombre del evento `whatsapp_click`

Etiquetas:
- **Ads - Compra**: Seguimiento de conversiones de Google Ads, ID `18320918955`, etiqueta
  `cegICNfD8PgcEKuTjKBE`, Valor `{{DLV - value}}`, ID de transacción `{{DLV - transaction_id}}`,
  Código de moneda `{{DLV - currency}}`. Activador: `CE - purchase`.
- **Ads - Clic WhatsApp**: Seguimiento de conversiones, ID `18320918955`, etiqueta
  `Xh5VCMzy05UdEKuTjKBE`. Activador: `CE - whatsapp_click`.
- **GA4 - purchase**: Evento de GA4, nombre `purchase`, "Enviar datos de comercio electrónico" con
  origen "Capa de datos". Activador: `CE - purchase` (NUNCA vista de página de /reserva-confirmada).
- **Vinculador de conversiones** (Conversion Linker): activador All Pages.

Quitar cualquier activador de vista de página o "Clic en enlace" que hoy dispare estas etiquetas,
para no medir doble.

## gclid / gbraid / wbraid

Se guardan al llegar desde un anuncio (cualquier página, `sessionStorage`; 90 días en
`localStorage` si aceptó cookies) y también se leen de la URL y de las cookies `_gcl_aw` / `_gcl_gb`.
Viajan con la reserva al backend (columnas `gclid`, `gbraid`, `wbraid` en `bookings`) y salen en el
CSV del panel. Sirven para importar conversiones offline en Google Ads (por ejemplo, saldo pagado
en sitio).
