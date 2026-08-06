/* =========================================================================
   Diagnosticador "7 Puntos" — Easy Marketing
   Autoevaluación guiada (wizard) → resumen gratis (semáforo) →
   captura de contacto → reporte completo personalizado + PDF (impresión).
   Todo en el cliente. La captura usa FormSubmit (mismo backend del sitio).
   Los resultados se derivan SOLO de lo que responde el usuario.
   ========================================================================= */
(function () {
  "use strict";
  var root = document.getElementById("dx");
  if (!root) return;

  /* ---------------- Tracking (no rompe si no hay dataLayer/gtag) --------- */
  function track(event, params) {
    try {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(Object.assign({ event: event }, params || {}));
      if (typeof window.gtag === "function") window.gtag("event", event, params || {});
    } catch (e) {}
  }

  /* ------------------------------ Los 7 puntos -------------------------- */
  var POINTS = [
    { key: "publico",       n: 1, name: "Público",       sub: "quién te compra" },
    { key: "problema",      n: 2, name: "Problema",      sub: "qué dolor resuelves" },
    { key: "solucion",      n: 3, name: "Solución",      sub: "qué tan clara es tu propuesta" },
    { key: "diferenciales", n: 4, name: "Diferenciales", sub: "qué te hace distinto" },
    { key: "testimonios",   n: 5, name: "Testimonios",   sub: "qué prueba social tienes" },
    { key: "objeciones",    n: 6, name: "Objeciones",    sub: "qué frena la compra" },
    { key: "garantia",      n: 7, name: "Garantía",      sub: "cómo quitas el riesgo" }
  ];

  /* --------------------------- Cuestionario ----------------------------
     type: choice | scale | open
     opts: [{t: texto, s: score 0-100}]   ·   w: peso al promediar (def 1)  */
  var Q = {
    publico: [
      { id: "pub_claridad", type: "scale", w: 1,
        label: "¿Qué tan claro tienes definido a tu cliente ideal?",
        help: "1 = no lo tengo claro · 5 = lo tengo cristalino",
        legend: ["Nada claro", "Cristalino"] },
      { id: "pub_valora", type: "choice", w: 1,
        label: "¿Qué es lo que MÁS valora tu cliente ideal antes de comprarte?",
        opts: [
          { t: "Confianza y seguridad", s: 100 },
          { t: "Rapidez / conveniencia", s: 80 },
          { t: "Precio", s: 60 },
          { t: "No estoy seguro", s: 20 }
        ] }
    ],
    problema: [
      { id: "prob_frase", type: "open", w: 0.6,
        label: "En una frase, ¿cuál es el problema #1 que resuelves?",
        help: "Con tus propias palabras. Si lo dejas en blanco, igual generamos tu reporte.",
        placeholder: "Ej: ayudo a empresas B2B a dejar de perder oportunidades entre marketing y ventas." },
      { id: "prob_palabras", type: "scale", w: 1,
        label: "¿Qué tan seguido tus clientes usan esas mismas palabras al contactarte?",
        help: "Si describen su dolor igual que tú, tu mensaje está bien afinado.",
        legend: ["Casi nunca", "Siempre"] }
    ],
    solucion: [
      { id: "sol_web", type: "choice", w: 1,
        label: "¿Tu web o comunicación explica claramente CÓMO resuelves ese problema?",
        opts: [
          { t: "Sí, queda muy claro", s: 100 },
          { t: "Más o menos", s: 50 },
          { t: "No, no queda claro", s: 10 }
        ] },
      { id: "sol_accion", type: "choice", w: 0.8,
        label: "Cuando alguien llega a tu web, ¿sabe cuál es el siguiente paso?",
        opts: [
          { t: "Sí, hay un paso claro (agendar, escribir, comprar)", s: 100 },
          { t: "Hay varias opciones y confunde", s: 50 },
          { t: "No hay un llamado a la acción claro", s: 15 }
        ] }
    ],
    diferenciales: [
      { id: "dif_competencia", type: "choice", w: 1,
        label: "¿Cuántos competidores directos ofrecen prácticamente lo mismo que tú?",
        opts: [
          { t: "Ninguno, soy claramente distinto", s: 100 },
          { t: "Algunos", s: 60 },
          { t: "La mayoría, es un mercado saturado", s: 25 }
        ] },
      { id: "dif_frase", type: "open", w: 0.4,
        label: "¿Cuál es tu principal diferenciador? (opcional)",
        help: "Aquello que solo tú puedes decir con honestidad.",
        placeholder: "Ej: único con método propietario y acompañamiento senior." }
    ],
    testimonios: [
      { id: "test_cantidad", type: "choice", w: 1,
        label: "¿Cuántos testimonios o casos de éxito tienes VISIBLES hoy?",
        help: "Visibles en tu web, redes o propuestas.",
        opts: [
          { t: "20+", s: 100 },
          { t: "6 – 20", s: 75 },
          { t: "1 – 5", s: 45 },
          { t: "Ninguno", s: 10 }
        ] },
      { id: "test_uso", type: "choice", w: 0.8,
        label: "¿Los usas en los momentos clave (web, propuestas, anuncios)?",
        opts: [
          { t: "Sí, de forma estratégica", s: 100 },
          { t: "Solo en un lugar", s: 55 },
          { t: "No los uso", s: 15 }
        ] }
    ],
    objeciones: [
      { id: "obj_conoce", type: "choice", w: 1,
        label: "¿Sabes cuál es la razón #1 por la que un prospecto NO te compra?",
        opts: [
          { t: "Sí, la tengo clara", s: 100 },
          { t: "Tengo una idea", s: 55 },
          { t: "No lo sé", s: 15 }
        ] },
      { id: "obj_resuelve", type: "choice", w: 1,
        label: "¿Resuelves esa objeción activamente en tu comunicación?",
        opts: [
          { t: "Sí, de forma directa", s: 100 },
          { t: "A veces", s: 50 },
          { t: "No", s: 15 }
        ] }
    ],
    garantia: [
      { id: "gar_ofrece", type: "choice", w: 1,
        label: "¿Ofreces alguna garantía o forma de reducir el riesgo de probarte?",
        opts: [
          { t: "Sí, clara y visible", s: 100 },
          { t: "Tengo algo, pero no la comunico", s: 50 },
          { t: "No tengo", s: 10 }
        ] }
    ]
  };

  /* Estado: answers[qid] = {s: score, t: texto/opción, v: valor crudo} */
  var answers = {};
  var stepIndex = 0; // 0 = intro; 1..7 = puntos; 8 = resumen
  var TOTAL = POINTS.length;
  var started = false, completed = false;

  /* --------------------------- Puntaje por punto ------------------------ */
  function openScore(txt) {
    var t = (txt || "").trim();
    if (!t) return 15;
    var words = t.split(/\s+/).length;
    return words >= 4 ? 100 : (words >= 1 ? 60 : 15);
  }
  function pointScore(key) {
    var qs = Q[key], sum = 0, wsum = 0;
    qs.forEach(function (q) {
      var a = answers[q.id];
      if (!a) return;
      var w = q.w || 1;
      sum += a.s * w; wsum += w;
    });
    return wsum ? Math.round(sum / wsum) : 0;
  }
  function level(score) {
    if (score >= 70) return "green";
    if (score >= 45) return "yellow";
    return "red";
  }
  var STATE_TXT = {
    green:  { label: "Sólido",       line: "Es una fortaleza de tu negocio." },
    yellow: { label: "Por mejorar",  line: "Funciona a medias; hay margen claro." },
    red:    { label: "Punto crítico", line: "Hoy te está costando ventas." }
  };

  /* ------------------- Recomendaciones personalizadas ------------------- */
  function esc(s){ return (s || "").replace(/[<>&]/g, function(c){return {"<":"&lt;",">":"&gt;","&":"&amp;"}[c];}); }
  function val(id){ return answers[id] ? answers[id].t : ""; }

  function recs(key) {
    var r = [], probFrase = (answers.prob_frase && answers.prob_frase.v || "").trim();
    var difFrase = (answers.dif_frase && answers.dif_frase.v || "").trim();
    if (key === "publico") {
      if ((answers.pub_claridad && answers.pub_claridad.s) < 60)
        r.push("Escribe en una frase a quién le vendes y a quién NO. Sin un cliente ideal definido, cada canal rinde por debajo de su potencial.");
      if (val("pub_valora") === "No estoy seguro")
        r.push("Habla con 5 clientes recientes y pregúntales por qué te eligieron: descubrir si compran por confianza, rapidez o precio cambia todo tu mensaje.");
      else if (val("pub_valora") === "Precio")
        r.push("Si hoy te eligen por precio, construye una razón para elegirte que no sea el precio —es la única forma de salir de la guerra de descuentos.");
      if (!r.length) r.push("Mantén tu cliente ideal documentado y revísalo cada trimestre; es la base sobre la que se apoyan los otros 6 puntos.");
    }
    if (key === "problema") {
      if (probFrase) r.push("Usa tu frase «" + esc(probFrase) + "» como titular en tu web y anuncios: el problema, dicho con las palabras del cliente, convierte más que describir tu servicio.");
      else r.push("Define en una sola frase el problema #1 que resuelves. Si tú no lo puedes decir claro, tu cliente tampoco lo va a reconocer.");
      if ((answers.prob_palabras && answers.prob_palabras.s) < 60)
        r.push("Recolecta las frases exactas que usan tus clientes al contactarte y refléjalas en tu comunicación; el mensaje que usa sus palabras se siente hecho para ellos.");
    }
    if (key === "solucion") {
      if (val("sol_web") !== "Sí, queda muy claro")
        r.push("Reescribe tu mensaje principal como «Ayudamos a [cliente] a [resultado] sin [fricción]»" + (probFrase ? ", conectándolo con «" + esc(probFrase) + "»." : "."));
      if (val("sol_accion") && val("sol_accion").indexOf("Sí") !== 0)
        r.push("Deja UN solo llamado a la acción principal por página (agendar, escribir o comprar). Varias opciones compiten entre sí y diluyen la conversión.");
      if (!r.length) r.push("Tu propuesta es clara; el siguiente paso es probar variantes del mensaje para subir aún más la conversión con datos.");
    }
    if (key === "diferenciales") {
      if (val("dif_competencia") && val("dif_competencia").indexOf("Ninguno") !== 0)
        r.push("Enumera 3 cosas que tú haces y tu competencia no, y llévalas al frente de tu comunicación. En un mercado parecido, quien articula mejor su diferencia gana.");
      if (difFrase) r.push("Tu diferenciador «" + esc(difFrase) + "» debe aparecer arriba en tu web y en tus propuestas, no escondido —hoy es un activo que no estás capitalizando del todo.");
      else r.push("Define tu diferenciador en una frase honesta que solo tú puedas decir; sin él, la decisión del cliente termina cayendo en el precio.");
    }
    if (key === "testimonios") {
      var tc = val("test_cantidad");
      if (tc === "Ninguno") r.push("Pide hoy 3 testimonios a tus mejores clientes. La prueba social es lo que convierte el interés en confianza —sin ella, el prospecto duda.");
      else if (tc === "1 – 5") r.push("Tienes prueba social pero es poca: ponte una meta de sumar 1–2 testimonios nuevos al mes hasta llegar a una masa que genere confianza inmediata.");
      if (val("test_uso") && val("test_uso").indexOf("Sí") !== 0)
        r.push("Coloca testimonios en los puntos de decisión: junto al botón de agendar, en la propuesta y en los anuncios —no solo en una página de 'testimonios'.");
      if (!r.length) r.push("Tienes buena prueba social; asegúrate de renovarla y de mostrar casos parecidos al perfil de cada prospecto.");
    }
    if (key === "objeciones") {
      if (val("obj_conoce") === "No lo sé")
        r.push("Pregunta a los prospectos que NO te compraron por qué no avanzaron. Conocer la objeción #1 es el mayor apalancamiento de conversión que existe.");
      if (val("obj_resuelve") && val("obj_resuelve").indexOf("Sí") !== 0)
        r.push("Responde tus 3 objeciones más comunes de forma explícita en tu web (FAQ) y en tu proceso de ventas, antes de que el prospecto tenga que preguntarlas.");
      if (!r.length) r.push("Manejas bien las objeciones; documenta cómo las resuelves para que todo tu equipo lo haga igual de bien.");
    }
    if (key === "garantia") {
      var g = val("gar_ofrece");
      if (g === "No tengo") r.push("Diseña una forma de reducir el riesgo de probarte (garantía, primer paso sin compromiso, diagnóstico previo). Quitar el riesgo de la decisión sube la conversión de inmediato.");
      else if (g && g.indexOf("no la comunico") !== -1) r.push("Ya tienes cómo reducir el riesgo: hazlo visible junto a tu llamado a la acción. Una garantía que no se comunica no convierte.");
      else r.push("Tu garantía es un activo: menciónala en cada punto de decisión y úsala como argumento de cierre.");
    }
    return r.slice(0, 2);
  }

  var EXPLAIN = {
    green:  "Este punto está trabajando a tu favor. Consolídalo para que sostenga al resto del sistema.",
    yellow: "Este punto funciona de forma parcial. Ajustarlo es de las mejoras más rápidas que puedes hacer.",
    red:    "Este punto está frenando tu conversión hoy. Es donde una intervención tiene mayor retorno."
  };

  /* ------------------------------ Render wizard ------------------------- */
  var elIntro = document.getElementById("dx-intro");
  var elWizard = document.getElementById("dx-wizard");
  var elStepHost = document.getElementById("dx-step-host");
  var elSummary = document.getElementById("dx-summary");
  var elProgFill = document.getElementById("dx-prog-fill");
  var elProgStep = document.getElementById("dx-prog-step");
  var elProgName = document.getElementById("dx-prog-name");
  var elBack = document.getElementById("dx-back");
  var elNext = document.getElementById("dx-next");

  var CHECK = '<span class="dx-tick"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>';

  function renderStep() {
    var point = POINTS[stepIndex - 1];
    var qs = Q[point.key];
    var html = "";
    qs.forEach(function (q, qi) {
      html += '<div class="dx-q">';
      html += '<div class="dx-q-num">Pregunta ' + (qi + 1) + " de " + qs.length + "</div>";
      html += '<div class="dx-q-label">' + q.label + "</div>";
      if (q.help) html += '<p class="dx-q-help">' + q.help + "</p>";
      if (q.type === "choice") {
        html += '<div class="dx-opts">';
        q.opts.forEach(function (o, oi) {
          var sel = answers[q.id] && answers[q.id].t === o.t ? " is-sel" : "";
          html += '<button type="button" class="dx-opt' + sel + '" data-q="' + q.id + '" data-oi="' + oi + '">' + CHECK + "<span>" + o.t + "</span></button>";
        });
        html += "</div>";
      } else if (q.type === "scale") {
        html += '<div class="dx-scale">';
        for (var v = 1; v <= 5; v++) {
          var s = Math.round((v - 1) / 4 * 100);
          var sels = answers[q.id] && answers[q.id].v === v ? " is-sel" : "";
          html += '<button type="button" class="dx-opt' + sels + '" data-q="' + q.id + '" data-scale="' + v + '" data-score="' + s + '">' + v + "</button>";
        }
        html += "</div>";
        var lg = q.legend || ["", ""];
        html += '<div class="dx-scale-legend"><span>' + lg[0] + "</span><span>" + lg[1] + "</span></div>";
      } else if (q.type === "open") {
        var cur = answers[q.id] ? answers[q.id].v : "";
        html += '<textarea class="dx-open" data-q="' + q.id + '" rows="2" maxlength="180" placeholder="' + (q.placeholder || "") + '">' + esc(cur) + "</textarea>";
        html += '<p class="dx-open-note">Opcional · pregunta abierta corta</p>';
      }
      html += "</div>";
    });
    elStepHost.innerHTML = html;

    elProgStep.textContent = "Punto " + point.n + " de " + TOTAL;
    elProgName.textContent = point.name + " — " + point.sub;
    elProgFill.style.width = (stepIndex / TOTAL * 100) + "%";
    elBack.hidden = stepIndex === 1;
    elNext.textContent = stepIndex === TOTAL ? "Ver mi diagnóstico" : "Siguiente";
    bindStep();
    updateNext();
    elWizard.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function bindStep() {
    elStepHost.querySelectorAll(".dx-opt").forEach(function (b) {
      b.addEventListener("click", function () {
        var qid = b.getAttribute("data-q");
        if (b.hasAttribute("data-scale")) {
          answers[qid] = { s: +b.getAttribute("data-score"), t: b.textContent, v: +b.getAttribute("data-scale") };
        } else {
          var oi = +b.getAttribute("data-oi");
          var q = findQ(qid), o = q.opts[oi];
          answers[qid] = { s: o.s, t: o.t, v: o.t };
        }
        // marca selección dentro del mismo grupo (mismo data-q)
        elStepHost.querySelectorAll('[data-q="' + qid + '"]').forEach(function (x) { x.classList.remove("is-sel"); });
        b.classList.add("is-sel");
        updateNext();
      });
    });
    elStepHost.querySelectorAll(".dx-open").forEach(function (t) {
      t.addEventListener("input", function () {
        var qid = t.getAttribute("data-q"), v = t.value;
        answers[qid] = { s: openScore(v), t: v, v: v };
        updateNext();
      });
    });
  }

  function findQ(id) {
    var found = null;
    Object.keys(Q).forEach(function (k) { Q[k].forEach(function (q) { if (q.id === id) found = q; }); });
    return found;
  }

  // Requiere responder las preguntas NO abiertas del paso actual
  function stepAnswered() {
    var point = POINTS[stepIndex - 1];
    return Q[point.key].every(function (q) {
      return q.type === "open" ? true : !!answers[q.id];
    });
  }
  function updateNext() { elNext.disabled = !stepAnswered(); }

  /* ------------------------------ Resumen ------------------------------- */
  function buildResults() {
    return POINTS.map(function (p) {
      var sc = pointScore(p.key), lv = level(sc);
      return { key: p.key, name: p.name, sub: p.sub, n: p.n, score: sc, lv: lv };
    });
  }
  function weakest(results) {
    return results.reduce(function (a, b) { return b.score < a.score ? b : a; }, results[0]);
  }

  function showSummary() {
    var results = buildResults();
    var weak = weakest(results);
    completed = true;
    track("dx_complete", { punto_debil: weak.name, punto_debil_score: weak.score });

    var html = "";
    results.forEach(function (r) {
      var st = STATE_TXT[r.lv];
      html += '<div class="dx-sem">' +
        '<span class="dx-dot dx-dot--' + r.lv + '"></span>' +
        '<div class="dx-sem-main"><div class="dx-sem-name">' + r.name + '</div>' +
        '<div class="dx-sem-line">' + st.line + '</div></div>' +
        '<span class="dx-sem-state dx-sem-state--' + r.lv + '">' + st.label + '</span></div>';
    });
    document.getElementById("dx-sem-list").innerHTML = html;
    document.getElementById("dx-weak-name").textContent = weak.name + " — " + weak.sub;

    elWizard.style.display = "none";
    elSummary.style.display = "block";
    // valor dinámico para el copy del CTA del reporte
    root.setAttribute("data-weak", weak.name);
    elSummary.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  /* --------------------------- Reporte completo ------------------------- */
  var RECICON = '<svg viewBox="0 0 24 24" fill="none" stroke-width="2.4"><path d="M20 6 9 17l-5-5"/></svg>';

  function buildReport() {
    var results = buildResults();
    var weak = weakest(results);
    var overall = Math.round(results.reduce(function (s, r) { return s + r.score; }, 0) / results.length);

    var html = "";
    // Encabezado imprimible
    html += '<div class="dx-print-only dx-print-head"><img src="../assets/img/easy-mkt-logo.png" alt="Easy Marketing"><span>Diagnóstico de los 7 Puntos</span></div>';
    html += '<div class="dx-rep-head"><div class="dx-rep-score">' + overall + '<span style="font-size:20px;color:var(--text-muted)">/100</span></div>' +
      '<p class="dx-rep-exp" style="margin-top:4px;color:var(--text-muted)">Índice general de por qué la gente te compra (o no). Abajo, cada punto con recomendaciones específicas para tu negocio.</p></div>';

    results.forEach(function (r) {
      var isWeak = r.key === weak.key;
      html += '<div class="dx-rep-point' + (isWeak ? " is-weak" : "") + '">';
      html += '<div class="dx-rep-top"><span class="dx-dot dx-dot--' + r.lv + '"></span>' +
        '<h3>' + r.n + ". " + r.name + '</h3>' +
        '<span class="dx-rep-badge">' + r.score + '/100 · ' + STATE_TXT[r.lv].label + '</span></div>';
      html += '<p class="dx-rep-exp">' + EXPLAIN[r.lv] + '</p>';
      recs(r.key).forEach(function (txt) {
        html += '<div class="dx-rec">' + RECICON + '<span>' + txt + '</span></div>';
      });
      html += "</div>";
    });

    // Prioridad #1
    html += '<div class="dx-priority"><span class="l">Prioridad #1 a resolver</span>' +
      '<h3>' + weak.name + ' — ' + weak.sub + '</h3>' +
      '<p>Es tu punto más débil (' + weak.score + '/100) y hoy es donde estás dejando ventas sobre la mesa. Empieza por aquí: es la palanca de mayor retorno para que más gente te compre.</p></div>';

    // CTAs (no se imprimen)
    var calCopy = "Agenda 30 min y resolvemos juntos tu " + weak.name;
    html += '<div class="dx-rep-cta">' +
      '<div class="dx-rep-actions">' +
        '<button type="button" class="btn dx-btn-pdf" id="dx-pdf"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="margin-right:2px"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>Descargar PDF</button>' +
        '<a class="btn btn--primary btn--lg" id="dx-cal" href="https://calendly.com/easymktok/asesoria">' + calCopy + '</a>' +
      '</div>' +
      '<a class="btn btn--wa" href="https://api.whatsapp.com/send/?phone=5215539787305&text=Hola%2C+hice+el+diagn%C3%B3stico+de+los+7+puntos+y+quiero+revisar+mi+punto+m%C3%A1s+d%C3%A9bil&type=phone_number&app_absent=0">' +
        '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.8 9.8 0 0 0 12.04 2Z"/></svg>' +
        'Prefiero escribir por WhatsApp</a>' +
      '<p class="btn-note" style="margin-top:14px">Tu reporte también quedó en el correo con el que te registraste.</p>' +
    '</div>';

    // Pie imprimible
    html += '<div class="dx-print-only dx-print-foot">Easy Marketing · Easy Growth System™ · easymarketing.mx — Reporte generado a partir de tus respuestas.</div>';

    return { html: html, results: results, weak: weak, overall: overall, calCopy: calCopy };
  }

  function showReport() {
    var rep = buildReport();
    var host = document.getElementById("dx-report");
    host.innerHTML = rep.html;
    document.getElementById("dx-gate").style.display = "none";
    host.style.display = "block";
    track("dx_report_unlocked", { punto_debil: rep.weak.name, indice_general: rep.overall });

    var pdf = document.getElementById("dx-pdf");
    if (pdf) pdf.addEventListener("click", function () {
      track("dx_pdf_download", { punto_debil: rep.weak.name });
      window.print();
    });
    var cal = document.getElementById("dx-cal");
    if (cal) cal.addEventListener("click", function () {
      track("dx_calendly_click", { punto_debil: rep.weak.name, location: "reporte_7_puntos" });
    });
    host.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  /* --------------------------- Captura de contacto ---------------------- */
  var PERSONAL = ["gmail.com","hotmail.com","outlook.com","yahoo.com","live.com","icloud.com","hotmail.es","yahoo.com.mx"];
  var form = document.getElementById("dx-lead-form");
  if (form) {
    var emailEl = form.querySelector("input[type=email]");
    var hint = form.querySelector("[data-email-hint]");
    var errorEl = form.querySelector("[data-form-error]");
    if (emailEl && hint) emailEl.addEventListener("blur", function () {
      var dom = (emailEl.value || "").toLowerCase().split("@")[1] || "";
      hint.hidden = !(dom && PERSONAL.indexOf(dom) !== -1);
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (errorEl) errorEl.hidden = true;
      if (!form.checkValidity()) { form.reportValidity(); return; }

      // Inyecta el resultado del diagnóstico en el correo del lead
      var results = buildResults(), weak = weakest(results);
      form.querySelectorAll(".dx-injected").forEach(function (n) { n.remove(); });
      function hidden(name, value) {
        var i = document.createElement("input");
        i.type = "hidden"; i.name = name; i.value = value; i.className = "dx-injected";
        form.appendChild(i);
      }
      results.forEach(function (r) { hidden("Punto " + r.n + " · " + r.name, r.score + "/100 (" + STATE_TXT[r.lv].label + ")"); });
      hidden("Punto más débil", weak.name + " (" + weak.score + "/100)");
      hidden("Índice general", Math.round(results.reduce(function (s, r) { return s + r.score; }, 0) / results.length) + "/100");
      if (answers.prob_frase && answers.prob_frase.v) hidden("Problema que resuelve (en sus palabras)", answers.prob_frase.v);
      if (answers.dif_frase && answers.dif_frase.v) hidden("Diferenciador (en sus palabras)", answers.dif_frase.v);

      /* --- Envío paralelo a Make (fire-and-forget, NO bloquea el flujo) ---
         Reutiliza exactamente los mismos valores que van a FormSubmit,
         empaquetados como JSON. Si falla o tarda, no afecta al usuario. */
      (function () {
        function fv(name) { var el = form.querySelector('[name="' + name + '"]'); return el ? (el.value || "") : ""; }
        var byKey = {};
        results.forEach(function (r) { byKey[r.key] = r.score + "/100"; });
        var overall = Math.round(results.reduce(function (s, r) { return s + r.score; }, 0) / results.length);
        var consentEl = form.querySelector('[name="consentimiento"]');
        var payload = {
          nombre: fv("nombre"),
          negocio: fv("negocio"),
          email: fv("email"),
          whatsapp: fv("whatsapp"),
          consentimiento: (consentEl && consentEl.checked) ? (consentEl.value || "Sí") : "No",
          punto_1_publico: byKey.publico,
          punto_2_problema: byKey.problema,
          punto_3_solucion: byKey.solucion,
          punto_4_diferenciales: byKey.diferenciales,
          punto_5_testimonios: byKey.testimonios,
          punto_6_objeciones: byKey.objeciones,
          punto_7_garantia: byKey.garantia,
          punto_mas_debil: weak.name + " (" + weak.score + "/100)",
          indice_general: overall + "/100",
          problema_respuesta: (answers.prob_frase && answers.prob_frase.v) || "",
          diferenciador_respuesta: (answers.dif_frase && answers.dif_frase.v) || ""
        };
        try {
          fetch("https://hook.us2.make.com/75gb150bmqtepgh5jypy29gu7fkpb8zk", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
          }).catch(function (err) { console.error("Error enviando a Make:", err); });
        } catch (err) { console.error("Error enviando a Make:", err); }
      })();

      var btn = form.querySelector("button[type=submit]");
      var original = btn ? btn.textContent : "";
      if (btn) { btn.disabled = true; btn.textContent = "Generando tu reporte…"; }
      track("dx_lead_submit", { punto_debil: weak.name });

      fetch(form.getAttribute("action"), { method: "POST", body: new FormData(form), headers: { "Accept": "application/json" } })
        .then(function (r) { if (!r.ok) throw new Error("bad"); return r; })
        .then(function () { showReport(); })
        .catch(function () {
          if (btn) { btn.disabled = false; btn.textContent = original; }
          if (errorEl) errorEl.hidden = false;
        });
    });
  }

  /* ------------------------------ Navegación ---------------------------- */
  document.getElementById("dx-start").addEventListener("click", function () {
    started = true;
    track("dx_start");
    stepIndex = 1;
    elIntro.style.display = "none";
    elWizard.style.display = "block";
    renderStep();
  });
  elNext.addEventListener("click", function () {
    if (!stepAnswered()) return;
    if (stepIndex === TOTAL) { showSummary(); return; }
    stepIndex++; renderStep();
  });
  elBack.addEventListener("click", function () {
    if (stepIndex > 1) { stepIndex--; renderStep(); }
  });
})();
