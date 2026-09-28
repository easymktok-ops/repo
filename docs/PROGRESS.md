# PROGRESS — Joyas Colombianas® Dinner & Show

## Hecho
- **2026-09-28** — Auditoría inicial del repositorio (`easymktok-ops/repo`, rama `claude/wonderful-allen-qhmfe5`).
  - El repo NO contiene tema/código de WordPress. Solo trae un CLI npm sin relación (`markitdown-cli`, `cli.js` + `package.json`) y 12 archivos sueltos de imagen/video subidos por Norman: `hero-loop.mp4`, `hero-valle.jpg`, `galeria-01..07.jpg`, `ocasion-aniversario.jpg`, `ocasion-cumpleanos.jpg`, `ocasion-pedida.jpg`.
  - No hay `docs/DESIGN-TOKENS.md` previo — no se ha recibido pantallazo/URL del sitio actual (`joyascolombianas...`) para auditar con `/impeccable`.
- **2026-09-28** — Placeholder de video del hero confirmado: `hero-loop.mp4` ya cumple el formato objetivo.
  - 1920×1080 (horizontal, 16:9), 10.1 s, 6.9 MB, sin audio detectado.
  - Se usará como placeholder temporal en el hero mientras el cliente confirma si tiene material horizontal (la mayoría/totalidad del material bruto en Drive es vertical — MVI_0006..0050.MOV). Pendiente reemplazo por clip real aprobado.

- **2026-09-28** — Prototipo aislado del hero (`prototipo/hero.html`), independiente de la plataforma final: video diferido (poster primero para LCP), overlay para contraste, CTA con fade-in y feedback táctil, `prefers-reduced-motion` respetado, sin autoplay en conexión lenta/Save-Data. Usa `hero-loop.mp4` como placeholder y copy en `{{...}}`.
- **2026-09-28** — Norman pide evitar WordPress (lo considera obsoleto/pesado) y pregunta por un enfoque tipo "aerodiverti" con autoadministración de títulos y datos básicos. No pude inspeccionar aerodiverti.com.mx (dominio bloqueado por la política de red de esta sesión) para ver qué stack usa. Recomendación dada en el chat: sitio a medida en PHP (sin WordPress) + panel de administración mínimo hecho a medida para los campos que realmente necesita editar el cliente, en vez de instalar un CMS genérico. Alternativa más "lista para usar" si prefieren un CMS real pero ligero: Grav (PHP, flat-file, sin base de datos). Pendiente decisión de Norman.

## Bloqueado (esperando a Norman / cliente)
- **Pantallazos y URLs de referencia del sitio actual** — necesarios para extraer `docs/DESIGN-TOKENS.md` (paleta, tipografías, estilo de imagen, formas, densidad) antes de tocar CSS, por regla del proyecto.
- **Aprobación de arquitectura técnica** — el proyecto exige "inspeccionar repo y entorno, proponer la opción más simple y esperar aprobación" antes de decidir el enfoque. Repo actual no tiene stack WP. Propuesta (ver abajo) pendiente de aprobación de Norman.
- **Decisión de plataforma sin WordPress** — Norman debe confirmar entre: (a) sitio a medida en PHP + admin panel propio para campos básicos (recomendado), o (b) Grav CMS (flat-file, PHP, sin WP). Ambas corren en hosting compartido de Hostinger sin necesitar Node en producción.
- **Confirmación de video horizontal del cliente** — Norman va a preguntar si existe material horizontal para el hero. Todo lo demás del banco de Drive revisado es vertical.
- **Contenido de John** (copy, textos) — aún no entregado; todo el sitio usará placeholders `{{TITULAR_HERO}}` etc. hasta recibirlo.
- **Decisión de páginas de llamada con la clienta** (Cartagena/Bogotá/Medellín, prioridad) — pendiente de que Norman confirme cuáles entran en este alcance.

## Siguiente
1. Norman aprueba enfoque técnico (ver propuesta abajo) para poder empezar a construir sobre una base real.
2. Recibir pantallazos/URLs del sitio actual → auditoría `/impeccable` → `docs/DESIGN-TOKENS.md` → diagnóstico corto (se conserva/mejora/descarta) → aprobación.
3. Mientras tanto: se puede avanzar en prototipo de home/hero en HTML/CSS puro (independiente de la arquitectura final) usando `hero-loop.mp4` como placeholder, sin tocar producción.

## Fuera de alcance / cotizar aparte
_(vacío por ahora — se registrará aquí cualquier pedido que caiga en CRO, QR/escaneo en puerta, mapa de asientos, cupones, etc.)_

---

## Propuesta de arquitectura (pendiente de aprobación de Norman)

El repo actual no tiene stack de WordPress ni acceso a Hostinger desde esta sesión. Tres opciones posibles, en orden de preferencia dada la restricción "hosting básico + WordPress, sin servidor Node":

1. **Tema hijo (child theme) del tema activo actual** — más simple y de menor riesgo si el tema base de la clienta es mantenible; hereda actualizaciones del padre.
2. **Tema propio ligero + plugin de funciones** — más control total (rendimiento, HTML limpio para Core Web Vitals), pero más trabajo inicial; recomendable si el tema actual es pesado/builder (Elementor/Divi) y estorba el objetivo de LCP < 2.5 s.
3. **Build estático integrado** (HTML/CSS/JS compilado e insertado en WP vía plantilla mínima) — máximo control de performance, pero más fricción para que Norman edite contenido luego en WP.

**Recomendación preliminar:** necesito saber qué tema/builder corre el sitio actual (Norman: ¿qué tema tiene activo Hostinger/WordPress hoy — Elementor, Divi, Astra, uno a medida?) para poder recomendar entre 1 y 2 con criterio. Sin ese dato no tomo la decisión.
