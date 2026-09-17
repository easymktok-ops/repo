# Design

Sistema visual del sitio de Kosecha. Los valores viven en `brand/tokens.css`;
esto explica cuándo se usa cada uno. Para el porqué estratégico, ver PRODUCT.md.

## Tema

Claro, cálido, alto contraste. El escenario: alguien parado en la calle de
Libres a las once de la mañana, con sol fuerte y el teléfono a media batería.
Nada de fondos oscuros ni de grises fríos: el papel es crema y la comida pone
el color.

## Color

Estrategia: **comprometida**. El verde y el crema son el 90% de la superficie;
los acentos identifican categoría y no decoran.

| Rol | Token | Valor | Uso |
| --- | --- | --- | --- |
| Verde | `--kos-verde` | `#1A532B` | Títulos, nav, trazabilidad, footer |
| Crema | `--kos-crema` | `#F4E9CD` | Fondos cálidos, texto sobre verde |
| Crema claro | `--kos-crema-claro` | `#FBF6EA` | Fondo de página |
| Amarillo | `--kos-amarillo` | `#FDC929` | Acento: wraps, frase de cierre |
| Lima | `--kos-lima` | `#9ABC1D` | Acento: ensaladas |
| Naranja | `--kos-naranja` | `#EB611E` | Acento: sándwiches (manchas, no texto) |
| Naranja hondo | `--kos-naranja-hondo` | `#AE4816` | CTA de WhatsApp, foco, precios |
| Lima hondo | `--kos-lima-hondo` | `#5C7111` | Etiquetas y kickers sobre crema |

**Regla dura:** los tres acentos de marca no llegan a 4.5:1 sobre crema. Para
texto y botones se usan siempre las versiones hondas. Los acentos originales
solo pintan superficies: puntos, anillos, rellenos al 18%.

El color nunca es el único indicador: las pills del menú llevan además un punto
de color y el nombre de la categoría.

## Tipografía

- **Display**: Rustic Delight (pendiente), fallback Georgia. Títulos, precios y
  la frase de cierre de trazabilidad.
- **Texto**: Poppins 300/400/500/600, auto-hospedada en `/fonts` (sin pedido a
  terceros en la ruta crítica; con el `<link>` a Google el LCP medido era de
  3,8 s y ahora es de 0,7 s).
- Escala fluida con `clamp()`, razón ≥1.25 entre pasos.
- `text-wrap: balance` en los títulos, `pretty` en la prosa larga.

## Espaciado y layout

Escala de 9 pasos (`--kos-space-1` … `--kos-space-9`). Ancho máximo 1180px,
gutter 1.25rem en móvil y 2.5rem desde 768px.

Mobile-first. La grilla del menú es `repeat(auto-fit, minmax(232px, 1fr))` y la
tarjeta destacada ocupa dos columnas y se acuesta, para que la grilla no lea
como una plantilla de tarjetas iguales.

## Componentes

- **Rueda de menú**: un contenedor que rota, hijos en `rotate(i·paso)
  translateY(-radio)` con contrarrotación por foto. Se mide contra su columna
  con `cqw`, no contra el viewport. En móvil sale por abajo del encuadre.
- **Panel de platillo**: cambia con cross-fade a mitad de la rotación, con un
  blur de 3px que funde los dos textos en vez de superponerlos. Contiene el CTA.
- **CTA de WhatsApp**: único botón del sitio. Tres variantes (naranja hondo
  sobre crema, crema sobre verde, contorno en tarjetas). 48px de alto mínimo.
- **Pills de categoría**: filtro del menú, 44px, punto de color + nombre.
- **Bowl dibujado**: ilustración de respaldo generada con los colores de los
  ingredientes. Solo aparece si la foto falta o falla.
- **Preguntas frecuentes**: `<details>` nativo, sin JavaScript. Se abre con
  teclado y con lector de pantalla; el signo más gira hasta la cruz al abrir.
- **Mapa**: iframe de Google con `loading="lazy"`, en un marco 4:3 redondeado.
  Hasta que la sección se acerca al viewport, la página no pide nada a Google.

## Movimiento

| Gesto | Duración | Curva |
| --- | --- | --- |
| Rotación de la rueda | 800 ms | `cubic-bezier(0.22, 1, 0.36, 1)` |
| Escala del bowl activo | 420 ms | `cubic-bezier(0.23, 1, 0.32, 1)` |
| Salida del panel | 180 ms | ease-ui |
| Entrada del panel | 260 ms | ease-ui |
| Press de botón | 140 ms | ease-ui, `scale(0.97)` |
| Entrada de página | 420-620 ms, escalonada 60 ms | ease-ui |
| Giro propio del plato activo | 55 s por vuelta | linear |

El plato activo gira despacio sobre sí mismo: es lo que hace la referencia con
su bowl grande y funciona porque las fotos son cenitales y redondas. Medida
contra el video, la transición completa de la referencia dura unos 2,5 s con
forma ease-in-out; acá la rueda gira en 800 ms, que es lo que pide el brief y lo
que aguanta una interacción que se repite.

La rueda es la pieza narrativa y puede durar; todo lo que responde al dedo vive
por debajo de los 300 ms. El bowl activo escala más rápido que el giro, así la
selección se siente inmediata aunque la rueda siga acomodándose.

Los hovers están detrás de `@media (hover: hover) and (pointer: fine)` para que
el tap en teléfono no los dispare. Con `prefers-reduced-motion: reduce` no hay
rotación ni entrada de página: el platillo cambia directo.

## Accesibilidad

WCAG 2.1 AA verificado con `npm run auditar`: contraste de cada nodo de texto
contra su fondo real, blancos de click de 44px y desborde horizontal en diez
anchos, de 320 a 1920. Foco visible en naranja hondo (3:1 contra crema).
