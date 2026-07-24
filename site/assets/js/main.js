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
})();
