# Casa Cacau — Cotizador hBook en el hero: spec + fix del datepicker

Archivo afectado: `landings/casa-cacau-pt/index-test-cotizador.html`
Script hBook (caja negra, externo): `.../hbook-universal-js/js/67c0a8fe970a6f628a11ad70.js` (**no minificado**, 769 líneas — se pudo leer y analizar directamente).

## 1. Comportamiento esperado vs. actual

**Esperado:** al abrir el datepicker (check-in/check-out) dentro del cotizador reubicado
en el hero, el calendario debe aparecer **anclado inmediatamente al input** (debajo, o
arriba si no hay espacio abajo), dentro del viewport, **la primera vez y siempre**, sin
importar fuentes/imagen cargando.

**Actual (bugs reportados):**
- **BUG 1** — al cargar, el calendario aparece pegado a la **esquina superior izquierda**,
  sobre el navbar, fuera del cotizador.
- **BUG 2** — la **primera** apertura muestra el calendario **desalineado/separado** del
  input; tras cerrar y reabrir se "reincorpora" a la posición correcta.

## 2. Diagnóstico de causa raíz (confirmado)

El widget de hBook usa **jQuery UI Datepicker**. Verificado leyendo el JS y reproduciendo
con jQuery UI 1.12.1 real (servido local):

- jQuery UI **anexa `#ui-datepicker-div` como hijo directo de `<body>`** (no dentro de
  nuestro contenedor) y lo posiciona con `position:absolute; top/left` en **coordenadas de
  página calculadas a partir del offset del input EN EL MOMENTO del show**.
- Nuestro contenedor del hero **no** usa `overflow:hidden` ni `transform` en la cadena de
  ancestros del box (se descartó como causa). El box sí se **reubica** (relocador) al card
  del hero; el datepicker es un popup aparte anexado al body.
- **Causa BUG 2 (reproducida):** si el layout cambia **después** de que jQuery UI calculó la
  posición (la **imagen del hero** y las **fuentes** Montserrat/Playfair cargan y desplazan
  el input hacia abajo), el calendario ya abierto **queda en la coordenada vieja** → se ve
  separado. Al reabrir, recalcula con el layout ya estable → correcto.
  Evidencia (control sin fix, tras un shift de layout): input en `top≈906`, calendario en
  `top≈459` → **~447px de desfase**.
- **Causa BUG 1 (timing):** un show que ocurre mientras el input aún está en offset ~0,0
  (durante el init/relocación, antes de que el layout pinte) deja el calendario en `0,0`
  (esquina superior izquierda). En el arnés local con jQuery instantáneo no se dispara solo;
  en producción (jQuery por red + shift de layout) sí. Se cubre con guardas (ver §3).

hBook es **caja negra**: no expone un método público de reposición. jQuery UI recalcula la
posición en cada `show`, pero **no** reposiciona un calendario ya abierto cuando el layout
cambia. Por eso el fix vive en NUESTRO código.

## 3. Fix (en nuestra página; hBook/jQuery UI intactos)

Capa de corrección que trata al datepicker como caja negra y lo **re-ancla** al input real:

1. **Relocador** del `#HSystemSearchBoxInline` al card del hero (igual que antes).
2. **`MutationObserver` sobre `#ui-datepicker-div`**: cada vez que se muestra o cambia de
   mes, tras un doble `requestAnimationFrame` (layout asentado) se reposiciona anclado al
   input actual (`jQuery.datepicker._curInst.input`, con fallback a `document.activeElement`).
   Si se muestra **sin** input válido (show huérfano → BUG 1), se **oculta**.
3. **Re-anclaje ante cualquier shift de layout**: `ResizeObserver` sobre `documentElement`,
   más listeners de `resize`/`scroll`, y disparos en `document.fonts.ready`, `load` y en el
   `load`/`decode()` de la imagen del hero → corrige BUG 2 (input que se movió con el
   calendario abierto).
4. Tras inicializar el datepicker, **blur** del input y ocultar cualquier popup prematuro.

El posicionamiento respeta el viewport: si no hay espacio abajo, abre **arriba** del input
(comportamiento estándar de datepicker).

## 4. Validación

Arnés local **fiel**: la propia página de Casa Cacau + el script real de hBook servido
local, con **jQuery 1.12.4 + jQuery UI 1.12.1 reales** (instalados desde npm), reescribiendo
solo las URLs de dependencias a local (el `src` del widget hBook NO se altera en el
entregable).

Suite automatizada por iteración: (A) en carga no queda en esquina sup-izq; (B) 1ª apertura
anclada; (C) **shift de layout con el calendario abierto** → sigue anclado; (D) show huérfano
→ se oculta; (E) reabrir → anclado. Tolerancia: |ΔX|≤28px y adyacente vertical (abajo o
flip arriba).

**Resultado:** `FIX` → **10/10 PASS** (desktop ×5 + mobile ×5, 0 fallas). `CONTROL` (sin la
capa) → **FAIL** en (C), reproduciendo BUG 2.

## 5. Limitación honesta (pendiente de tu lado)

No pude correr la validación final **en el servidor real** (`grupocaminue.com.br`): la red de
este entorno bloquea ese dominio y bloquea que el navegador cargue el CDN de hBook. La
validación se hizo en un arnés local fiel (jQuery UI real + script real de hBook). Falta que
hagas las **5 recargas con hard-refresh (Cmd/Ctrl+Shift+R)** en tu server, desktop (~1440) y
mobile (~375), confirmando 0 fallas. Si en alguna reaparece el bug, mandámela y vuelvo al §2
(por ejemplo, ajustar el disparador de re-anclaje a algún evento propio de hBook).
