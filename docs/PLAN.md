# PLAN — Joyas Colombianas® Dinner & Show

Fases y tareas. Detalle de avance en `docs/PROGRESS.md`; stack en `docs/STACK.md`.

## Plan reformulado (9 oct): los textos los escribimos nosotros
John no entrega los textos. Se escriben a partir de `docs/FICHA-DE-HECHOS.md` (solo hechos confirmados) y `docs/SEO-BRIEF.md`. Lanzamiento: sábado 17 oct. Mínimo para lanzar: home SEO, landing de Medellín, compra con pago, ticket, política de datos y términos.

| Día | Entrega |
|---|---|
| Vie 9 | Landings de campaña (Medellín y Bogotá), ficha de hechos, brief SEO. Norman manda a la clienta las preguntas pendientes |
| Lun 12 | Borrador completo del texto de la home (~2.500 palabras) con preguntas frecuentes y marcado de datos estructurados |
| Mar 13 | Revisión de hechos con la clienta; textos finales. Páginas legales (datos personales, términos, cancelaciones) para su revisión. Validación de pagos internacionales |
| Mié 14 | Mercado Pago, si ya hay credenciales y documentación; correo de confirmación |
| Jue 15 | Panel mínimo y lista de asistentes; redirecciones del sitio viejo; Google My Business; pruebas en el servidor de pruebas |
| Vie 16 | Revisión de punta a punta y pase a producción, con aprobación de Norman |

Después del lanzamiento: versión en inglés, guías de apoyo (qué hacer en Medellín de noche, danzas por región, qué es un dinner show), Cartagena y pruebas de conversión.

## Fase 1 — 28 sept a 2 oct: accesos, auditoría, tokens, estructura base y home
- [ ] Acceso a Hostinger (credenciales, staging, versión de PHP)
- [x] Pantallazo del sitio actual (Norman)
- [x] Tokens de diseño y diagnóstico → `docs/DESIGN-TOKENS.md` (pendiente revisión de Norman)
- [x] Plataforma: sitio a medida sin WordPress, PHP + panel propio
- [x] Estructura base PHP (rutas, contenido editable, SEO, formulario)
- [x] Home full SEO con parallax por capas, contenido migrado
- [ ] Logo, fotos y video reales en `public/assets/img/` (`docs/IMAGENES.md`)
- [x] Panel de administración mínimo: ingreso, resumen de cupos, pedidos, contenido básico (WhatsApp, redes, reseña) — falta editar precios y programación desde el panel

## Fase 2 — 5 a 9 oct: landings de campaña + contenido de John
- [ ] Landing Cartagena (marca, "Tesoros del Pacífico", sin checkout, noindex)
- [x] Landings Medellín y Bogotá: conversión 100% UX/UI, noindex, fuera del sitemap (construidas, con datos verificados)
- [ ] Textos de la home y páginas legales redactados por nosotros (ya no dependen de John)
- [ ] Revisión cruzada de contenido y enlaces internos

## Fase 3 — 12 a 16 oct: pagos, ticket, correo, door list, QA, producción
- [x] Esquema de base de datos: órdenes, entradas, tickets, avisos (MySQL y SQLite) — probado en SQLite; falta probar MySQL en staging
- [ ] Mercado Pago detrás de un adaptador (prep 12 oct, validación de cobertura internacional 13 oct, integración 14 oct)
- [x] Aviso de pago idempotente con firma verificada → ticket `#JCD-1001…` atómico (probado con pasarela simulada)
- [x] Página de compra, estado del pedido y ticket digital
- [ ] Correo transaccional con ticket
- [x] Lista de la puerta, lista de reservas de Cartagena y reporte para la contadora en CSV (Excel)
- [ ] Eventos `begin_checkout` y `purchase` (solo pago aprobado, en URL propia)
- [ ] Google My Business (15 oct)
- [ ] QA de punta a punta en staging y pase a producción (16 oct, con aprobación de Norman)

## Preguntas abiertas
- ¿Versión en inglés para turistas?
- ¿Se mantiene el formulario de contacto además de WhatsApp? (hoy está construido, igual que en el sitio actual)
- ¿Correo transaccional por SMTP de Hostinger o servicio externo?
- ¿Hay video horizontal del show?
