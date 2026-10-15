# Dino Snacks · demo de pedir y recoger

Sitio estático (HTML, CSS y JS sin dependencias de build). Para publicarlo en Netlify, arrastra esta carpeta `dinosnacks/` o apunta el "publish directory" a ella.

- `index.html` · página única: hero, menú, Arma tu Dino-vaso, juego Dino-Chomp y datos de recogida.
- `js/data.js` · ✏️ menú, precios, horario y WhatsApp.
- `js/dino.js` · el dino Pac-Man en SVG, chiles fantasma y pistas animadas.
- `js/game.js` · juego Dino-Chomp (canvas).
- `js/app.js` · carrito, horarios de recogida, ticket con QR, seguimiento, Dino-puntos y envío por WhatsApp.

Todo lo de pedidos, casilleros y puntos es simulado y se guarda en `localStorage`. El botón de WhatsApp sí abre un chat real con el número del negocio.
Para probar localmente: `python3 -m http.server` dentro de esta carpeta (los módulos JS no cargan con `file://`).
