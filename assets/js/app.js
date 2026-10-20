/* StudentHub — shared UI behaviour. Pure progressive enhancement: pages work without it. */
(function () {
  "use strict";
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var hasGsap = typeof window.gsap !== "undefined";
  if (hasGsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

  /* ---------- scroll reveal ---------- */
  var reveals = document.querySelectorAll("[data-reveal]");
  if (reveals.length) {
    if (!hasGsap || reduce) {
      reveals.forEach(function (el) { el.style.opacity = 1; });
    } else {
      reveals.forEach(function (el) {
        var delay = parseFloat(el.getAttribute("data-delay") || 0);
        gsap.fromTo(el, { opacity: 0, y: 36 }, {
          opacity: 1, y: 0, duration: 0.9, delay: delay, ease: "power3.out",
          scrollTrigger: window.ScrollTrigger ? { trigger: el, start: "top 90%", once: true } : null
        });
      });
    }
  }

  /* ---------- counter-up ---------- */
  function runCounter(el) {
    var target = parseFloat(el.getAttribute("data-count")) || 0;
    var suffix = el.getAttribute("data-suffix") || "";
    var decimals = (String(target).split(".")[1] || "").length;
    if (!hasGsap || reduce) { el.textContent = target.toFixed(decimals) + suffix; return; }
    var obj = { v: 0 };
    gsap.to(obj, { v: target, duration: 1.8, ease: "power2.out",
      onUpdate: function () { el.textContent = obj.v.toFixed(decimals) + suffix; } });
  }
  var counters = document.querySelectorAll("[data-count]");
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries, o) {
      entries.forEach(function (en) { if (en.isIntersecting) { runCounter(en.target); o.unobserve(en.target); } });
    }, { threshold: 0.4 });
    counters.forEach(function (c) { io.observe(c); });
  } else { counters.forEach(runCounter); }

  /* animated progress bars */
  document.querySelectorAll(".bar>i[data-width]").forEach(function (b) {
    var w = b.getAttribute("data-width") + "%";
    if (hasGsap && !reduce) gsap.to(b, { width: w, duration: 1.2, delay: 0.3, ease: "power2.out" });
    else b.style.width = w;
  });
  document.querySelectorAll(".ring[data-p]").forEach(function (r) {
    var p = parseFloat(r.getAttribute("data-p")) || 0;
    if (hasGsap && !reduce) { var o = { v: 0 }; gsap.to(o, { v: p, duration: 1.4, ease: "power2.out", onUpdate: function () { r.style.setProperty("--p", o.v); } }); }
    else r.style.setProperty("--p", p);
  });

  /* ---------- glow follows cursor ---------- */
  function trackPointer(selector) {
    document.querySelectorAll(selector).forEach(function (el) {
      el.addEventListener("pointermove", function (e) {
        var r = el.getBoundingClientRect();
        el.style.setProperty("--mx", (e.clientX - r.left) + "px");
        el.style.setProperty("--my", (e.clientY - r.top) + "px");
      });
    });
  }
  trackPointer(".glow-card");

  /* ---------- 3D tilt ---------- */
  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  if (finePointer && !reduce) {
    document.querySelectorAll("[data-tilt]").forEach(function (el) {
      var max = parseFloat(el.getAttribute("data-tilt")) || 8;
      el.addEventListener("pointermove", function (e) {
        var r = el.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
        el.style.transform = "rotateY(" + ((px - 0.5) * max * 2).toFixed(2) + "deg) rotateX(" + ((0.5 - py) * max * 2).toFixed(2) + "deg) translateZ(0)";
        el.style.setProperty("--mx", (px * 100) + "%");
        el.style.setProperty("--my", (py * 100) + "%");
      });
      el.addEventListener("pointerleave", function () { el.style.transform = ""; });
    });
  }

  /* ---------- tabs ---------- */
  document.querySelectorAll("[data-tabs]").forEach(function (root) {
    var btns = root.querySelectorAll("[data-tab]");
    var panels = root.querySelectorAll("[data-panel]");
    function show(name) {
      btns.forEach(function (b) {
        var on = b.getAttribute("data-tab") === name;
        b.classList.toggle("active", on);
        b.setAttribute("aria-selected", on ? "true" : "false");
      });
      panels.forEach(function (p) {
        var on = p.getAttribute("data-panel") === name;
        p.hidden = !on;
        if (on && hasGsap && !reduce) gsap.fromTo(p, { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 0.45, ease: "power2.out" });
      });
    }
    btns.forEach(function (b) { b.addEventListener("click", function () { show(b.getAttribute("data-tab")); }); });
    if (btns.length) show(btns[0].getAttribute("data-tab"));
  });

  /* ---------- accordion ---------- */
  document.querySelectorAll("[data-accordion]").forEach(function (root) {
    var items = root.querySelectorAll(".acc-item");
    function setOpen(item, open) {
      var panel = item.querySelector(".acc-panel");
      var trigger = item.querySelector("[data-acc-trigger]");
      item.classList.toggle("open", open);
      trigger.setAttribute("aria-expanded", open ? "true" : "false");
      if (hasGsap && !reduce) gsap.to(panel, { height: open ? "auto" : 0, duration: 0.45, ease: "power2.inOut" });
      else panel.style.height = open ? "auto" : "0px";
    }
    items.forEach(function (item, i) {
      item.querySelector("[data-acc-trigger]").addEventListener("click", function () {
        var willOpen = !item.classList.contains("open");
        setOpen(item, willOpen);
      });
      if (i === 0) setOpen(item, true);
    });
  });

  /* ---------- sidebar (mobile) ---------- */
  var sb = document.getElementById("sidebar"), tg = document.getElementById("sidebar-toggle"), sc = document.getElementById("scrim");
  function toggleSidebar(force) {
    if (!sb) return;
    var open = typeof force === "boolean" ? force : !sb.classList.contains("open");
    sb.classList.toggle("open", open);
    if (sc) sc.classList.toggle("open", open);
    if (tg) tg.setAttribute("aria-expanded", open ? "true" : "false");
  }
  if (tg) tg.addEventListener("click", function () { toggleSidebar(); });
  if (sc) sc.addEventListener("click", function () { toggleSidebar(false); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") toggleSidebar(false); });

  /* ---------- live course filter ---------- */
  var search = document.querySelector("[data-filter-input]");
  if (search) {
    var cards = document.querySelectorAll("[data-filter-item]");
    var empty = document.querySelector("[data-filter-empty]");
    search.addEventListener("input", function () {
      var q = search.value.trim().toLowerCase(), shown = 0;
      cards.forEach(function (c) {
        var hit = c.getAttribute("data-filter-item").indexOf(q) !== -1;
        c.style.display = hit ? "" : "none";
        if (hit) shown++;
      });
      if (empty) empty.hidden = shown !== 0;
    });
  }

  /* ---------- glass video player ---------- */
  var player = document.querySelector("[data-player]");
  if (player) {
    var video = player.querySelector("video");
    var hasSrc = video && video.getAttribute("src");
    var big = player.querySelector("[data-play-big]");
    var toggle = player.querySelector("[data-play]");
    var seek = player.querySelector(".seek");
    var fill = seek.querySelector("i");
    var time = player.querySelector("[data-time]");
    var speedBtn = player.querySelector("[data-speed]");
    var fsBtn = player.querySelector("[data-fs]");
    var muteBtn = player.querySelector("[data-mute]");
    var duration = parseFloat(player.getAttribute("data-duration")) || 768; // demo length (seconds) when no video file
    var cur = 0, playing = false, rate = 1, timer = null;
    function fmt(s) { s = Math.max(0, Math.floor(s)); return Math.floor(s / 60) + ":" + String(s % 60).padStart(2, "0"); }
    function paint() {
      var total = hasSrc && video.duration ? video.duration : duration;
      var now = hasSrc ? video.currentTime : cur;
      fill.style.width = Math.min(100, (now / total) * 100) + "%";
      time.textContent = fmt(now) + " / " + fmt(total);
    }
    function setPlaying(on) {
      playing = on;
      toggle.innerHTML = on ? '<i class="fa-solid fa-pause"></i>' : '<i class="fa-solid fa-play"></i>';
      if (big) big.style.display = on ? "none" : "grid";
      if (hasSrc) { on ? video.play() : video.pause(); return; }
      clearInterval(timer);
      if (on) timer = setInterval(function () { cur += 0.25 * rate; if (cur >= duration) { cur = duration; setPlaying(false); } paint(); }, 250);
    }
    toggle.addEventListener("click", function () { setPlaying(!playing); });
    if (big) big.addEventListener("click", function () { setPlaying(true); });
    if (hasSrc) { video.addEventListener("timeupdate", paint); video.addEventListener("loadedmetadata", paint); video.addEventListener("ended", function () { setPlaying(false); }); }
    seek.addEventListener("click", function (e) {
      var r = seek.getBoundingClientRect(), p = (e.clientX - r.left) / r.width;
      if (hasSrc && video.duration) video.currentTime = p * video.duration; else cur = p * duration;
      paint();
    });
    speedBtn.addEventListener("click", function () {
      var rates = [1, 1.25, 1.5, 2, 0.75]; rate = rates[(rates.indexOf(rate) + 1) % rates.length];
      speedBtn.textContent = rate + "x"; if (hasSrc) video.playbackRate = rate;
    });
    muteBtn.addEventListener("click", function () {
      var m = muteBtn.getAttribute("data-muted") !== "1"; muteBtn.setAttribute("data-muted", m ? "1" : "0");
      muteBtn.innerHTML = m ? '<i class="fa-solid fa-volume-xmark"></i>' : '<i class="fa-solid fa-volume-high"></i>';
      if (hasSrc) video.muted = m;
    });
    fsBtn.addEventListener("click", function () {
      if (document.fullscreenElement) document.exitFullscreen(); else if (player.requestFullscreen) player.requestFullscreen();
    });
    document.querySelectorAll("[data-lesson]").forEach(function (l) {
      l.addEventListener("click", function () {
        document.querySelectorAll("[data-lesson]").forEach(function (x) { x.classList.remove("current"); });
        l.classList.add("current");
        var t = document.querySelector("[data-lesson-title]"); if (t) t.textContent = l.getAttribute("data-lesson");
        cur = 0; if (hasSrc) video.currentTime = 0; setPlaying(false); paint();
        player.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "center" });
      });
      l.addEventListener("keydown", function (e) { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); l.click(); } });
    });
    paint();
  }

  /* ---------- hero text intro ---------- */
  var intro = document.querySelectorAll("[data-intro]");
  if (intro.length && hasGsap && !reduce) {
    gsap.from(intro, { y: 40, opacity: 0, duration: 1, ease: "power4.out", stagger: 0.12, delay: 0.15 });
  }

  /* ---------- header scroll state ---------- */
  var nav = document.querySelector("[data-nav]");
  if (nav) {
    var onScroll = function () { nav.classList.toggle("glass-strong", window.scrollY > 24); };
    onScroll(); window.addEventListener("scroll", onScroll, { passive: true });
  }
})();
