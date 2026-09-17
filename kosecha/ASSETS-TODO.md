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
- **Mapa del local.** El embed de la ficha de Google Maps está en la sección de
  ubicación, con `loading="lazy"`: no pide nada a Google hasta que la sección
  entra en pantalla. De ahí salen también las coordenadas del schema
  (19.4582082, -97.6876632) y el botón "Cómo llegar".
- **SEO local**: JSON-LD de Restaurant con el menú completo y sus precios,
  FAQPage, WebPage, sitemap.xml, robots.txt, imagen para compartir en WhatsApp
  e Instagram (`og.jpg`) y metadatos geográficos. Todo generado desde
  `src/data/` por `npm run seo`, que corre solo antes de cada build.

## 1. Tipografía display

- [ ] `RusticDelight.woff2` en `public/fonts/`. El `@font-face` está declarado
      en `brand/tokens.css`; hasta que llegue, los títulos usan la serif de
      respaldo. Es lo único que hoy separa al sitio del logo, que sí usa la
      serif de marca.

## 2. Datos que faltan del menú

El MENÚ.docx que subiste dice exactamente lo mismo que el documento de Drive,
así que estos siguen abiertos:

- [ ] **Macros** (kcal, proteína, carbohidratos, grasas). El campo `macros` de
      cada platillo está en `null` y el panel no muestra el bloque.
- [ ] **Precio de los smoothies** y de los extras (quesos, vegetales, carnes).
- [ ] Confirmar el mapeo de acentos por categoría: ensaladas → lima,
      sándwiches → naranja, wraps → amarillo, arma la tuya → verde.

## 3. Ubicación y contacto

- [ ] **Dirección exacta en Libres** (calle, número, colonia, CP). El mapa ya
      ubica el local, pero el schema lleva localidad y coordenadas, no calle.
      Google Business Profile la pide igual que el sitio: conviene que digan
      exactamente lo mismo, letra por letra.
- [ ] **Horarios de atención**. Es lo que más falta para el SEO local: sin
      ellos no se declara `openingHoursSpecification` y Google no puede mostrar
      "Abierto ahora" en los resultados. Un horario inventado manda gente al
      local cuando está cerrado, así que el schema lo omite a propósito.
- [ ] **¿Hay entrega a domicilio?** Si la hay, se declara el área y el tipo de
      servicio; hoy el schema solo dice que se retira en el local.
- [ ] Confirmar que el WhatsApp de pedidos es **+52 276 112 0304**
      (`wa.me/522761120304`), en `src/lib/whatsapp.js` o en
      `VITE_WHATSAPP_NUMERO`.

## 4. Fotos que todavía no hay

- [ ] Foto de la fachada para "Quiénes somos" en
      `public/assets/fotos/fachada.jpg`. Mientras no esté, ese lugar muestra un
      marco vacío. Se puede subir directo desde GitHub a esa carpeta.
- [ ] Foto propia por variante de sándwich: los 5 comparten `sandwich.png`.

## 5. Manual de marca

- [ ] Confirmar los acentos `#FDC929`, `#9ABC1D` y `#EB611E`: salen del brief,
      no del moodboard. Para texto y botones se usan versiones más hondas
      porque los originales no llegan a 4.5:1 sobre crema.
- [ ] Faltan las 4 capturas de `web-reference/` que menciona el brief.

## 6. Medición y presencia

- [ ] ID de GA4 o Pixel de Meta. `src/lib/analytics.js` ya dispara
      `pedido_whatsapp` con el id del platillo; falta cargar el tag.
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
