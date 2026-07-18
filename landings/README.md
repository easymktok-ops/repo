# Landing pages — Grupo Caminuê

Reestructuración de las landings de captación (Meta Ads / Google Ads) para
Casa Cacau Guest House y Pousada Canoa do Mar, en español (ES) y portugués (PT-BR).

```
landings/
├── casa-cacau-es/index.html
├── casa-cacau-pt/index.html
├── canoa-do-mar-es/index.html
└── canoa-do-mar-pt/index.html
```

## Qué cambió en cada archivo

1. **Habitaciones justo debajo del hero** — patrón Booking/Airbnb. El reorden es
   visual (flexbox `order` sobre el `<body>`); el orden final es:
   `hero → barra de confianza → prueba social → habitaciones → beneficios → comodidades → galería → ubicación → FAQ → CTA → footer`.
   No se mezcló contenido entre idiomas ni entre propiedades.
2. **CTA "Reservar" del hero corregido** — ya no apunta al checkout roto
   (`/hotels/.../`); ahora hace scroll al listado de habitaciones (`#rooms`).
   Mismo cambio en el CTA de la banda inferior.
3. **CTA de WhatsApp por habitación** — cada tarjeta abre WhatsApp
   (`+55 22 99878-2768`) con un mensaje personalizado que menciona esa habitación.
4. **Prueba social** — banda de calificación agregada debajo de la barra de confianza.
5. **SEO / datos estructurados** — JSON-LD `LodgingBusiness` + `FAQPage` en el `<head>`.
6. **sitemap.xml** en la raíz del repo, con alternativas hreflang ES/PT.

## ⚠️ Placeholders a reemplazar antes de publicar

- **Calificación agregada**: los valores `4,9` y `+40` son PLACEHOLDER. Cambialos por
  los números reales de Google en dos lugares por archivo:
  - la banda visible `.social-proof` (`4,9` y `+40`);
  - el `aggregateRating` del JSON-LD (`ratingValue` / `reviewCount`).
  Si no querés mostrar calificación todavía, borrá la banda `.social-proof` y el
  bloque `aggregateRating`.
- **sitemap.xml**: las `<loc>` de las landings usan rutas de ejemplo (`/lp/...`).
  Ajustalas a las URLs reales donde se publiquen. Las de `/hotels/...` sí son las
  páginas canónicas reales.
- **Imágenes**: las rutas de imágenes son relativas; cada `index.html` espera sus
  `.webp`/`.png` en la misma carpeta (igual que en el export original).

## Medición para campañas (capa dataLayer + GTM)

GTM (`GTM-K5P832HB`) carga en las 4 páginas. Los CTAs empujan estos eventos al
`dataLayer` — **falta crear las etiquetas/gatillos dentro de GTM** para que
disparen las conversiones de Meta y Google Ads:

| Evento (`event`) | Cuándo | Parámetros | Uso sugerido |
|---|---|---|---|
| `wa_click` | Clic en cualquier CTA de WhatsApp | `wa_placement`, `room_name`, `property`, `language` | **Conversión principal** → Meta `Lead`/`Contact` + conversión de Google Ads |
| `select_rooms` | Clic en "Reservar" del hero/banda (scroll a `#rooms`) | `wa_placement`, `property`, `language` | Micro-conversión (intención) |
| `view_item_list` | El listado de habitaciones entra en pantalla | `property`, `language` | Calidad de tráfico / remarketing |

`wa_placement` ∈ `room_card · hero · topbar · cta_band · floating · footer`.
`room_name` trae el nombre de la habitación cuando el clic sale de una tarjeta.

**Qué configurar (una sola vez, en GTM):**
1. Variables de capa de datos: `wa_placement`, `room_name`, `property`, `language`.
2. Gatillo *Custom Event* = `wa_click` → etiqueta Meta Pixel (evento `Lead`) y
   etiqueta *Google Ads Conversion* (creá la acción de conversión "Contacto
   WhatsApp" y pegá su ID/label). Repetir con `select_rooms`/`view_item_list` si
   se quieren medir como conversiones secundarias.
3. Optimizá las campañas hacia `wa_click`. El clic es *intención*, no reserva
   confirmada: para ROAS real, importá conversiones offline (Google Ads con
   `gclid` / Meta CAPI con `fbclid`) cuando el lead concreta la reserva.

> Las llamadas directas `fbq('Lead')` / `InitiateCheckout` / `ViewContent` del
> HTML original se removieron para evitar doble conteo — ahora todo pasa por el
> `dataLayer` (fuente única). El Meta Pixel base (`PageView`) y el `gtag` de
> Google Ads siguen cargando como antes.
