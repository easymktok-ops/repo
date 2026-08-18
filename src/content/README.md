# Contenido autoadministrable

Estas carpetas las llena **el dueño del negocio desde `/admin`** (Decap CMS), no
a mano. Cada archivo es una entrada validada contra el schema de
`src/content.config.ts`, así que no se puede romper el maquetado al editar.

| Carpeta         | Qué guarda                                  | Quién lo edita |
| --------------- | ------------------------------------------- | -------------- |
| `packages/`     | Paquetes de vuelo (precio, cupo, fotos)     | Negocio        |
| `faq/`          | Preguntas frecuentes (acordeón, citable)    | Negocio        |
| `testimonials/` | Reseñas y testimonios                       | Negocio        |
| `promos/`       | Promociones con vigencia                     | Negocio        |
| `gallery/`      | Fotos de galería                            | Negocio        |
| `pages/`        | Texto largo (globopuerto, legal)            | Negocio / dev  |

No se incluyen datos de negocio de ejemplo (precios, políticas): se cargan reales
cuando el negocio los confirme. Las colecciones vacías compilan sin problema.

Para editar en local: `npm run cms:proxy` en una terminal, `npm run dev` en otra,
y abre `/admin`.
