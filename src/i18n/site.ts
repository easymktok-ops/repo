// Constantes del sitio compartidas entre componentes.
// NOTA: ningún CTA apunta a una URL/acción sin confirmación del cliente.
// Por ahora "Reservar cita" apunta al formulario de contacto (#contacto).

export const site = {
  name: 'The Spa Mita',
  email: 'info@thespamita.com',
  // Enlace de WhatsApp confirmado en el prompt:
  whatsapp:
    'https://api.whatsapp.com/send?phone=5213227798917&text=Hello%20i%20need%20some%20information!%20%2F%2F%20Hola%20necesito%20informaci%C3%B3n!',
  phoneDisplay: '+52 1 322 779 8917',
  address: {
    locality: 'Punta de Mita',
    region: 'Nayarit',
    country: 'MX',
  },
  // Backend del formulario: Web3Forms (gratis, ilimitado, sin dashboard).
  // Para activarlo: entra a https://web3forms.com, escribe info@thespamita.com,
  // te llega un "access key" por correo y lo pegas aquí. Sin cuenta ni login.
  formEndpoint: 'https://api.web3forms.com/submit',
  web3formsKey: 'REEMPLAZAR_ACCESS_KEY',
};
