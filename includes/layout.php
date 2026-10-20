<?php
/**
 * StudentHub — shared UI layout helpers (frontend only, no business logic).
 * Usage in a page:
 *   require_once __DIR__ . "/../includes/layout.php";
 *   sh_head("Title");                         // <head> + opens <body>
 *   sh_app_open("dashboard", $fullName, $role); // sidebar shell (logged-in pages)
 *   ... page markup ...
 *   sh_app_close();
 *   sh_scripts(["dashboard"]);                // closes <body>; extra page scripts
 * $base is "../" for files in /pages and "" for the project root (index.php).
 */

if (!function_exists("sh_e")) {
    function sh_e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

/** Deterministic gradient + icon for a course card cover (placeholder until you add images). */
function sh_cover(int $id): array
{
    $icons = ["fa-code", "fa-database", "fa-palette", "fa-chart-line", "fa-robot", "fa-shield-halved", "fa-mobile-screen", "fa-cloud"];
    $h1 = ($id * 47) % 360;
    $h2 = ($h1 + 70) % 360;
    return [
        "style" => "background:linear-gradient(135deg,hsl($h1 80% 55% / .85),hsl($h2 85% 45% / .85))",
        "icon"  => $icons[$id % count($icons)],
    ];
}

function sh_head(string $title, string $base = "../", string $bodyClass = ""): void
{
    ?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#05070f">
    <title><?= sh_e($title) ?> — StudentHub</title>
    <script>document.documentElement.className = "js";</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { display: ["Space Grotesk", "sans-serif"], sans: ["DM Sans", "system-ui", "sans-serif"] },
                colors: { brand: { violet: "#8b7bff", cyan: "#59dcff", pink: "#ff7ad9" }, ink: { 950: "#05070f", 900: "#070b16", 800: "#0d1424", 700: "#111a2d" } }
            } }
        };
    </script>
    <link rel="stylesheet" href="<?= $base ?>assets/css/theme.css">
</head>
<body class="sh sh-bg <?= sh_e($bodyClass) ?>">
<a class="skip" href="#main">Skip to content</a>
<?php
}

/** Flash messages stored in $_SESSION by the existing PHP handlers. */
function sh_flash(): void
{
    foreach (["success_message" => "alert-ok", "error_message" => "alert-err"] as $key => $class) {
        if (isset($_SESSION[$key])) {
            $icon = $key === "success_message" ? "fa-circle-check" : "fa-triangle-exclamation";
            echo '<div class="alert ' . $class . ' mb-6" role="status" data-reveal><i class="fa-solid ' . $icon . ' mt-0.5"></i><span>'
                . sh_e($_SESSION[$key]) . '</span></div>';
            unset($_SESSION[$key]);
        }
    }
}

function sh_app_open(string $active, string $fullName, string $role = "student"): void
{
    $items = [
        "dashboard" => ["dashboard.php", "fa-gauge-high", "Dashboard"],
        "courses"   => ["courses.php", "fa-compass", "Explore Courses"],
        "my"        => ["my-courses.php", "fa-graduation-cap", "My Courses"],
        "profile"   => ["profile.php", "fa-user-astronaut", "My Profile"],
    ];
    if ($role === "admin") {
        $items["admin"] = ["admin.php", "fa-shield-halved", "Admin Panel"];
    }
    $initial = mb_strtoupper(mb_substr(trim($fullName) !== "" ? $fullName : "S", 0, 1, "UTF-8"), "UTF-8");
    ?>
<div class="scrim" id="scrim"></div>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Sidebar">
        <a href="../index.php" class="flex items-center gap-3 font-display text-xl font-bold">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-violet-400 to-blue-500 shadow-lg shadow-violet-500/30"><i class="fa-solid fa-star-of-life text-sm"></i></span>
            StudentHub
        </a>
        <nav class="flex flex-1 flex-col gap-1.5" aria-label="Main">
            <?php foreach ($items as $key => [$href, $icon, $label]): ?>
                <a class="nav-link <?= $key === $active ? "active" : "" ?>" href="<?= $href ?>"><i class="fa-solid <?= $icon ?>"></i><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="glass rounded-2xl p-3">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-cyan-300 to-violet-400 font-bold text-slate-900"><?= sh_e($initial) ?></span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold"><?= sh_e($fullName) ?></p>
                    <p class="text-xs capitalize text-slate-400"><?= sh_e($role) ?></p>
                </div>
            </div>
            <a href="logout.php" class="btn-ghost mt-3 w-full !py-2 text-sm"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a>
        </div>
    </aside>
    <div class="min-w-0">
        <div class="sticky top-0 z-20 flex items-center justify-between px-4 py-3 lg:hidden glass-strong">
            <button id="sidebar-toggle" class="btn-ghost !px-3 !py-2" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
            <span class="font-display font-bold">StudentHub</span>
            <span class="w-10"></span>
        </div>
        <main id="main" class="app-main">
<?php
}

function sh_app_close(): void
{
    echo "        </main>\n    </div>\n</div>\n";
}

/** Closes <body>. $pageScripts: any of "hero", "dashboard", "player". */
function sh_scripts(array $pageScripts = [], string $base = "../"): void
{
    $needsThree = in_array("hero", $pageScripts, true);
    $needsChart = in_array("dashboard", $pageScripts, true);
    ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<?php if ($needsThree): ?><script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script><?php endif; ?>
<?php if ($needsChart): ?><script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script><?php endif; ?>
<script src="<?= $base ?>assets/js/app.js"></script>
<?php if ($needsThree): ?><script src="<?= $base ?>assets/js/hero3d.js"></script><?php endif; ?>
<?php if ($needsChart): ?><script src="<?= $base ?>assets/js/dashboard.js"></script><?php endif; ?>
</body>
</html>
<?php
}
