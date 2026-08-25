# Backlog visual — Aerodiverti

Cambios de diseño/UI acumulados por el cliente. Se implementaron en bloque el
**2026-08-25** ("dale, ataquemos el backlog visual").

Estado: `PEND` = pendiente · `LISTO` = aplicado · `DUDA` = requiere decisión/asset.

---

## Resumen de implementación (2026-08-25)

| Item | Qué | Estado |
| --- | --- | --- |
| VIS-00 | Acento de marca aqua -> fucsia | **LISTO** |
| VIS-01 | CTAs en fucsia | **LISTO** |
| VIS-02 | Favicon fucsia bold | **LISTO** |
| VIS-03 | Logo en el menú | **LISTO** (wordmark tratado para oscuro) |
| VIS-04 | Favoritos en fucsia | **LISTO** |
| VIS-05 | Outlines de reservas en fucsia | **LISTO** |
| VIS-06 | Padding de "Elegir fecha" | **LISTO** |
| VIS-07 | Parallax del hero | **LISTO** (se implementó de cero) |
| VIS-08 | Quitar marca de agua "Teotihuacán" | **LISTO** |
| VIS-09 | Probar fondo claro | `DUDA` -> recomendación: **mantener oscuro** (ver nota) |
| VIS-10 | Globopuerto: globos en tierra | `DUDA` (bloqueado: fotos del Drive) |
| VIS-11 | Feedback al elegir vuelo (auto-scroll) | **LISTO** |

Todo el cambio de color es sistémico: sale de un solo token `--accent` en
`src/styles/tokens.css`. No hay fucsia hardcodeado en componentes.

---

## Dirección de marca (transversal) — RESUELTO (VIS-00)

**Decisión aplicada:** el acento único pasa **por completo** de aqua a **fucsia
de marca**. Se conserva la regla "un solo acento" de DESIGN.md/CLAUDE.md; solo
cambia el matiz. El aqua "cielo alto" queda retirado.

El fucsia de marca puro del logo es `#B4438C` = `oklch(0.56 0.167 344)`. Ese tono
es demasiado oscuro para leer como **texto** sobre el fondo Obsidiana (L 0.17).
El token quedó afinado a `oklch(0.74 0.145 344)` (mismo matiz, más claro) para
pasar AA en sus dos papeles:

- como texto/icono/borde sobre `--bg`: **~7.7:1** (AA cuerpo, AAA large)
- como fondo de botón con `--accent-ink` (tinta oscura) encima: **~7.7:1**

## Paleta de pinceladas (acuarela del globo)

Añadida a `tokens.css` como `--brush-*` (OKLCH), documentada **solo para
micro-detalle** (bullets/puntos), nunca como color de UI. Aún **no** se cablea a
ningún componente: se hará en un punto concreto y con criterio para no romper la
disciplina de un solo acento. Si quieres, dime en qué lista/bloque la aplico.

- lima `oklch(0.852 0.173 128)` · verde `oklch(0.862 0.121 139)` ·
  amarillo `oklch(0.866 0.152 113)` · naranja `oklch(0.777 0.117 66)` ·
  coral `oklch(0.793 0.09 24)` · rosa `oklch(0.799 0.109 344)` ·
  cian `oklch(0.898 0.081 193)`

---

## 1. Color y marca

- **VIS-01 · CTAs en fucsia** `LISTO`
  Todos los botones de acción (hero, rail, "Elegir fecha", "Ir a pagar",
  "Continuar", etc.) usan `--accent`, ahora fucsia. Cambio de un solo token.

- **VIS-02 · Favicon** `LISTO`
  Rediseñado en `public/favicon.svg`: silueta de globo **rellena en fucsia**
  (con gajos y canasta), legible y con carácter a 16px. Sustituye al globo de
  trazo fino aqua que se perdía en pequeño.

