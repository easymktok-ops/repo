/*
 * Recorrido guiado (tour) para los paneles internos: panel de ventas/precios y /admin (Decap).
 * Sin dependencias. Se muestra solo la primera vez (localStorage) y se puede repetir con un boton
 * [data-tour-start]. Teclado: flechas para avanzar/regresar, Esc para cerrar. Respeta
 * prefers-reduced-motion. Colores por variables --tour-* (cada panel define las suyas).
 *
 * Uso:
 *   AeroTour.define("precios-temporadas", [
 *     { title: "Bienvenida", body: "..." },                 // sin sel: tarjeta centrada
 *     { sel: "[data-tour=nueva]", title: "...", body: "..." },
 *   ], { autostart: true, version: 1 });
 *   AeroTour.start("precios-temporadas");
 */
(function () {
  "use strict";
  if (window.AeroTour) return;

  var tours = {};
  var active = null;

  function seenKey(key, v) {
    return "aero-tour:" + key + ":v" + (v || 1);
  }
  function seen(key, v) {
    try {
      return localStorage.getItem(seenKey(key, v)) === "1";
    } catch (e) {
      return false;
    }
  }
  function markSeen(key, v) {
    try {
      localStorage.setItem(seenKey(key, v), "1");
    } catch (e) {}
  }

  var CSS =
    ".at-veil{position:fixed;inset:0;z-index:2147483000;background:transparent}" +
    ".at-spot{position:fixed;left:0;top:0;z-index:2147483001;border-radius:12px;pointer-events:none;" +
    "box-shadow:0 0 0 2px var(--tour-accent,#ea83c1),0 0 0 9999px var(--tour-dim,rgba(8,9,12,.66));" +
    "transition:transform .26s cubic-bezier(.23,1,.32,1),width .26s cubic-bezier(.23,1,.32,1),height .26s cubic-bezier(.23,1,.32,1),opacity .2s cubic-bezier(.23,1,.32,1)}" +
    ".at-spot.is-center{opacity:0}" +
    ".at-veil.is-center{background:var(--tour-dim,rgba(8,9,12,.66))}" +
    ".at-pop{position:fixed;left:0;top:0;z-index:2147483002;width:min(340px,calc(100vw - 32px));box-sizing:border-box;" +
    "padding:1rem 1.05rem .9rem;border-radius:14px;background:var(--tour-bg,#1c1f27);color:var(--tour-ink,#ecedf1);" +
    "border:1px solid var(--tour-line,#333844);box-shadow:0 18px 50px -12px rgba(0,0,0,.6);" +
    "font:15px/1.5 var(--tour-font,ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif);text-align:left}" +
    ".at-pop.is-in{animation:at-in .2s cubic-bezier(.23,1,.32,1) both}" +
    "@keyframes at-in{from{opacity:0;transform:var(--at-t) translateY(6px) scale(.98)}to{opacity:1;transform:var(--at-t)}}" +
    ".at-step{display:flex;align-items:center;gap:.5rem;margin:0 0 .35rem;font-size:.76rem;color:var(--tour-muted,#a6abb5);font-variant-numeric:tabular-nums}" +
    ".at-bar{flex:1;height:3px;border-radius:3px;background:var(--tour-line,#333844);overflow:hidden}" +
    ".at-bar i{display:block;height:100%;background:var(--tour-accent,#ea83c1);border-radius:3px;transform-origin:left;" +
    "transition:transform .26s cubic-bezier(.23,1,.32,1)}" +
    ".at-title{margin:0 0 .3rem;font-size:1.02rem;font-weight:650;line-height:1.3;color:var(--tour-ink,#ecedf1)}" +
    ".at-body{margin:0;font-size:.9rem;color:var(--tour-soft,#d3d7df)}" +
    ".at-body b{color:var(--tour-ink,#ecedf1);font-weight:600}" +
    ".at-foot{display:flex;align-items:center;gap:.45rem;margin-top:.9rem}" +
    ".at-foot .at-sp{flex:1}" +
    ".at-btn{font:inherit;font-size:.86rem;font-weight:600;border-radius:999px;padding:.42rem .9rem;cursor:pointer;border:1px solid transparent;" +
    "transition:transform .14s cubic-bezier(.23,1,.32,1),background-color .14s ease,border-color .14s ease,color .14s ease}" +
    ".at-btn:active{transform:scale(.97)}" +
    ".at-btn:focus-visible{outline:2px solid var(--tour-accent,#ea83c1);outline-offset:2px}" +
    ".at-next{background:var(--tour-accent,#ea83c1);color:var(--tour-accent-ink,#17131a)}" +
    ".at-back{background:transparent;color:var(--tour-ink,#ecedf1);border-color:var(--tour-line,#333844)}" +
    ".at-skip{background:transparent;color:var(--tour-muted,#a6abb5);padding-left:.2rem;padding-right:.2rem}" +
    "@media (hover:hover){.at-skip:hover{color:var(--tour-ink,#ecedf1)}.at-back:hover{border-color:var(--tour-muted,#a6abb5)}}" +
    "@media (max-width:560px){.at-pop{left:16px!important;right:16px;top:auto!important;bottom:16px;width:auto;--at-t:translate(0,0)!important;transform:none!important}}" +
    "@media (prefers-reduced-motion:reduce){.at-spot,.at-bar i{transition:none}.at-pop.is-in{animation:none}}";

  function injectCss() {
    if (document.getElementById("at-css")) return;
    var s = document.createElement("style");
    s.id = "at-css";
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function find(sel) {
    if (!sel) return null;
    var list = sel.split("||");
    for (var i = 0; i < list.length; i++) {
      var n = document.querySelector(list[i].trim());
      if (n && (n.offsetWidth || n.offsetHeight || n.getClientRects().length)) return n;
    }
    return null;
  }

  // Espera a que aparezca el elemento hasta `ms` (en /admin la interfaz se pinta tarde; en el panel, 0).
  function waitFor(sel, ms, cb) {
    if (!sel) return cb(null);
    var t0 = Date.now();
    (function tick() {
      var n = find(sel);
      if (n || Date.now() - t0 >= ms) return cb(n);
      setTimeout(tick, 80);
    })();
  }

  function define(key, steps, opts) {
    opts = opts || {};
    tours[key] = {
      key: key,
      steps: steps,
      version: opts.version || 1,
      onEnd: opts.onEnd,
      wait: opts.wait || 0,
    };
    if (opts.autostart !== false && !seen(key, opts.version)) {
      var go = function () {
        setTimeout(
          function () {
            if (!active) start(key);
          },
          opts.delay != null ? opts.delay : 450,
        );
      };
      if (document.readyState === "complete") go();
      else window.addEventListener("load", go, { once: true });
    }
  }

  function start(key) {
    var def = tours[key];
    if (!def) return;
    // Sin espera (paginas ya pintadas): se quitan de entrada los pasos sin elemento, asi el contador no salta.
    var t = def.wait
      ? def
      : {
          key: def.key,
          version: def.version,
          onEnd: def.onEnd,
          wait: 0,
          steps: def.steps.filter(function (s) {
            return !s.sel || find(s.sel);
          }),
        };
    if (!t.steps.length) return;
    if (active) end(false);
    injectCss();
    var veil = el("div", "at-veil");
    var spot = el("div", "at-spot is-center");
    var pop = el("div", "at-pop");
    pop.setAttribute("role", "dialog");
    pop.setAttribute("aria-modal", "true");
    pop.setAttribute("aria-labelledby", "at-title");
    pop.innerHTML =
      '<p class="at-step"><span class="at-count"></span><span class="at-bar"><i></i></span></p>' +
      '<h2 class="at-title" id="at-title"></h2><p class="at-body"></p>' +
      '<div class="at-foot"><button type="button" class="at-btn at-skip">Saltar</button><span class="at-sp"></span>' +
      '<button type="button" class="at-btn at-back">Atrás</button><button type="button" class="at-btn at-next"></button></div>';
    document.body.appendChild(veil);
    document.body.appendChild(spot);
    document.body.appendChild(pop);
    active = {
      tour: t,
      i: 0,
      veil: veil,
      spot: spot,
      pop: pop,
      target: null,
      prevFocus: document.activeElement,
    };
    pop.querySelector(".at-skip").addEventListener("click", function () {
      end(true);
    });
    pop.querySelector(".at-back").addEventListener("click", function () {
      go(-1);
    });
    pop.querySelector(".at-next").addEventListener("click", function () {
      go(1);
    });
    document.addEventListener("keydown", onKey, true);
    window.addEventListener("resize", place);
    window.addEventListener("scroll", place, true);
    show(0, 1);
  }

  function go(dir) {
    if (!active || active.busy) return;
    var n = active.i + dir;
    if (n >= active.tour.steps.length) return end(true);
    if (n < 0) return;
    show(n, dir);
  }

  function show(i, dir) {
    var a = active;
    var step = a.tour.steps[i];
    a.busy = true;
    waitFor(step.sel, a.tour.wait, function (target) {
      if (!active || active !== a) return;
      a.busy = false;
      // Paso con selector cuyo elemento no existe en esta pantalla: se salta.
      if (step.sel && !target && !step.optionalTarget) {
        var n = i + dir;
        if (n >= 0 && n < a.tour.steps.length) return show(n, dir);
        if (n >= a.tour.steps.length) return end(true);
      }
      a.i = i;
      a.target = target;
      var total = a.tour.steps.length;
      a.pop.querySelector(".at-count").textContent = i + 1 + " de " + total;
      a.pop.querySelector(".at-bar i").style.transform = "scaleX(" + (i + 1) / total + ")";
      a.pop.querySelector(".at-title").textContent = step.title;
      a.pop.querySelector(".at-body").innerHTML = step.body;
      var back = a.pop.querySelector(".at-back");
      back.hidden = i === 0;
      var next = a.pop.querySelector(".at-next");
      next.textContent = i === total - 1 ? "Listo" : i === 0 && !step.sel ? "Empezar" : "Siguiente";
      a.pop.querySelector(".at-skip").hidden = i === total - 1;
      if (target) {
        var r = target.getBoundingClientRect();
        var mobile = window.innerWidth <= 560;
        var fixed = isFixed(target);
        if (!fixed) {
          var want = mobile ? 72 : Math.max(72, (window.innerHeight - r.height) / 2 - 40);
          if (r.top < 64 || r.bottom > window.innerHeight - (mobile ? 260 : 24) || mobile) {
            window.scrollTo({ top: Math.max(0, window.scrollY + r.top - want), behavior: "auto" });
          }
        }
      }
      place();
      a.pop.classList.remove("is-in");
      void a.pop.offsetWidth;
      a.pop.classList.add("is-in");
      next.focus({ preventScroll: true });
    });
  }

  function isFixed(n) {
    for (var x = n; x && x !== document.body; x = x.parentElement) {
      var p = getComputedStyle(x).position;
      if (p === "fixed" || p === "sticky") return true;
    }
    return false;
  }

  function place() {
    var a = active;
    if (!a) return;
    var pop = a.pop;
    var pad = 8;
    if (!a.target) {
      a.spot.classList.add("is-center");
      a.veil.classList.add("is-center");
      var w = pop.offsetWidth;
      var h = pop.offsetHeight;
      var x = Math.round((window.innerWidth - w) / 2);
      var y = Math.round((window.innerHeight - h) / 2);
      pop.style.setProperty("--at-t", "translate(" + x + "px," + y + "px)");
      pop.style.transform = "translate(" + x + "px," + y + "px)";
      return;
    }
    a.spot.classList.remove("is-center");
    a.veil.classList.remove("is-center");
    var r = a.target.getBoundingClientRect();
    a.spot.style.transform = "translate(" + (r.left - pad) + "px," + (r.top - pad) + "px)";
    a.spot.style.width = r.width + pad * 2 + "px";
    a.spot.style.height = r.height + pad * 2 + "px";
    var pw = pop.offsetWidth;
    var ph = pop.offsetHeight;
    var gap = 14;
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var top;
    if (r.bottom + pad + gap + ph <= vh - 12) top = r.bottom + pad + gap;
    else if (r.top - pad - gap - ph >= 12) top = r.top - pad - gap - ph;
    else top = Math.max(12, vh - ph - 12);
    var left = Math.min(Math.max(16, r.left), vw - pw - 16);
    // Si el elemento es muy alto (una tabla), la tarjeta va al lado si cabe.
    if (r.height > vh * 0.55 && r.right + gap + pw < vw - 12) {
      left = r.right + gap;
      top = Math.min(Math.max(12, r.top), vh - ph - 12);
    }
    var t = "translate(" + Math.round(left) + "px," + Math.round(top) + "px)";
    pop.style.setProperty("--at-t", t);
    pop.style.transform = t;
  }

  function onKey(e) {
    if (!active) return;
    if (e.key === "Escape") {
      e.preventDefault();
      end(true);
    } else if (e.key === "ArrowRight") {
      e.preventDefault();
      go(1);
    } else if (e.key === "ArrowLeft") {
      e.preventDefault();
      go(-1);
    } else if (e.key === "Tab") {
      var f = Array.prototype.filter.call(active.pop.querySelectorAll("button"), function (b) {
        return !b.hidden;
      });
      var idx = f.indexOf(document.activeElement);
      e.preventDefault();
      var n = (idx + (e.shiftKey ? -1 : 1) + f.length) % f.length;
      f[n].focus();
    }
  }

  function end(done) {
    var a = active;
    if (!a) return;
    active = null;
    if (done) markSeen(a.tour.key, a.tour.version);
    document.removeEventListener("keydown", onKey, true);
    window.removeEventListener("resize", place);
    window.removeEventListener("scroll", place, true);
    [a.veil, a.spot, a.pop].forEach(function (n) {
      n.remove();
    });
    if (a.prevFocus && a.prevFocus.focus) a.prevFocus.focus({ preventScroll: true });
    if (a.tour.onEnd) a.tour.onEnd();
  }

  document.addEventListener("click", function (e) {
    var b = e.target.closest && e.target.closest("[data-tour-start]");
    if (!b) return;
    e.preventDefault();
    var key = b.getAttribute("data-tour-start");
    if (!key) {
      var keys = Object.keys(tours);
      key = keys[keys.length - 1];
    }
    start(key);
  });

  window.AeroTour = {
    define: define,
    start: start,
    end: end,
    seen: seen,
    active: function () {
      return !!active;
    },
  };
})();
