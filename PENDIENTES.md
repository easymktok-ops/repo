# 📝 Pendientes — The Spa Mita

Lista de ajustes y detalles por implementar. Se va llenando conforme el cliente
marca cosas. **No se implementa nada hasta orden explícita** ("dale",
"ataquemos", "implementa", etc.).

**Cómo usar:** pásame los detalles en cualquier momento y los agrego aquí.
Cuando digas la orden, los ataco todos juntos, marco cada uno como hecho y
hago un solo push.

Leyenda: `[ ]` pendiente · `[~]` en progreso · `[x]` hecho

---

## 🔴 Pendientes (por implementar)

- [ ] **Hero — bajar intensidad del shade.** Casi no se aprecia la imagen.
      Buscar equilibrio entre legibilidad del H1 y presencia de la foto
      (reducir opacidad del gradiente blanco, quizá 0.96→~0.75 en la zona del texto).
- [ ] **Texto de personalización (Servicios/Precios) — quitar "color de cabina".**
      La clienta quiere que diga: **"Fragancia ambiental, música y aromaterapia."**
      Quitar definitivamente la cromoterapia / color de cabina.
      Revisar TODAS las apariciones para consistencia:
      - `serv.page.lead` (lead de la página de Servicios)
      - copy de intro del home (`intro.p2`) que menciona "color de la cabina (cromoterapia)"
      - FAQ `faq.a3` que también menciona cromoterapia
- [ ] **Página 404** personalizada (bilingüe, con el diseño del sitio: menú,
      mensaje amable y botón de volver al inicio / WhatsApp).

---

## 💡 Ideas — APROBADAS para el próximo ataque

- [ ] **Formulario también por WhatsApp** (con nombre/servicio/fecha prefilled).
      Botón "Enviar por WhatsApp" que arma un mensaje con todos los datos.
      Gratis, sin backend. ✔ Aprobado — se implementa en el próximo ataque.
- [ ] **Música de fondo chill (loop).** Toggle 🎵 apagado por defecto (el navegador
      no permite autoplay con sonido). ✔ Aprobado.
      ⚠️ **Dependencia:** hace falta la pista de audio. Si el cliente no da una,
      uso una royalty-free/libre; si el proxy bloquea la descarga, pediré que
      suban el MP3 al repo (`public/audio/`). Se resuelve en el ataque.

---

## ▶️ Orden del cliente
La próxima interacción es la señal para **atacar TODO** lo anterior de una sola vez.

---

## ⚙️ Operativos / esperando algo del cliente

- [ ] **Probar formulario en el hosting** — subir `/demo`, enviar el formulario
      y confirmar que el correo llega a info@thespamita.com.
- [ ] **Pasar a producción en la raíz** — cuando se apruebe, cambiar `base`
      de `/demo` a `/` en `astro.config.mjs`, recompilar y entregar ZIP final.

---

## ✅ Hecho (histórico)

- [x] Estructura Astro bilingüe (ES/EN) con rutas separadas + hreflang
- [x] Sistema de diseño (blanco, DM Sans 300, DM Serif Display, terracota)
- [x] Menú lateral fijo + drawer mobile, toggle de idioma persistente
- [x] Home, Servicios (precios completos), Nosotras, Galería, Contacto, Gracias
- [x] 55 imágenes optimizadas a WebP (2 tamaños)
- [x] SEO: schema.org DaySpa, Open Graph, sitemap, robots, hreflang
- [x] WhatsApp flotante + botón back-to-top
- [x] Hero con parallax + shade; nuevo hero (foto Punta de Mita del cliente)
- [x] Selector de idioma con banderas (visible en mobile sin abrir menú)
- [x] Logos de marcas más grandes
- [x] Cada servicio enlaza a reservar cita (con servicio preseleccionado)
- [x] Galería a 5 fotos (mosaico)
- [x] Footer con Google Maps, TripAdvisor, Instagram y Facebook
- [x] Formulario conectado a Web3Forms (info@thespamita.com)
- [x] Sitio base-aware para desplegar en /demo o en la raíz
