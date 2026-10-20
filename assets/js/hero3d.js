/* StudentHub — lightweight Three.js scenes (low-poly, capped particles, paused off-screen).
   Usage: <canvas class="hero-canvas" data-scene="hero|auth"></canvas> */
(function () {
  "use strict";
  if (typeof THREE === "undefined") return;
  var canvases = document.querySelectorAll("canvas[data-scene]");
  if (!canvases.length) return;
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var mobile = window.matchMedia("(max-width: 768px)").matches || /Mobi|Android/i.test(navigator.userAgent);

  canvases.forEach(function (canvas) {
    var scene = canvas.getAttribute("data-scene");
    var isHero = scene === "hero";
    var PARTICLES = isHero ? (mobile ? 700 : 1600) : (mobile ? 250 : 500);

    var renderer;
    try { renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: false, alpha: true, powerPreference: "low-power" }); }
    catch (e) { canvas.style.display = "none"; return; }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mobile ? 1.25 : 1.75));

    var sc = new THREE.Scene();
    var cam = new THREE.PerspectiveCamera(55, 1, 0.1, 100);
    cam.position.set(0, 0, isHero ? 9 : 10);

    var root = new THREE.Group();
    sc.add(root);

    /* Galaxy-spiral particles = "growth". Single draw call. */
    var pos = new Float32Array(PARTICLES * 3), col = new Float32Array(PARTICLES * 3);
    var c1 = new THREE.Color("#8b7bff"), c2 = new THREE.Color("#59dcff"), c3 = new THREE.Color("#ff7ad9"), tmp = new THREE.Color();
    for (var i = 0; i < PARTICLES; i++) {
      var t = i / PARTICLES, arm = i % 3, ang = t * 14 + arm * (Math.PI * 2 / 3);
      var rad = 1.2 + t * (isHero ? 7 : 8);
      var jitter = (Math.random() - 0.5) * (0.35 + t * 1.1);
      pos[i * 3] = Math.cos(ang) * rad + jitter;
      pos[i * 3 + 1] = (Math.random() - 0.5) * (0.6 + t * 1.6) + Math.sin(ang * 2) * 0.25;
      pos[i * 3 + 2] = Math.sin(ang) * rad + jitter;
      tmp.copy(c1).lerp(c2, t); if (arm === 2) tmp.lerp(c3, 0.45);
      col[i * 3] = tmp.r; col[i * 3 + 1] = tmp.g; col[i * 3 + 2] = tmp.b;
    }
    var geo = new THREE.BufferGeometry();
    geo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
    geo.setAttribute("color", new THREE.BufferAttribute(col, 3));
    var pts = new THREE.Points(geo, new THREE.PointsMaterial({ size: isHero ? 0.06 : 0.05, vertexColors: true, transparent: true, opacity: 0.9, depthWrite: false, blending: THREE.AdditiveBlending }));
    pts.rotation.x = 0.5;
    root.add(pts);

    var core, shell, orbiters = [];
    if (isHero) {
      /* Low-poly "knowledge crystal": icosahedron (80 faces) + wire shell */
      core = new THREE.Mesh(new THREE.IcosahedronGeometry(1.35, 1),
        new THREE.MeshBasicMaterial({ color: 0x7966f5, wireframe: true, transparent: true, opacity: 0.85 }));
      shell = new THREE.Mesh(new THREE.OctahedronGeometry(2.1, 0),
        new THREE.MeshBasicMaterial({ color: 0x59dcff, wireframe: true, transparent: true, opacity: 0.35 }));
      root.add(core, shell);
      for (var k = 0; k < 4; k++) {
        var m = new THREE.Mesh(new THREE.TetrahedronGeometry(0.18, 0), new THREE.MeshBasicMaterial({ color: k % 2 ? 0xff7ad9 : 0x59dcff }));
        m.userData = { r: 2.9 + k * 0.45, s: 0.5 + k * 0.17, o: k * 1.6 };
        orbiters.push(m); root.add(m);
      }
      root.position.x = mobile ? 0 : 2.4;
      root.position.y = mobile ? 2.2 : 0;
    }

    function resize() {
      var w = canvas.clientWidth || 1, h = canvas.clientHeight || 1;
      renderer.setSize(w, h, false);
      cam.aspect = w / h; cam.updateProjectionMatrix();
    }
    resize();
    window.addEventListener("resize", resize);

    var mx = 0, my = 0, tx = 0, ty = 0;
    window.addEventListener("pointermove", function (e) { mx = (e.clientX / window.innerWidth - 0.5) * 2; my = (e.clientY / window.innerHeight - 0.5) * 2; }, { passive: true });

    var visible = true, raf = 0, clock = new THREE.Clock();
    function frame() {
      raf = requestAnimationFrame(frame);
      var dt = Math.min(clock.getDelta(), 0.05), t = clock.elapsedTime;
      tx += (mx - tx) * 0.05; ty += (my - ty) * 0.05;
      pts.rotation.y += dt * 0.08;
      root.rotation.y = tx * 0.35; root.rotation.x = ty * 0.18;
      if (isHero) {
        core.rotation.x += dt * 0.25; core.rotation.y += dt * 0.35;
        shell.rotation.x -= dt * 0.12; shell.rotation.y -= dt * 0.18;
        var s = 1 + Math.sin(t * 1.4) * 0.04; core.scale.set(s, s, s);
        orbiters.forEach(function (o) {
          var d = o.userData; o.position.set(Math.cos(t * d.s + d.o) * d.r, Math.sin(t * d.s * 1.3 + d.o) * 0.9, Math.sin(t * d.s + d.o) * d.r);
          o.rotation.x += dt; o.rotation.y += dt * 1.3;
        });
      }
      renderer.render(sc, cam);
    }
    function start() { if (!raf && !reduce) { clock.start(); frame(); } }
    function stop() { cancelAnimationFrame(raf); raf = 0; }

    if (reduce) renderer.render(sc, cam);
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (en) { visible = en[0].isIntersecting; visible ? start() : stop(); }, { threshold: 0 }).observe(canvas);
    } else start();
    document.addEventListener("visibilitychange", function () { document.hidden ? stop() : (visible && start()); });
  });
})();