- **VIS-03 · Logo en el menú** `LISTO`
  El logo original es line-art negro + wordmark fucsia sobre **blanco** (para
  fondo claro). Como mantenemos el tema oscuro (VIS-09), extraje el **wordmark
  "Aerodiverti"** del logo, lo aislé del fondo (transparente) y lo normalicé al
  **fucsia de marca** del sitio. Reemplaza el texto en el rail (desktop) y en la
  barra superior (móvil). Assets: `src/assets/brand/aerodiverti-wordmark-dark.png`
  (usado en el sitio vía Astro Image) y `public/aerodiverti-wordmark.svg` (SVG
  drop-in, envuelve el PNG). Nota honesta: un SVG **vectorial puro** requiere el
  archivo fuente del diseñador (.ai/.eps/PDF vectorial); el actual es la obra
  real tratada a alta resolución, nítida al tamaño del menú y bastante más.

- **VIS-04 · Favoritos en fucsia** `LISTO`
  El corazón "guardado" usa `--accent`: catálogo, `/favoritos` y pin, todos en
  fucsia automáticamente.

- **VIS-05 · Outlines de Reservas en fucsia** `LISTO`
  Bordes de inputs, tarjetas de paquete/modo de pago, anillos de foco y el
  stepper: todo deriva de `--accent`. Ya en fucsia.

## 2. Botones / espaciado

- **VIS-06 · Padding de "Elegir fecha"** `LISTO`
  `.card-cta` pasa de `0.6rem 1rem` a `0.72rem 1.25rem` (+ gap). Respira y queda
  consistente con los demás botones pill.

## 3. Hero (home)

- **VIS-07 · Parallax** `LISTO`
  No había parallax real. Se añadió uno sutil: la imagen deriva más lento que el
  scroll (rAF + IntersectionObserver, solo mientras el hero se ve). Escala base
  1.12 para que el desplazamiento no muestre bordes. Respeta
  `prefers-reduced-motion` (sin movimiento, queda estático).

- **VIS-08 · Quitar marca de agua** `LISTO`
  Eliminada la palabra gigante y tenue **"TEOTIHUACÁN"** (`.de-word`,
  opacity 0.05) de la sección de doble exposición, justo bajo el hero. Era el
  único elemento tipo watermark del sitio. Si te referías a otra cosa concreta,
  dime y la ajusto.

- **VIS-09 · Probar fondo claro (blanco)** `DUDA (UX) -> recomendación`
  **Recomendación: mantener el tema oscuro (Obsidiana).** Razones: (1) es una
  **regla dura** en CLAUDE.md ("tema único, no hay light mode"); (2) la foto del
  amanecer y el video del hero ganan aire cinematográfico sobre negro y pierden
  fuerza sobre blanco; (3) el fucsia como acento rinde mejor sobre oscuro. Lo
  que sí resuelve un fondo claro (el logo) se puede atender con un tratamiento de
  logo sobre oscuro (VIS-03) sin volcar todo el sitio. Si aún quieres verlo, hago
  un prototipo A/B solo del hero para comparar con evidencia, sin tocar el resto.

## 4. Fotos / contenido

- **VIS-10 · Globopuerto: globos en tierra** `DUDA (asset)`
  Sigue **bloqueado por assets**. Necesito las fotos de globo en tierra del
  Drive. Puedo intentar leerlas vía el conector de Google Drive si me confirmas;
  o súbelas al repo (`src/assets/…`) y las selecciono/optimizo.
  Drive: https://drive.google.com/drive/folders/1jJKAUhgot46Uu0gC7oIrjn-v0kapxeKV

## 5. UX / interacción

- **VIS-11 · Feedback al elegir vuelo (auto-scroll)** `LISTO`
  En el paso 1 de reservas, al elegir un vuelo ahora: (1) se hace **scroll suave**
  hacia el bloque Pasajeros/Fecha si está fuera de vista, y (2) ese bloque recibe
  un **halo fucsia breve** como confirmación. Respeta `prefers-reduced-motion`
  (scroll instantáneo, sin halo).

---

## Assets que sigo necesitando

- [ ] **Logo** tratado para fondo oscuro, en **SVG** (desbloquea VIS-03).
- [ ] **Fotos de globo en tierra** del Drive (desbloquea VIS-10).
