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

- [ ] **Dirección exacta en Libres** (calle, número, colonia, CP). Hoy el sitio
      dice "Cobertura en Libres, Puebla" y el JSON-LD lleva localidad, no calle.
- [ ] **Horarios de atención**, para la sección y para el
      `openingHoursSpecification`.
- [ ] Confirmar que el WhatsApp de pedidos es **+52 276 112 0304**
      (`wa.me/522761120304`), en `src/lib/whatsapp.js` o en
      `VITE_WHATSAPP_NUMERO`.

## 4. Fotos que todavía no hay

- [ ] Foto para "Quiénes somos" en `public/assets/fotos/equipo.jpg` (4:5).
      Mientras no esté, ese lugar muestra un marco vacío.
- [ ] Foto propia por variante de sándwich: los 5 comparten `sandwich.png`.

## 5. Manual de marca

- [ ] Confirmar los acentos `#FDC929`, `#9ABC1D` y `#EB611E`: salen del brief,
      no del moodboard. Para texto y botones se usan versiones más hondas
      porque los originales no llegan a 4.5:1 sobre crema.
- [ ] Faltan las 4 capturas de `web-reference/` que menciona el brief.

## 6. Medición

- [ ] ID de GA4 o Pixel de Meta. `src/lib/analytics.js` ya dispara
      `pedido_whatsapp` con el id del platillo; falta cargar el tag.

## 7. Copy nuevo (no es copy aprobado)

El hero — "Comer sano sí sabe deli" y su bajada — lo escribí yo. "Quiénes
somos" y "Trazabilidad" sí son verbatim del manual.

- [ ] Aprobar o reemplazar el copy del hero.
