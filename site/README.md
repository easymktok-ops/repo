# Easy Marketing — Sitio reconstruido (reposicionamiento IA)

Sitio estático de dos páginas para el nuevo posicionamiento de Easy Marketing:
**generación de demanda con automatización de IA** para despachos y firmas de
servicios profesionales (headhunting, staffing, consultoría B2B).

El producto ancla que se comunica: un sistema de IA que **responde en segundos,
califica al prospecto y agenda la cita** en el calendario del cliente,
automáticamente. La propuesta de valor es **ahorro de tiempo y dinero**, no
"hacemos tus campañas".

## Estructura

```
site/
├── index.html              # Home (/)
├── diagnostico/
│   └── index.html          # Landing de campaña (/diagnostico/) — reemplaza el rol de /free/
├── assets/
│   ├── css/styles.css      # Design system compartido (todo por variables CSS)
│   └── js/main.js          # Solo menú móvil; el FAQ usa <details> nativo
└── README.md
```

## Cómo verlo localmente

```bash
cd site
python3 -m http.server 8080
# abre http://localhost:8080/  y  http://localhost:8080/diagnostico/
```

## Pendientes de reemplazar (placeholders)

Todos los CTA usan enlaces de marcador de posición. Busca y reemplaza en ambos HTML:

| Placeholder | Reemplazar por |
|---|---|
| `https://calendly.com/easymarketing/diagnostico` | El enlace real de Calendly |
| `https://wa.me/52XXXXXXXXXX?text=...` | El número real de WhatsApp (formato `52` + 10 dígitos) |
| `[Testimonio pendiente]` / `[Caso de resultado pendiente]` | Testimonios/casos reales cuando existan |

> **Nota honesta:** no se inventaron clientes, cifras ni resultados. La sección
> de prueba social del home queda con placeholders explícitos hasta tener casos
> reales.

## Re-skin de marca (colores y tipografía)

No fue posible extraer la paleta del sitio actual (`easymarketing.mx` responde
403 a peticiones automatizadas). Se usó una paleta **placeholder profesional
B2B**, lista para re-skin. Para aplicar los colores reales de marca, edita solo
las variables en `assets/css/styles.css` → `:root`:

- `--brand-ink` — navy de marca / texto fuerte
- `--accent` — **color del CTA principal (Calendly)**, el de mayor contraste
- `--whatsapp` — verde del CTA secundario
- `--font` — tipografía (actualmente Inter vía Google Fonts + fallback de sistema)

## Decisiones de diseño y contenido

- **Jerarquía de CTAs (requisito funcional):** Calendly es siempre el botón más
  prominente (color de acento, presente en header, hero, después de cada sección
  de valor, CTA final y barra fija móvil). WhatsApp es secundario (ícono en el
  header + repetido más abajo y en el footer/CTA final).
- **Sin formularios de contacto** que compitan con Calendly.
- **Home** (audiencia fría): hooks de nivel Problema/Solución → dolor, sistema,
  ¿para quién?, cómo funciona (pasos numerados en vez de testimonios falsos),
  FAQ de objeciones B2B, CTA final.
- **Landing `/diagnostico/`** (audiencia que vio un anuncio): oferta de
  diagnóstico gratuito de 30 min, bloque de **calificación explícita** que
  autofiltra por sector, tamaño (5–50) e iguala mensual, proceso en 3 pasos,
  FAQ de conversión, CTA repetido.
- **Reglas de lenguaje:** se evitó por completo "agencia de marketing", "gestión
  de redes sociales", "campañas publicitarias", "SEO", "growth", "funnel",
  "ROAS". El servicio se vende como **sistema/producto de IA**, enfocado en el
  resultado (agenda llena, tiempo recuperado, dinero ahorrado).
- **Mobile-first y responsive:** buena parte del tráfico de campañas llega desde
  celular. Incluye menú móvil y barra de CTA fija inferior.

## Dudas que quedaron abiertas (la sesión no permitió preguntar en vivo)

1. **Banco de hooks** (`Easy_Marketing_Matriz_Hooks_Reposicionamiento.md`): no
   estaba disponible. El copy es original, escrito según el brief y las reglas de
   lenguaje. Si compartes el documento, ajusto headlines/subheads a esas frases.
2. **Colores de marca reales:** ver sección de re-skin.
3. **Ruta de la landing:** se usó `/diagnostico/`. Si prefieres conservar
   `/free/` para no romper enlaces de anuncios, se renombra la carpeta.
