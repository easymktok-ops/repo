# Configuración de GTM — Conversiones de las landings

Contenedor: **`GTM-K5P832HB`** (ya cargado en las 4 landings).
Los CTAs empujan eventos al `dataLayer`; falta crear dentro de GTM las
variables, activadores y etiquetas para convertirlos en conversiones de
Meta y Google Ads. Esta guía está calzada a los nombres exactos ya cableados.

## Eventos disponibles

| Evento (`event`) | Cuándo se dispara | Parámetros |
|---|---|---|
| `wa_click` | Clic en cualquier CTA de WhatsApp | `wa_placement`, `room_name`, `property`, `language` |
| `select_rooms` | Clic en "Reservar" del hero/banda (scroll a `#rooms`) | `wa_placement`, `property`, `language` |
| `view_item_list` | El listado de habitaciones entra en pantalla | `property`, `language` |

- `wa_placement` ∈ `room_card · hero · topbar · cta_band · floating · footer`
- `room_name` = nombre de la habitación cuando el clic sale de una tarjeta
- `property` ∈ `casa-cacau · canoa-do-mar` · `language` ∈ `pt-BR · es`

## Paso 1 — Variables (Variables → Nuevas → *Variable de capa de datos*)

| Nombre de variable | Data Layer Variable Name |
|---|---|
| `DLV - wa_placement` | `wa_placement` |
| `DLV - room_name` | `room_name` |
| `DLV - property` | `property` |
| `DLV - language` | `language` |

## Paso 2 — Activadores (*Triggers* → tipo *Evento personalizado*)

- `wa_click` → nombre de evento `wa_click`
- `select_rooms` → `select_rooms`
- `view_item_list` → `view_item_list`

## Paso 3 — Etiquetas (*Tags*)

### A) Meta / Facebook — conversión principal
El píxel base ya está en la página, así que basta una etiqueta **HTML personalizado**:

```html
<script>
  if (typeof fbq !== 'undefined') {
    fbq('track', 'Lead', {
      content_name: {{DLV - room_name}},
      content_category: {{DLV - property}},
      wa_placement: {{DLV - wa_placement}}
    });
  }
</script>
```

- Activador: **`wa_click`**.
- (Opcional) En Events Manager creá una *Conversión personalizada* basada en el
  evento `Lead`, filtrando por `content_category` para separar Casa Cacau de
  Canoa do Mar.

### B) Google Ads — conversión principal
1. En **Google Ads → Objetivos → Conversiones → Nueva acción de conversión →
   Sitio web**, creá la acción *"Contacto WhatsApp"*, categoría
   **Contacto / Cliente potencial**, configurándola con **Google Tag Manager**.
   Vas a obtener el **ID de conversión** (`17815241132`) y una **etiqueta de
   conversión** (string tipo `abcDEfGh...`).
2. En GTM, etiqueta tipo **Seguimiento de conversiones de Google Ads** con ese
   ID + etiqueta. Activador: **`wa_click`**.

### C) (Opcional) Secundarias
Repetí A/B con activadores `select_rooms` y `view_item_list` para medirlas como
conversiones secundarias o para remarketing.

## Paso 4 — Probar y publicar
1. GTM → **Vista previa** → abrí una landing → hacé clic en un CTA de WhatsApp.
2. Confirmá en Tag Assistant que aparece `wa_click` y que las etiquetas Meta +
   Google Ads figuran como *Fired*.
3. Verificá en **Meta Events Manager** (Test Events) y en **Google Ads →
   Diagnóstico de conversión**.
4. **Enviar / Publicar** el contenedor.

## ROAS real (recomendado)
Optimizá las campañas hacia `wa_click` (señal rápida y abundante), pero el clic
es *intención*, no reserva confirmada. Para ingresos reales, importá
**conversiones offline** cuando el lead concreta la reserva: Google Ads con el
`gclid` y Meta con la CAPI (`fbclid`), capturados de la URL al abrir WhatsApp.

---

> Las llamadas directas `fbq('Lead')` / `InitiateCheckout` / `ViewContent` del
> HTML original se removieron para evitar doble conteo — ahora todo pasa por el
> `dataLayer` como fuente única. El Meta Pixel base (`PageView`) y el `gtag` de
> Google Ads siguen cargando como antes.
