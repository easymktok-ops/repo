/*
 * Mejoras del administrador de contenido (Decap CMS):
 * 1. Vista previa con el look del sitio (oscuro, tarjeta de paquete con precio, testimonio, pregunta).
 * 2. Recorrido guiado la primera vez (lista y editor) + boton "Ver recorrido" en la barra superior.
 * Requiere /ui/tour.js (motor del recorrido) y se carga despues de decap-cms.js.
 */
(function () {
  "use strict";
  var CMS = window.CMS;
  var h = window.h;
  if (!CMS || !h) return;

  /* ------------------------------------------------------------------ */
  /* 1. Vista previa                                                    */
  /* ------------------------------------------------------------------ */
  CMS.registerPreviewStyle(
    [
      ":root{color-scheme:dark}",
      "html,body{margin:0;background:#0f1116;color:#ecedf1;font:16px/1.55 ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;-webkit-font-smoothing:antialiased}",
      "body{padding:28px}",
      ".pv-note{margin:0 0 18px;font-size:13px;color:#a6abb5}",
      ".pv-flag{display:inline-block;margin:0 6px 14px 0;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;background:rgba(230,180,80,.14);color:#e6b450}",
      ".pv-flag.bad{background:rgba(239,109,109,.14);color:#ef6d6d}",
      ".pv-card{max-width:420px;border-radius:20px;overflow:hidden;background:#1c1f27;border:1px solid #262b34}",
      ".pv-media{position:relative;aspect-ratio:4/3;background:#242833}",
      ".pv-media img{width:100%;height:100%;object-fit:cover;display:block}",
      ".pv-media .empty{position:absolute;inset:0;display:grid;place-items:center;color:#878e9b;font-size:14px}",
      ".pv-badge{position:absolute;top:14px;left:14px;padding:4px 12px;border-radius:999px;background:#ea83c1;color:#17131a;font-size:12px;font-weight:700}",
      ".pv-body{padding:18px 20px 20px}",
      ".pv-title{margin:0 0 6px;font-size:22px;line-height:1.2;letter-spacing:-.01em;text-wrap:balance}",
      ".pv-sum{margin:0 0 14px;color:#d3d7df;font-size:15px}",
      ".pv-meta{margin:0 0 14px;color:#a6abb5;font-size:13px}",
      ".pv-list{margin:0 0 16px;padding:0;list-style:none;display:grid;gap:6px;font-size:14px;color:#d3d7df}",
      ".pv-list li::before{content:'';display:inline-block;width:6px;height:6px;margin:0 10px 2px 0;border-radius:50%;background:#ea83c1}",
      ".pv-price{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}",
      ".pv-price b{font-size:26px;font-variant-numeric:tabular-nums}",
      ".pv-price s{color:#878e9b}",
      ".pv-price span{color:#a6abb5;font-size:13px}",
      ".pv-en{margin-top:22px;padding-top:16px;border-top:1px solid #262b34}",
      ".pv-en h3{margin:0 0 6px;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#878e9b}",
      ".pv-en p{margin:0 0 4px;color:#a6abb5;font-size:14px}",
      ".pv-quote{max-width:520px;padding:22px 24px;border-radius:18px;background:#1c1f27;border:1px solid #262b34}",
      ".pv-stars{color:#ea83c1;letter-spacing:2px;font-size:16px;margin-bottom:10px}",
      ".pv-quote blockquote{margin:0 0 14px;font-size:18px;line-height:1.5;color:#ecedf1}",
      ".pv-quote cite{font-style:normal;color:#a6abb5;font-size:14px}",
      ".pv-faq{max-width:620px}",
      ".pv-faq h2{margin:0 0 10px;font-size:22px;line-height:1.25}",
      ".pv-faq p{margin:0;color:#d3d7df}",
      ".pv-promo{max-width:620px;padding:20px 22px;border-radius:18px;background:linear-gradient(135deg,rgba(234,131,193,.16),rgba(143,184,242,.08));border:1px solid rgba(234,131,193,.35)}",
      ".pv-promo h2{margin:0 0 6px;font-size:20px}",
      ".pv-promo p{margin:0;color:#d3d7df}",
      "img{max-width:100%;height:auto;border-radius:12px}",
      "h1,h2,h3{color:#ecedf1}",
      "strong{color:#ecedf1}",
    ].join("\n"),
    { raw: true },
  );

  function get(entry, path) {
    var v = entry.getIn(["data"].concat(path.split(".")));
    return v == null ? "" : v;
  }
  function money(n) {
    if (n === "" || n == null || isNaN(Number(n))) return null;
    return "$" + Number(n).toLocaleString("es-MX", { maximumFractionDigits: 0 });
  }
  function flags(entry, extra) {
    var out = [];
    if (get(entry, "draft") === true)
      out.push(h("span", { className: "pv-flag", key: "d" }, "Borrador: no se publica"));
    if (extra) out = out.concat(extra);
    return out;
  }
  function english(rows) {
    var have = rows.filter(function (r) {
      return r[1];
    });
    if (!have.length) return null;
    return h(
      "div",
      { className: "pv-en" },
      h("h3", null, "Versión en inglés"),
      have.map(function (r, i) {
        return h("p", { key: i }, h("strong", null, r[0] + ": "), r[1]);
      }),
    );
  }

  var PackagePreview = window.createClass({
    render: function () {
      var e = this.props.entry;
      var img = get(e, "heroImage");
      var asset = img ? this.props.getAsset(img) : null;
      var price = money(get(e, "priceFrom"));
      var was = money(get(e, "priceWas"));
      var mins = get(e, "durationMinutes");
      var max = get(e, "capacity.max");
      var meta = [
        mins ? Math.round(mins / 60) + " h" : null,
        max ? "Hasta " + max + " personas" : null,
      ]
        .filter(Boolean)
        .join(" · ");
      var hl = e.getIn(["data", "highlights"]);
      var items = hl && hl.size ? hl.toArray() : [];
      var extra =
        get(e, "available") === false
          ? [
              h(
                "span",
                { className: "pv-flag bad", key: "a" },
                "No disponible: no se puede reservar",
              ),
            ]
          : [];
      return h(
        "div",
        null,
        h("p", { className: "pv-note" }, "Así se ve la tarjeta del paquete en el sitio."),
        flags(e, extra),
        h(
          "article",
          { className: "pv-card" },
          h(
            "div",
            { className: "pv-media" },
            asset
              ? h("img", { src: asset.toString(), alt: get(e, "heroImageAlt.es") })
              : h("div", { className: "empty" }, "Sin imagen"),
            get(e, "badge.es") ? h("span", { className: "pv-badge" }, get(e, "badge.es")) : null,
          ),
          h(
            "div",
            { className: "pv-body" },
            h("h2", { className: "pv-title" }, get(e, "title.es") || "Título del paquete"),
            get(e, "summary.es") ? h("p", { className: "pv-sum" }, get(e, "summary.es")) : null,
            meta ? h("p", { className: "pv-meta" }, meta) : null,
            items.length
              ? h(
                  "ul",
                  { className: "pv-list" },
                  items.map(function (it, i) {
                    var t =
                      it && it.get ? it.get("es") || (it.getIn && it.getIn(["text", "es"])) : it;
                    return t ? h("li", { key: i }, String(t)) : null;
                  }),
                )
              : null,
            h(
              "div",
              { className: "pv-price" },
              price ? h("span", null, "Desde") : null,
              h("b", null, price || "Consultar"),
              was ? h("s", null, was) : null,
              price ? h("span", null, "por persona") : null,
            ),
          ),
        ),
        english([
          ["Título", get(e, "title.en")],
          ["Resumen", get(e, "summary.en")],
          ["Etiqueta", get(e, "badge.en")],
        ]),
      );
    },
  });

  var TestimonialPreview = window.createClass({
    render: function () {
      var e = this.props.entry;
      var n = Math.max(0, Math.min(5, Number(get(e, "rating")) || 0));
      var who = [get(e, "author"), get(e, "location")].filter(Boolean).join(" · ");
      return h(
        "div",
        null,
        flags(e),
        h(
          "figure",
          { className: "pv-quote" },
          h(
            "div",
            { className: "pv-stars", "aria-label": n + " de 5" },
            "★★★★★".slice(0, n) + "☆☆☆☆☆".slice(0, 5 - n),
          ),
          h("blockquote", null, get(e, "quote.es") || "Texto de la reseña"),
          h("cite", null, who || "Autor"),
        ),
        english([["Reseña", get(e, "quote.en")]]),
      );
    },
  });

  var FaqPreview = window.createClass({
    render: function () {
      var e = this.props.entry;
      return h(
        "div",
        null,
        flags(e),
        h(
          "section",
          { className: "pv-faq" },
          h("h2", null, get(e, "question.es") || "Pregunta"),
          h("p", null, get(e, "answer.es") || "Respuesta"),
        ),
        english([
          ["Pregunta", get(e, "question.en")],
          ["Respuesta", get(e, "answer.en")],
        ]),
      );
    },
  });

  var PromoPreview = window.createClass({
    render: function () {
      var e = this.props.entry;
      var off =
        get(e, "active") !== true
          ? [h("span", { className: "pv-flag bad", key: "o" }, "Inactiva: no se muestra")]
          : [];
      return h(
        "div",
        null,
        off,
        h(
          "section",
          { className: "pv-promo" },
          h("h2", null, get(e, "title.es") || "Título de la promoción"),
          h("p", null, get(e, "body.es")),
        ),
        english([
          ["Título", get(e, "title.en")],
          ["Texto", get(e, "body.en")],
        ]),
      );
    },
  });

  CMS.registerPreviewTemplate("packages", PackagePreview);
  CMS.registerPreviewTemplate("testimonials", TestimonialPreview);
  CMS.registerPreviewTemplate("faq", FaqPreview);
  CMS.registerPreviewTemplate("promos", PromoPreview);

  /* ------------------------------------------------------------------ */
  /* 2. Recorrido guiado                                                 */
  /* ------------------------------------------------------------------ */
  var T = window.AeroTour;
  if (!T) return;

  var LIST = [
    {
      title: "Bienvenida al administrador de contenido",
      body: "Aquí cambias lo que se ve en el sitio: paquetes y precios base, preguntas frecuentes, testimonios, promociones y fotos. Te muestro cómo en un minuto.",
    },
    {
      sel: '[class*="-AppHeaderNavList"]',
      title: "Contenido y Medios",
      body: "<b>Contenido</b> son los textos y datos del sitio. <b>Medios</b> es la biblioteca de fotos que ya subiste.",
    },
    {
      sel: '[class*="-SidebarNavList"]',
      title: "Secciones del sitio",
      body: "Cada sección es una parte del sitio. Haz clic en una para ver sus elementos. Debajo del título de cada sección te explico para qué sirve.",
    },
    {
      sel: '[class*="-SearchContainer"]',
      title: "Buscar",
      body: "Encuentra cualquier elemento por su texto, en todas las secciones.",
    },
    {
      sel: '[class*="-CollectionTopNewButton"]',
      title: "Agregar",
      body: "Crea un elemento nuevo en esta sección, por ejemplo un paquete o una pregunta.",
    },
    {
      sel: '[class*="-CollectionControlsContainer"]',
      title: "Ordenar, filtrar y vista",
      body: "Ordena la lista, muestra solo borradores o disponibles, y cambia entre lista y cuadrícula.",
    },
    {
      sel: '[class*="-ListCard-card"]||[class*="-GridCard"]',
      title: "Abrir para editar",
      body: "Haz clic en un elemento para editarlo. En paquetes la línea muestra también el precio base.",
    },
    {
      sel: '[class*="-AppHeaderQuickNewButton"]',
      title: "Añadir rápido",
      body: "Atajo para crear algo nuevo en cualquier sección sin cambiar de pantalla.",
    },
    {
      sel: '[class*="-AppHeaderSiteLink"]',
      title: "Ver el sitio",
      body: "Abre el sitio público. Los cambios se ven ahí unos minutos después de publicar.",
    },
    {
      sel: ".aero-tour-btn",
      title: "Repite el recorrido",
      body: "Si olvidas algo, aquí lo vuelves a ver. El editor tiene su propio recorrido la primera vez que abres un elemento.",
    },
  ];

  var EDIT = [
    {
      sel: '[class*="-ControlPaneContainer"]',
      title: "Campos",
      body: "Aquí escribes. Cada campo dice qué es y algunos traen una nota debajo. Los marcados como opcionales pueden quedar vacíos.",
    },
    {
      sel: '[class*="-PreviewPaneContainer"]',
      title: "Vista previa",
      body: "Muestra cómo se verá mientras escribes. En paquetes ves la tarjeta con su precio, tal como en el sitio.",
    },
    {
      sel: '[class*="-ViewControls"]',
      title: "Mostrar u ocultar la vista previa",
      body: "Con el ojo la ocultas para escribir más cómodo. La otra flecha sincroniza el desplazamiento de ambos lados.",
    },
    {
      sel: '[class*="-ToolbarSubSectionFirst"]',
      title: "Publicar",
      body: "Cuando terminas, <b>Publicar</b> manda los cambios al sitio. El sitio se actualiza solo en unos minutos. <b>Eliminar entrada</b> lo borra.",
    },
    {
      sel: '[class*="-ToolbarSectionBackLink"]',
      title: "Volver",
      body: "Regresa a la lista. Abajo de la flecha ves si hay cambios sin guardar.",
    },
  ];

  T.define("admin-lista", LIST, { autostart: false, wait: 1800, version: 1 });
  T.define("admin-editor", EDIT, { autostart: false, wait: 1800, version: 1 });

  function screen() {
    return /#\/collections\/[^/]+\/(entries|new)/.test(location.hash)
      ? "admin-editor"
      : /#\/collections\//.test(location.hash)
        ? "admin-lista"
        : null;
  }

  // Boton "Ver recorrido" en la barra superior (la de lista o la del editor); se vuelve a poner si Decap repinta.
  function ensureButton() {
    var key = screen();
    var bar =
      document.querySelector('[class*="-AppHeaderActions"]') ||
      document.querySelector('[class*="-ToolbarSectionMeta"]');
    var b = document.querySelector(".aero-tour-btn");
    if (!key || !bar) {
      if (b) b.remove();
      return;
    }
    if (!b || !bar.contains(b)) {
      if (b) b.remove();
      b = document.createElement("button");
      b.type = "button";
      b.className = "aero-tour-btn";
      b.textContent = "Ver recorrido";
      bar.insertBefore(b, bar.firstChild);
    }
    b.setAttribute("data-tour-start", key);
  }

  var lastKey = null;
  function onRoute() {
    ensureButton();
    var key = screen();
    if (key && key !== lastKey && !T.seen(key, 1) && !T.active()) {
      lastKey = key;
      setTimeout(function () {
        if (screen() === key && !T.active()) T.start(key);
      }, 900);
    }
  }

  var queued = false;
  new MutationObserver(function () {
    if (queued) return;
    queued = true;
    requestAnimationFrame(function () {
      queued = false;
      ensureButton();
    });
  }).observe(document.body, { childList: true, subtree: true });
  window.addEventListener("hashchange", onRoute);
  // Primera carga: Decap tarda en pintar (y en produccion primero pide entrar con GitHub).
  var tries = 0;
  (function boot() {
    if (
      screen() &&
      document.querySelector('[class*="-AppHeader-css"], [class*="-ToolbarContainer"]')
    )
      return onRoute();
    if (++tries < 120) setTimeout(boot, 250);
  })();
})();
