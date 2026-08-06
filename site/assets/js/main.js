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

  /* -------- Count-up en métricas de casos (progressive enhancement) ------
     El valor real ya está en el HTML (extraíble por crawlers/GEO). Solo si
     hay motion y soporte, animamos 0 -> valor al entrar en viewport. */
  if (!reduce && "IntersectionObserver" in window) {
    var nums = document.querySelectorAll("[data-count]");
    if (nums.length) {
      var numObs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) return;
          var el = e.target; numObs.unobserve(el);
          var target = parseFloat(el.getAttribute("data-count")) || 0;
          var pre = el.getAttribute("data-prefix") || "";
          var suf = el.getAttribute("data-suffix") || "";
          var dur = 1100, t0 = null;
          el.textContent = pre + "0" + suf;
          requestAnimationFrame(function step(ts) {
            if (t0 === null) t0 = ts;
            var p = Math.min((ts - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 4); // ease-out-quart
            el.textContent = pre + Math.round(eased * target) + suf;
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = pre + target + suf;
          });
        });
      }, { threshold: 0.6 });
      nums.forEach(function (el) { numObs.observe(el); });
    }
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
