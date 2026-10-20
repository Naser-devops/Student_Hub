/* StudentHub — dashboard charts (Chart.js). Data comes from <script id="sh-chart-data" type="application/json"> printed by PHP. */
(function () {
  "use strict";
  if (typeof Chart === "undefined") return;
  var node = document.getElementById("sh-chart-data");
  if (!node) return;
  var data; try { data = JSON.parse(node.textContent); } catch (e) { return; }

  Chart.defaults.color = "#93a3bd";
  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.borderColor = "rgba(255,255,255,.06)";
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduce) Chart.defaults.animation = false;

  function gradient(ctx, a, b) {
    var g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight || 260);
    g.addColorStop(0, a); g.addColorStop(1, b); return g;
  }

  var activity = document.getElementById("chart-activity");
  if (activity) {
    var ctx = activity.getContext("2d");
    var chart = new Chart(ctx, {
      type: "line",
      data: { labels: data.week.labels, datasets: [{ label: "Study hours", data: data.week.values, tension: 0.4, fill: true, borderWidth: 3,
        borderColor: "#8b7bff", backgroundColor: gradient(ctx, "rgba(139,123,255,.45)", "rgba(139,123,255,0)"),
        pointBackgroundColor: "#59dcff", pointRadius: 4, pointHoverRadius: 7 }] },
      options: { responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: "index" },
        plugins: { legend: { display: false }, tooltip: { backgroundColor: "#0d1424", borderColor: "rgba(255,255,255,.12)", borderWidth: 1, padding: 12 } },
        scales: { y: { beginAtZero: true, border: { display: false } }, x: { grid: { display: false } } } }
    });
    document.querySelectorAll("[data-range]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var key = btn.getAttribute("data-range");
        document.querySelectorAll("[data-range]").forEach(function (b) { b.classList.toggle("btn-primary", b === btn); b.classList.toggle("btn-ghost", b !== btn); });
        chart.data.labels = data[key].labels; chart.data.datasets[0].data = data[key].values; chart.update();
      });
    });
  }

  var donut = document.getElementById("chart-donut");
  if (donut) {
    new Chart(donut, {
      type: "doughnut",
      data: { labels: ["Completed", "Remaining"], datasets: [{ data: [data.overall, 100 - data.overall], backgroundColor: ["#8b7bff", "rgba(255,255,255,.08)"], borderWidth: 0, hoverOffset: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: "78%", plugins: { legend: { display: false } } }
    });
  }

  var bars = document.getElementById("chart-courses");
  if (bars && data.courses.labels.length) {
    var c = bars.getContext("2d");
    new Chart(bars, {
      type: "bar",
      data: { labels: data.courses.labels, datasets: [{ label: "Progress %", data: data.courses.values, borderRadius: 10, borderSkipped: false,
        backgroundColor: gradient(c, "#59dcff", "#7966f5") }] },
      options: { indexAxis: "y", responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
        scales: { x: { max: 100, border: { display: false } }, y: { grid: { display: false } } } }
    });
  }
})();
