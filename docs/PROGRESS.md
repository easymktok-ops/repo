# PROGRESS — Joyas Colombianas® Dinner & Show

Stack: `docs/STACK.md`. Tokens y diagnóstico: `docs/DESIGN-TOKENS.md`. Fotos pendientes: `docs/IMAGENES.md`. Cronograma: 15 sesiones de 1h, 28 sept a 16 oct, lanzamiento 17 oct.

## Hecho
- **28 sept** — Auditoría del repo, decisión de plataforma: sin WordPress, PHP a medida + panel propio (sobre Grav). Stack documentado.
- **30 sept** — Estructura base + home completo (sesiones "estructura base" y "maquetación" del cronograma):
  - Front controller PHP (`public/index.php`) con rutas: `/`, `/contacto` (POST), `/sitemap.xml`, páginas provisionales noindex para `/comprar/`, `/medellin/`, `/bogota/`, `/cartagena/`, `/politica-de-datos/`, y 404.
  - Capa de contenido editable (`app/content/defaults.php`, sobrescribible por `storage/content.json`, que escribirá el panel de admin).
  - Home full SEO: title, canonical, Open Graph, robots, schema.org (PerformingGroup + Event semanal con Offers 195.000 / 180.000 COP generados desde la programación), sitemap, robots.txt.
  - Secciones en el mismo orden del sitio actual: hero, frase + marco de video, experiencia, banda, ciudades + programación 2026, ¿Qué es un Dinner Show?, banda, contacto, footer.
  - Parallax por capas ligado al scroll (CSS, fuera del hilo principal) + profundidad por puntero en el hero (escritorio), todo desactivado con `prefers-reduced-motion`.
  - Formulario con CSRF, honeypot, validación por campo y guardado en `storage/leads.csv` (fuera de la carpeta pública).
  - Iconos SVG propios, fuentes locales (Cinzel, Montserrat, Great Vibes de respaldo para el logo, todas OFL).
  - QA: sin errores de consola, sin desborde horizontal en 390px y 1440px, JSON-LD válido.
- Contenido migrado: textos del sitio actual (pantallazo) y de la reseña oficial en Drive. Nombre normalizado a "Joyas Colombianas® Dinner & Show". Sin mención al recinto anterior.

## Confirmar con la clienta antes de publicar
- **Número de WhatsApp** del botón (la reseña trae dos: Esteban Pardo +57 302 854 5902 y Diana López +57 300 308 3374). Hoy el botón apunta a #contacto.
- **Medellín**: el sitio actual dice sábados 8:00 PM en Hotel Marriott Medellín; la reseña de Drive dice otro día, hora y recinto (desactualizada). Se usó la del sitio actual.
- **Bogotá**: se puso Hotel Tequendama (notas de la reunión del 25 sept); el sitio actual solo dice "Funciones privadas".
- **Duración del show**: la reseña dice "una hora en dos intervalos de 45 min" (no cuadra); no se publicó.
- Dirección exacta del Marriott (para schema.org y Google My Business).
- URLs de Facebook y TripAdvisor, y el texto de la reseña de TripAdvisor del footer.
- Segundo botón del hero "Compra entradas COP": se retiró por duplicado; confirmar si era para otra moneda.
- Texto de la política de tratamiento de datos (Ley 1581).

## Bloqueado
- **Fotos y logo**: la descarga directa de Drive y el sitio original están bloqueados por la red de esta sesión. Se dejaron espacios con el nombre de archivo esperado (`docs/IMAGENES.md`). Para desbloquear: Norman sube los archivos a `public/assets/img/`, o se agregan `joyascolombianasdinnershow.com` y `drive.google.com` a los dominios permitidos del entorno.
- **Video del hero**: lo provee Norman.
- **Copy SEO de John**: llega el 6 oct. Hoy hay contenido provisional migrado y `{{META_DESCRIPTION_HOME}}`.
- **Pasarela**: Mercado Pago (confirmado en la reunión del 25 sept); validación de cobertura internacional el 13 oct.
- **Hostinger**: versión de PHP, credenciales, staging.

## Siguiente
1. Panel de administración mínimo (login + edición de `storage/content.json`: programación, precios, textos, WhatsApp).
2. Landings de campaña (semana 2): Cartagena (marca, sin checkout), Medellín y Bogotá. **Enfoque 100% conversión UX/UI, noindex, sin prioridad SEO.** El home sí queda full SEO.
3. Esquema MySQL: funciones, órdenes, tickets (`#JCD-1001…`), door list.

## Fuera de alcance / cotizar aparte
- A/B testing de CRO: lo hace John después del lanzamiento (15 días), no entra en esta construcción.

## Cómo correrlo en local
```
php -S 127.0.0.1:8000 -t public public/index.php
```
En Hostinger: el contenido de `public/` va a `public_html/`; `app/`, `config/` y `storage/` quedan un nivel arriba (fuera de la carpeta pública). `config/config.php` se crea en el servidor a partir de `config/config.example.php`.
