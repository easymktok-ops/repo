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
  whatsappPhone: '5213227798917',
  address: {
    street: 'Av. El Anclote 10',
    locality: 'Punta de Mita',
    region: 'Nayarit',
    postalCode: '63734',
    country: 'MX',
  },
  // Redes y reseñas (tomadas del sitio anterior)
  instagram: 'https://instagram.com/thespamita',
  facebook: 'https://www.facebook.com/thespamita',
  tripadvisor:
    'https://www.tripadvisor.com.mx/Attraction_Review-g499443-d15605997-Reviews-The_Spa_Mita-Punta_de_Mita_Pacific_Coast.html',
  // Embed de Google Maps con la dirección real
  mapEmbed:
    'https://maps.google.com/maps?output=embed&q=Av.%20El%20Anclote%2010%2C%2063734%20Punta%20de%20Mita%2C%20Nay.&z=15&t=m',
  mapLink:
    'https://www.google.com/maps/search/?api=1&query=Av.%20El%20Anclote%2010%2C%2063734%20Punta%20de%20Mita%2C%20Nayarit',
  // Backend del formulario: Web3Forms (gratis, ilimitado, sin dashboard).
  // Para activarlo: entra a https://web3forms.com, escribe info@thespamita.com,
  // te llega un "access key" por correo y lo pegas aquí. Sin cuenta ni login.
  formEndpoint: 'https://api.web3forms.com/submit',
  web3formsKey: '50dc2be4-57d8-410c-a0d5-db1dfbc03af7',
};
