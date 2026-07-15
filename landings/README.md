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
