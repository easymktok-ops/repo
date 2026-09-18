/**
 * Preguntas frecuentes.
 *
 * Solo van preguntas que se pueden responder con datos confirmados del menú y
 * de la ficha del local. Horarios, envío a domicilio y formas de pago siguen
 * sin confirmar, así que no figuran: inventarlas sería peor que no tenerlas.
 *
 * El bloque se muestra en la página y además alimenta el schema FAQPage, que
 * Google solo acepta si el contenido está visible.
 */
export const preguntas = [
  {
    id: 'como-pido',
    pregunta: '¿Cómo hago un pedido?',
    respuesta:
      'Elige tu platillo en el menú y toca "Pedir por WhatsApp". Se abre el chat con tu pedido ya escrito: nosotros te confirmamos disponibilidad y lo preparamos. No hay carrito ni pago en línea.',
  },
  {
    id: 'donde-estan',
    pregunta: '¿Dónde están y a qué hora abren?',
    respuesta:
      'En Calle Galeana, esquina Av. 16 de Septiembre, Libres, Puebla. Abrimos de lunes a viernes de 9:00 a 17:00; sábado y domingo cerramos. En la sección de ubicación está el mapa y el botón "Cómo llegar".',
  },
  {
    id: 'entrega',
    pregunta: '¿Hacen entrega a domicilio?',
    respuesta:
      'Sí, entregamos a domicilio desde $25 según la zona, y también puedes pasar a recogerlo a la sucursal. Nos dices cuál prefieres en el mismo chat de WhatsApp.',
  },
  {
    id: 'precios',
    pregunta: '¿Cuánto cuestan?',
    respuesta:
      'Las ensaladas cuestan $110, los sándwiches $90 y los wraps $80. Cada extra (quesos, palmito, alcachofa, elotitos, jamón serrano, lomo, salami o salmón) suma $20.',
  },
  {
    id: 'armar-ensalada',
    pregunta: '¿Puedo armar mi propia ensalada?',
    respuesta:
      'Sí. Eliges 1 base, 1 aderezo, 3 frutas o vegetales, 1 carbohidrato, 1 semilla y 1 proteína, por $110. Cada extra suma $20.',
  },
  {
    id: 'wrap-o-sandwich',
    pregunta: '¿Las ensaladas se pueden pedir como wrap o sándwich?',
    respuesta:
      'Sí, todas las ensaladas se pueden hacer wrap o sándwich. Avísanos en el mismo chat de WhatsApp al hacer el pedido.',
  },
  {
    id: 'ingredientes',
    pregunta: '¿De dónde vienen los ingredientes?',
    respuesta:
      'Las hojas verdes las elegimos cada semana en el Miércoles de Agrotianquis de La Estación, y el huevo, la miel, los quesos y la fruta de temporada vienen de productores de la región de Libres.',
  },
];
