# 📝 Pendientes — The Spa Mita

Lista de ajustes y detalles. **No se implementa nada hasta orden explícita**
("dale", "ataquemos", etc.). Pásame detalles cuando quieras y los apunto aquí.

Leyenda: `[ ]` pendiente · `[~]` en progreso · `[x]` hecho

---

## 🔴 Pendientes (por implementar)

- [ ] **Horarios por temporada.** Reemplazar el horario único ("Lun–Dom
      9:00–20:00") por dos horarios estacionales:
      - **Temporada alta (noviembre–marzo):** Lun–Dom **9:00–20:00**
      - **Temporada baja (abril–octubre):** Lun–Sáb **9:30–18:30**;
        Dom **9:30–18:00**
      Lugares a actualizar: menú lateral (`side.horario.valor`), footer,
      página de Contacto y `openingHours` del schema.org (rangos por día).
      Presentación sugerida: dos líneas "Temporada alta / Temporada baja"
      con los meses entre paréntesis.

### ✔ Validado (sin acción)
- Frase "Si logra relajar la mente también lo hace." → NO se pasó a la nueva
  web; el párrafo de Nosotras ya está redactado limpio. Nada que borrar.
- Shiatsu → ya muestra 90 min en la nueva.

---

## ⚙️ Operativos / esperando algo del cliente

- [x] **Pista de música** subida (`public/audio/ambient.mp3`, 56 s, loop, vol. 35%).
      Reproductor 🎵 activo y verificado.
- [ ] **Probar formulario en el hosting** — enviar por correo y por WhatsApp y
      confirmar que llegan.
- [ ] **Pasar a producción en la raíz** — cambiar `base` de `/demo` a `/`,
      recompilar y entregar ZIP final.

---

## ✅ Hecho (histórico)

- [x] Sitio Astro bilingüe (ES/EN), rutas separadas + hreflang, base-aware (/demo)
- [x] Sistema de diseño, menú lateral, toggle idioma con banderas
- [x] Home, Servicios (precios), Nosotras, Galería (5 fotos), Contacto, Gracias
- [x] Imágenes WebP, hero nuevo (foto Punta de Mita), parallax + shade
- [x] SEO: schema.org DaySpa, sitemap, robots, hreflang
- [x] WhatsApp flotante, back-to-top, footer (Maps/TripAdvisor/redes)
- [x] Formulario Web3Forms conectado a info@thespamita.com (+ preselección de servicio)
- [x] **Hero: shade más ligero** (equilibrio legibilidad H1 ↔ imagen)
- [x] **Texto de personalización** sin "color de cabina/cromoterapia"
      → "fragancia ambiental, música y aromaterapia" (Servicios, home, FAQ, meta)
- [x] **Página 404** personalizada bilingüe
- [x] **og:image / twitter:image** = tarjeta con el logo (1200×630), evita
      confusión de ubicación
- [x] **Formulario también por WhatsApp** (botón que arma el mensaje con los datos)
- [x] **Reproductor de música 🎵** con toggle (apagado por defecto; requiere el MP3)
