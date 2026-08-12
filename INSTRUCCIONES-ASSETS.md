# Cómo subir imágenes y referencias — The Spa Mita

Ya dejé las carpetas creadas. Aquí va **dónde va cada cosa** y **cómo subirlo**.

---

## 1. Dónde va cada archivo

| Carpeta | Qué colocar ahí |
|---|---|
| `assets-originales/` | **Todas las imágenes originales del sitio actual**, tal como las tengas (JPG/PNG, cualquier tamaño). No las renombres ni optimices tú: yo las convierto a WebP y genero los 2 tamaños con `srcset`. Si puedes, agrúpalas en subcarpetas (`hero/`, `servicios/`, `galeria/`, `nosotros/`) — si no, las clasifico yo. |
| `referencias/bodhispa/` | HTML/CSS de `bodhispa.com` (view-source o export de dev tools) para inspeccionar la estructura real que vamos a clonar. |
| `referencias/screenshots/` | Capturas del **menú lateral** de referencia (estilo Lilly Victoria Beauty / GoDaddy) y de cualquier sección de bodhispa.com que quieras que replique. |

> **Importante:** `public/img/**` es la carpeta de SALIDA (ahí genero yo los WebP optimizados). **Tú no pones nada ahí** — tú solo llenas `assets-originales/` y `referencias/`.

---

## 2. Cómo subir los archivos (elige UNA opción)

### Opción A — Arrastrar al chat (más simple para pocos archivos)
Arrastra las imágenes/archivos a la ventana del chat, igual que hiciste con el prompt `.md`.
Dime **cuántos archivos** subiste y yo los muevo a la carpeta correcta dentro del repo.
- Bien para: referencias, screenshots, y lotes chicos de imágenes (hasta ~15-20).
- Limitación: si son muchas imágenes pesadas, se vuelve lento.

### Opción B — Subirlas por Git desde tu computadora (mejor para muchas imágenes)
Desde tu máquina, con el repo clonado en la rama de trabajo:
```bash
git checkout claude/new-session-4ragwm
git pull origin claude/new-session-4ragwm

# copia tus imágenes dentro de assets-originales/ y tus referencias dentro de referencias/
git add assets-originales/ referencias/
git commit -m "Agregar imágenes originales y referencias de The Spa Mita"
git push origin claude/new-session-4ragwm
```
Luego avísame y hago `git pull` de mi lado para procesarlas.

---

## 3. Para que valide que no falta ninguna imagen
Dime **cuántas imágenes esperas** en total (y por sección si sabes), así confirmo que llegaron todas antes de procesarlas — no invento imágenes faltantes.

---

## 4. Todavía me faltan 2 confirmaciones tuyas (del prompt original)

1. **Servicios "Niños" y "Más servicios":** ¿entran como opciones en el selector del formulario, o se quedan fuera de esta fase? (Los 6 servicios principales ya están definidos.)
2. **Contenido de páginas secundarias (Servicios, Nosotros, Galería):** ¿me pasas el HTML actual de `thespamita.com` (español e inglés) para extraer el texto real, o te confirmo que lo redacte basándome solo en el copy de marca que ya me diste? (No inventaré contenido sin tu OK.)

Mientras subes todo esto, **yo avanzo con el sistema de diseño (tokens de color/tipografía) y la maqueta del home con placeholders**, que no dependen de estas respuestas.
