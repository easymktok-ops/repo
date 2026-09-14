# Kosecha — manual de marca (reconstruido)

> **Origen de este archivo.** El repo no traía `brand/BRAND.md`. Reconstruí lo
> que sigue leyendo el moodboard de la marca (Drive › Kosecha › "moodboards")
> y el documento MENÚ. Lo marcado como **confirmado** sale textual de esas
> fuentes; lo marcado como **por confirmar** viene del brief y hay que
> validarlo contra el manual original. Ver `../ASSETS-TODO.md`.

## Color

| Rol | Token | Hex | Estado |
| --- | --- | --- | --- |
| Verde principal | `--kos-verde` | `#1A532B` | Confirmado (moodboard) |
| Crema principal | `--kos-crema` | `#F4E9CD` | Confirmado (moodboard) |
| Acento amarillo | `--kos-amarillo` | `#FDC929` | Por confirmar |
| Acento lima | `--kos-lima` | `#9ABC1D` | Por confirmar |
| Acento naranja | `--kos-naranja` | `#EB611E` | Por confirmar |

El par verde + crema manda en todo el sitio. Los acentos solo identifican
categoría (pills del menú, viñetas de trazabilidad, botón de pedido).

## Tipografía

- **Display:** Rustic Delight — archivo `RusticDelight.woff2` pendiente. El
  `@font-face` ya está declarado en `tokens.css` apuntando a
  `/fonts/RusticDelight.woff2`, con fallback serif mientras tanto.
- **Texto:** Poppins (confirmado en el moodboard), vía Google Fonts.

## Logo

Archivos en Drive: `kosecha.png`, `kosecha1.png`, `kosecha2.png` y las
variantes "Mesa de trabajo". Van a `public/assets/logo/`. El monograma **K**
se usa en el footer sobre verde.

## Tono de voz

Cercano, mexicano, sin solemnidad: "comer sano no es pasar hambre". Habla de
tú, mezcla naturalmente algo de inglés (*wellness*, *healthy lifestyle*,
*fruits & veggies*, *zero waste*) y siempre aterriza en Libres.

## Copy aprobado (verbatim — no reescribir)

El texto de **Quiénes somos** y **Trazabilidad** está transcripto tal cual del
moodboard en `src/data/contenido.js`. Cualquier cambio se hace ahí y solo con
el texto nuevo aprobado por la marca.

## Fotografía

Comida real, madera y luz natural. Nada de gradientes multicolor,
glassmorphism ni tarjetas con borde de acento a la izquierda.

Las fotos de platillo para la rueda deben ser **PNG con fondo transparente**,
recortadas al contorno del bowl.
