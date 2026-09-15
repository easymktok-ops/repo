# Pendientes de contenido y assets

Estado al armar el sitio. Nada de esto está inventado en el código: donde falta
el dato, el bloque simplemente no se muestra.

## 1. Fotos de platillo (bloqueante para la rueda)

La rueda del hero y las tarjetas del menú esperan **PNG recortados con fondo
transparente**, al contorno del bowl. Mientras el archivo no exista, cada
platillo se dibuja con los colores de sus propios ingredientes
(`src/lib/paleta.js`), así que la rueda ya distingue una Kosecha de otra y el
layout es real. Aun así son ilustraciones: las fotos son el corazón del diseño
y hay que subirlas antes de publicar.

Subir a `public/assets/platillos/` con estos nombres exactos (vienen de Drive ›
Kosecha):

| Archivo en Drive | Nombre en el repo |
| --- | --- |
| `Mediterránea.png` | `mediterranea.png` |
| `Bosque.png` | `bosque.png` |
| `Sureña.png` | `surena.png` |
| `Asiática.png` | `asiatica.png` |
| `Coles.png` | `coles.png` |
| `Kale.png` | `kale.png` |
| `Personalizada.png` | `personalizada.png` |
| `Sandiwch.png` | `sandwich.png` |
| `Wrap.png` | `wrap.png` |

Después de subirlas: `npm run optimizar`. El script genera el `.webp` de cada
una y **avisa si alguna no trae canal alfa** (es decir, si vino con fondo).

- [ ] Confirmar que los 9 PNG están recortados con transparencia.
- [ ] Falta foto propia por variante de sándwich: hoy los 5 sándwiches comparten
      `sandwich.png`.

## 2. Logo

Subir a `public/assets/logo/`:

- [ ] `kosecha.png` — logo horizontal para el nav (hoy cae en el logotipo tipográfico).
- [ ] `kosecha-monograma.png` — la K del footer.

## 3. Tipografía

- [ ] `RusticDelight.woff2` en `public/fonts/`. El `@font-face` ya está declarado
      en `brand/tokens.css`; hasta que llegue el archivo, los títulos usan la
      serif de fallback.

## 4. Datos que faltan del menú

- [ ] **Macros** (kcal, proteína, carbohidratos, grasas). No están en el
      documento MENÚ. El campo `macros` de cada platillo está en `null` y el
      panel no muestra el bloque. Al cargarlos, el panel se puede reactivar.
- [ ] **Precio de los smoothies** y de los extras (quesos, vegetales, carnes).
      Hoy figuran como texto descriptivo, sin precio.
- [ ] Confirmar el mapeo de colores de acento por categoría: ensaladas → lima,
      sándwiches → naranja, wraps → amarillo, arma la tuya → verde.

## 5. Ubicación y contacto

- [ ] **Dirección exacta en Libres** (calle, número, colonia, CP). Hoy el sitio
      solo dice "Cobertura en Libres, Puebla" y el JSON-LD lleva localidad, no
      calle. Hace falta para el SEO local y para el mapa.
- [ ] **Horarios de atención**, para mostrarlos y para el
      `openingHoursSpecification` del JSON-LD.
- [ ] Confirmar que el WhatsApp de pedidos es **+52 276 112 0304**
      (`wa.me/522761120304`). Vive en una sola constante,
      `src/lib/whatsapp.js`, y se puede pisar con `VITE_WHATSAPP_NUMERO`.

## 6. Fotografía de marca

- [ ] Foto real para "Quiénes somos" en `public/assets/fotos/equipo.jpg`
      (4:5, comida real / madera / luz natural). Mientras no esté, ese lugar
      muestra un marco vacío.

## 7. Manual de marca

El repo no tenía `brand/BRAND.md`. Lo reconstruí en `brand/BRAND.md` con lo que
sí está confirmado en el moodboard de Drive.

- [ ] Confirmar los tres acentos (`#FDC929`, `#9ABC1D`, `#EB611E`): salen del
      brief, no del moodboard.
- [ ] Faltan las 4 capturas de `web-reference/` que menciona el brief. El
      conector de Drive devuelve el texto del moodboard, no sus imágenes.

## 8. Referencia de animación

- [ ] Subir el video de Behance (ya está en Drive como
      `1-nutri-bowls-ui-design-sushmita-lakshme-1080.mp4`) a `reference/`.
      Con el archivo en el repo se extraen los frames
      (`ffmpeg -i reference/nutri-bowls.mp4 -vf fps=4 reference/frames/frame-%03d.png`)
      y se calibran duración y easing de la rueda contra la referencia.
      Hoy usa 800 ms con `cubic-bezier(0.22, 1, 0.36, 1)`, los valores del brief.

## 9. Medición

- [ ] ID de GA4 y/o Pixel de Meta. `src/lib/analytics.js` ya dispara el evento
      `pedido_whatsapp` con el id del platillo; solo falta cargar el tag.

## 10. Copy nuevo (no es copy aprobado)

El texto del hero — "Comer sano sí sabe deli" y su bajada — lo escribí yo para
llenar el hueco. Los textos de "Quiénes somos" y "Trazabilidad" sí son verbatim
del manual. Todo el copy de interfaz habla de tú, como el manual.

- [ ] Aprobar o reemplazar el copy del hero.
