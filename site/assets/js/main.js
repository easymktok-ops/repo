/* Easy Marketing — interacciones mínimas (menú móvil). FAQ usa <details> nativo. */
(function () {
  "use strict";

  var toggle = document.querySelector(".nav-toggle");
  var menu = document.getElementById("mobile-menu");

  if (toggle && menu) {
    toggle.addEventListener("click", function () {
      var open = menu.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });

    // Cerrar el menú al elegir un enlace de ancla
    menu.addEventListener("click", function (e) {
      if (e.target.closest("a")) {
        menu.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  // Cerrar el menú si se agranda la ventana a desktop
  window.addEventListener("resize", function () {
    if (window.innerWidth > 720 && menu) {
      menu.classList.remove("open");
      if (toggle) toggle.setAttribute("aria-expanded", "false");
    }
  });

  /* Entrada discreta al hacer scroll (fade-up). Mejora progresiva:
     sin JS o sin soporte, el contenido se ve normal. */
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (!reduce && "IntersectionObserver" in window) {
    var sel = ".section-head, .grid > *, .phases > *, .steps > *, .stack > *, .cases > *, .offer > *, .fit > *, .media, .cta-final";
    var els = Array.prototype.slice.call(document.querySelectorAll(sel)).filter(function (el) {
      return !el.closest(".hero"); // el hero se muestra de inmediato
    });
    els.forEach(function (el) {
      el.classList.add("reveal");
      var parent = el.parentElement;
      if (parent) {
        var i = Array.prototype.indexOf.call(parent.children, el);
        if (i > 0) el.style.transitionDelay = Math.min(i * 60, 240) + "ms";
      }
    });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -6% 0px" });
    els.forEach(function (el) { io.observe(el); });
  }

  /* ---------------- Tracking (dataLayer / GA4, no-op si no existen) ------- */
  function track(event, params) {
    try {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(Object.assign({ event: event }, params || {}));
      if (typeof window.gtag === "function") window.gtag("event", event, params || {});
    } catch (e) { /* nunca romper la UX por tracking */ }
  }

  // CTA principal (cualquier enlace que lleva al formulario)
  document.addEventListener("click", function (e) {
    var a = e.target.closest && e.target.closest("a");
    if (!a) return;
    var href = a.getAttribute("href") || "";
    if (href.indexOf("diagnostico-negocio") !== -1) track("diagnostico_negocio_cta", { cta_text: (a.textContent || "").trim() });
    else if (href.indexOf("#diagnostico") !== -1) track("diagnostico_ejecutivo_cta", { cta_text: (a.textContent || "").trim() });
    if (/wa\.me|api\.whatsapp\.com/.test(href)) track("whatsapp_click", { location: href });
  });

  /* ---------------- Formulario Diagnóstico Ejecutivo --------------------- */
  var PERSONAL = ["gmail.com","hotmail.com","outlook.com","yahoo.com","live.com","icloud.com","hotmail.es","yahoo.com.mx"];
  document.querySelectorAll(".diag-form").forEach(function (form) {
    var started = false;
    var errorEl = form.querySelector("[data-form-error]");
    var emailEl = form.querySelector("#f-email, input[type=email]");
    var hint = form.querySelector("[data-email-hint]");

    form.addEventListener("focusin", function () {
      if (!started) { started = true; track("diagnostico_ejecutivo_start"); }
    });

    if (emailEl && hint) {
      emailEl.addEventListener("blur", function () {
        var v = (emailEl.value || "").toLowerCase().trim();
        var dom = v.split("@")[1] || "";
        hint.hidden = !(dom && PERSONAL.indexOf(dom) !== -1); // solo advierte, no bloquea
      });
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault(); // el form tiene novalidate: controlamos el envío nosotros
      if (errorEl) errorEl.hidden = true;
      if (!form.checkValidity()) { form.reportValidity(); return; } // muestra validación nativa
      var btn = form.querySelector("button[type=submit]");
      var original = btn ? btn.textContent : "";
      if (btn) { btn.disabled = true; btn.textContent = "Enviando…"; }
      track("diagnostico_ejecutivo_submit", {
        desafio: (form.querySelector("[name=desafio]") || {}).value,
        equipo_comercial: (form.querySelector("[name=equipo]:checked") || {}).value
      });
      var thankyou = form.getAttribute("data-thankyou") || "gracias.html";
      var action = form.getAttribute("action");
      fetch(action, { method: "POST", body: new FormData(form), headers: { "Accept": "application/json" } })
        .then(function (r) {
          if (r.ok) { window.location.href = thankyou; }
          else { throw new Error("bad status"); }
        })
        .catch(function () {
          if (btn) { btn.disabled = false; btn.textContent = original; }
          if (errorEl) errorEl.hidden = false;
        });
    });
  });
})();
