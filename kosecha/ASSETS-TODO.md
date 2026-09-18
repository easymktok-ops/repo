# Pendientes de contenido y assets

Estado después de integrar lo que subiste al repo. Lo que falta no está
inventado en el código: donde no hay dato, el bloque no se muestra.

## Listo

- **Las 9 fotos de platillo.** Llegaron como tomas cenitales de 1080px sobre
  fondo blanco, sin canal alfa. Recortadas con `npm run recortar`, que borra el
  blanco de alrededor sin comerse el plato (que también es blanco): para los
  platos ajusta un círculo a su filo, y para el wrap y el sándwich, que no son
  redondos, inunda desde los bordes. Quedan en `public/assets/platillos/` en
  webp de 480 y 900px con PNG de respaldo. Los originales quedan intactos en
  `assets-origen/platillos/`.
- **Logo.** `kosecha.png` en el nav, el monograma K en crema en el footer.
  También quedó la versión crema del logotipo para fondos verdes.
- **Poppins auto-hospedada** en `public/fonts/` (`npm run fuentes`). El sitio ya
  no depende de fonts.googleapis.com, que además es un request que bloquea el
  render.
- **Video de referencia** en `reference/nutri-bowls.mp4`, analizado.
- **Foto de la fachada** en "Quiénes somos". El original queda en
  `assets-origen/fotos/fachada.jpg`; en `public/` va la versión de 900px con su
  webp.
- **Google Tag Manager** (`GTM-W82HZVCF`) en el `<head>`, con su `<noscript>`.
  El evento `pedido_whatsapp` ya llega solo a `dataLayer` en cada click de CTA,
  con el id del platillo y el origen (`rueda`, `menu`, `armador`, `nav`,
  `ubicacion`). Falta armar el activador y las etiquetas dentro de GTM.
- **Mapa del local.** El embed de la ficha de Google Maps está en la sección de
  ubicación, con `loading="lazy"`: no pide nada a Google hasta que la sección
  entra en pantalla. De ahí salen también las coordenadas del schema
  (19.4582082, -97.6876632) y el botón "Cómo llegar".
- **Dirección, horario y entrega.** Calle Galeana esquina Av. 16 de Septiembre,
  CP 73782; lunes a viernes de 9:00 a 17:00; entrega a domicilio desde $25 o
  pick up en sucursal. El sitio muestra además si el local está **abierto ahora**,
  calculado en la hora de Libres, no en la del visitante.
- **Extras a +$20** en el armador de ensaladas.
- **Smoothies y paquetes fuera del menú**: la marca todavía no los ofrece.
- **SEO local**: JSON-LD de Restaurant con el menú completo y sus precios,
  FAQPage, WebPage, sitemap.xml, robots.txt, imagen para compartir en WhatsApp
  e Instagram (`og.jpg`) y metadatos geográficos. Todo generado desde
  `src/data/` por `npm run seo`, que corre solo antes de cada build.

## 1. Tipografía display

Rustic Delight (TimelessType) ya está puesta: los títulos del sitio usan la
misma tipografía que el logo. Los OTF originales quedan en
`assets-origen/fuentes/` y `npm run display` los convierte a woff2 recortados al
latín (de 143 KB a 24 KB cada uno).

- [ ] **Confirmar que la licencia cubre uso web.** Los archivos que llegaron son
      OTF de escritorio y muchas fundiciones venden la licencia web aparte. El
      sitio sirve la fuente desde su propio dominio, que es justo lo que esa
      licencia regula. Vale una consulta a TimelessType antes de publicar; si no
      la cubre, se compra el add-on web o se cambia la display.

## 2. Datos que faltan del menú

- [ ] **Macros** (kcal, proteína, carbohidratos, grasas). El campo `macros` de
      cada platillo está en `null` y el panel no muestra el bloque. Es lo único
      que queda del menú.
- [ ] Confirmar el mapeo de acentos por categoría: ensaladas → lima,
      sándwiches → naranja, wraps → amarillo, arma la tuya → verde.

## 3. Ubicación y contacto

- [ ] Confirmar que el WhatsApp de pedidos es **+52 276 112 0304**
      (`wa.me/522761120304`), en `src/data/negocio.js` o en
      `VITE_WHATSAPP_NUMERO`.

Dirección, horario y formas de entrega ya están cargados y declarados en el
schema. Si cambian, se editan en `src/data/contenido.js` y `npm run seo`
actualiza el JSON-LD solo.

## 4. Fotos que todavía no hay

- [ ] Foto propia por variante de sándwich: los 5 comparten `sandwich.png`.
- [ ] Fotos del interior o del equipo, si más adelante se quiere una galería.

## 5. Manual de marca

- [ ] Confirmar los acentos `#FDC929`, `#9ABC1D` y `#EB611E`: salen del brief,
      no del moodboard. Para texto y botones se usan versiones más hondas
      porque los originales no llegan a 4.5:1 sobre crema.
- [ ] Faltan las 4 capturas de `web-reference/` que menciona el brief.

## 6. Medición y presencia

- [ ] Dentro de GTM: crear el activador de evento personalizado
      `pedido_whatsapp` y enviarlo a GA4 y/o a Meta. El sitio ya lo empuja; lo
      que falta es del lado del contenedor, no del código.
- [ ] **Google Business Profile.** El sitio ya declara todo lo que la ficha
      necesita, pero quien mueve la aguja en búsquedas locales es la ficha:
      conviene reclamarla, poner el mismo nombre, teléfono y dirección que el
      sitio, subir estas mismas fotos y enlazar kosecha.fit.
- [ ] **Search Console.** Dar de alta el dominio y mandar
      `https://www.kosecha.fit/sitemap.xml`.

## 7. Copy nuevo (no es copy aprobado)

El hero — "Comer sano sí sabe deli" y su bajada — lo escribí yo. "Quiénes
somos" y "Trazabilidad" sí son verbatim del manual.

- [ ] Aprobar o reemplazar el copy del hero.
