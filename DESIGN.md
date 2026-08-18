# Design

Sistema visual de Aerodiverti. La fuente de verdad ejecutable es
`src/styles/tokens.css`; este documento explica el porqué.

## Dials (design-taste-frontend)

- `DESIGN_VARIANCE: 8` — composición asimétrica, arte por sección. Catálogo y
  checkout se mantienen disciplinados.
- `MOTION_INTENSITY: 7` — hero cinematográfico, doble exposición del globo,
  hover-carrusel tipo Decathlon. Todo con `prefers-reduced-motion`.
- `VISUAL_DENSITY: 4` global; **override local a 5** solo en el catálogo de
  paquetes ("lo que no se ve rápido no se vende").

## Tema

Uno solo, bloqueado: **Obsidiana** (oscuro cinematográfico). No hay variante
clara: la marca ES oscuro. Es una decisión de single-look deliberada, no una
omisión. `color-scheme: dark`.

## Color (estrategia: Committed, tirando a Drenched)

Todo en OKLCH. Referencia mental: obsidiana volcánica + cal (piedra) + un acento
de "cielo a gran altura".

- **Superficies**: `--bg` obsidiana fría `oklch(0.17 0.014 258)`, con `--surface`
  y `--surface-raised` para elevación.
- **Tinta**: `--ink` cal fría casi bone; `--muted` para secundario (pasa AA).
- **Acento único** (`--accent`, Color Consistency Lock): aqua pálido
  `oklch(0.82 0.10 205)`. Es EL acento en toda la página.
- **Ember** (`--ember`, terracota de las pirámides): terciario RARO, reservado a
  un solo momento de marca (la palabra que cruza la doble exposición). No es un
  segundo acento; nunca aparece como color de UI.

Razón anti-slop: se rechaza el dorado turístico. La lectura cultural la cargan la
tipografía, la imagen y el copy.

## Tipografía

Máximo 2 familias + 1 mono acotada. Eje de contraste real (display con carácter /
texto neutral), no dos sans genéricas.

- **Display**: Bricolage Grotesque Variable. Carácter, inktraps, aire de
  señalética/precisión → altitud y aviación, no una serif literaria de invitación.
  Se elige display grotesque a propósito para esquivar el reflejo
  editorial-magazine (serif italic + drop caps).
- **Cuerpo**: Geist Variable. Workhorse neutral, altísima legibilidad. El
  contraste "display con personalidad / texto neutro" es el eje.
- **Mono**: Geist Mono Variable, ACOTADO al motivo boarding-pass (cifras
  tabulares, folios, altitud/hora). Nunca como disfraz "técnico" en cuerpo.

Escala fluida `clamp()`, ratio ≥ 1.25, tope display ≤ ~92px. Tracking display
`-0.03em` (piso -0.04em). `text-wrap: balance` en h1–h3, `pretty` en prosa.

## Forma y elevación

Shape Consistency Lock, motivo "boleto/boarding pass": cards 14px, inputs 10px,
botones pill (999px), sellos 999px. Sombras tintadas al fondo (nunca negro puro).

## Motion (emil-design-eng)

Curvas custom en tokens: `--ease-out: cubic-bezier(0.23,1,0.32,1)` para
entradas/UI; `--ease-in-out: cubic-bezier(0.77,0,0.175,1)` para movimiento en
pantalla; `--ease-drawer` para drawers. Duraciones por elemento (press 140ms,
pop 200ms, menu 240ms, panel 320ms, hero 700ms). Reglas: nunca `transition: all`,
nunca `ease-in` en UI, `:active` con `scale(0.97)`, popovers origin-aware,
nada animado desde `scale(0)`. Interacciones continuas (hover-carrusel, magnético)
con `useMotionValue`/`useTransform` de Motion, nunca `useState`.

## Iconografía

Phosphor Icons, `strokeWidth` global consistente. Nunca Lucide por defecto, nunca
SVG a mano (salvo el favicon, marca geométrica simple).

## Patrones de referencia a replicar (brief §5)

- Hero con video `object-fit: cover` + poster = LCP precargado (`fetchpriority`);
  video diferido post-load, nunca bloquea FCP. Scrim direccional, no rectángulo.
- Cards de paquete con hairlines 1px, "Desde $X" siempre visible (theenglishbus).
- Menú lateral persistente, elevado.
- Hover con `ease-out` fuerte en puntos de decisión reales (airbus.com).
- Selector hover-carrusel tipo "ruleta" con Motion (decathlon.co.uk).
- Doble exposición: silueta de globo como `clipPath`/máscara sobre paisaje del
  valle con las pirámides al amanecer. Palabra de marca sólida cruzando, sin
  gradiente en el texto.
