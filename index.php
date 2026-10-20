<?php
session_start();
require_once __DIR__ . "/includes/layout.php";

$loggedIn = isset($_SESSION["user_id"]);
$courses = [];

// Load latest courses for the public catalog. Landing page must still render if the DB is unreachable.
try {
    define("SH_DB_OPTIONAL", true); // landing page must still render if the DB is not ready
    require_once __DIR__ . "/config/db.php";
    $stmt = $pdo->prepare("SELECT id, course_name, description FROM courses ORDER BY created_at DESC, id DESC LIMIT 6");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log($e->getMessage());
}

sh_head("Learn. Grow. Succeed.", "");
?>

<header data-nav class="fixed inset-x-0 top-0 z-50 transition-all">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 md:px-8">
        <a href="index.php" class="flex items-center gap-3 font-display text-xl font-bold">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-violet-400 to-blue-500 shadow-lg shadow-violet-500/30"><i class="fa-solid fa-star-of-life text-sm"></i></span>
            StudentHub
        </a>
        <nav class="flex items-center gap-2 md:gap-6" aria-label="Main navigation">
            <a class="hidden text-slate-300 transition hover:text-white md:inline" href="#features">Features</a>
            <a class="hidden text-slate-300 transition hover:text-white md:inline" href="#catalog">Courses</a>
            <?php if ($loggedIn): ?>
                <a class="btn-primary !py-2.5" href="pages/dashboard.php">Dashboard <i class="fa-solid fa-arrow-right"></i></a>
            <?php else: ?>
                <a class="text-slate-300 transition hover:text-white" href="pages/login.php">Sign in</a>
                <a class="btn-primary !py-2.5" href="pages/signup.php">Get Started <i class="fa-solid fa-arrow-right"></i></a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main">
    <!-- HERO -->
    <section class="relative grid-lines flex min-h-screen items-center overflow-hidden">
        <canvas class="hero-canvas" data-scene="hero" aria-hidden="true"></canvas>
        <div class="relative mx-auto w-full max-w-7xl px-5 pb-24 pt-32 md:px-8">
            <div class="max-w-2xl">
                <span class="chip" data-intro>A smarter way to learn</span>
                <h1 class="font-display mt-5 text-5xl font-bold leading-[1.02] tracking-tight sm:text-6xl lg:text-7xl" data-intro>
                    Your future starts <span class="gradient-text">right here.</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-slate-300/90" data-intro>
                    Register for courses, follow your progress and keep your momentum — all inside one immersive learning space.
                </p>
                <div class="mt-9 flex flex-wrap gap-3" data-intro>
                    <?php if ($loggedIn): ?>
                        <a class="btn-primary" href="pages/courses.php">Browse Courses <i class="fa-solid fa-compass"></i></a>
                    <?php else: ?>
                        <a class="btn-primary" href="pages/signup.php">Create Free Account <i class="fa-solid fa-rocket"></i></a>
                        <a class="btn-ghost" href="#catalog">Explore Courses</a>
                    <?php endif; ?>
                </div>
                <dl class="mt-14 grid max-w-lg grid-cols-3 gap-6" data-intro>
                    <div><dt class="text-xs uppercase tracking-widest text-slate-400">Courses</dt><dd class="font-display text-3xl font-bold" data-count="<?= max(count($courses), 6) ?>" data-suffix="+">0</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-slate-400">Learners</dt><dd class="font-display text-3xl font-bold" data-count="1200" data-suffix="+">0</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-slate-400">Rating</dt><dd class="font-display text-3xl font-bold" data-count="4.9">0</dd></div>
                </dl>
            </div>
        </div>
        <a href="#features" class="absolute bottom-8 left-1/2 hidden -translate-x-1/2 text-slate-400 md:block" aria-label="Scroll down"><i class="fa-solid fa-chevron-down animate-bounce"></i></a>
    </section>

    <!-- FEATURES (bento) -->
    <section id="features" class="mx-auto max-w-7xl px-5 py-24 md:px-8">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <span class="chip">Why StudentHub</span>
            <h2 class="font-display mt-4 text-4xl font-bold tracking-tight md:text-5xl">Everything you need to <span class="gradient-text">move forward.</span></h2>
            <p class="mt-4 text-slate-400">Simple tools designed to make learning feel clear, focused and rewarding.</p>
        </div>
        <div class="mt-14 grid gap-5 md:grid-cols-3">
            <?php
            $features = [
                ["fa-chart-line", "Track Progress", "See how far you've come with live stats and charts that make every milestone visible."],
                ["fa-layer-group", "Stay Organized", "All your enrolled courses, lessons and goals in one clean, searchable place."],
                ["fa-bolt", "Build Momentum", "Short lessons, clear next steps and streaks that keep you moving every day."],
            ];
            foreach ($features as $i => [$icon, $title, $text]): ?>
                <article class="glass glow-card p-7" data-reveal data-delay="<?= $i * 0.1 ?>">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-violet-500/40 to-cyan-400/30 text-xl text-cyan-200"><i class="fa-solid <?= $icon ?>"></i></span>
                    <h3 class="font-display mt-5 text-xl font-semibold"><?= $title ?></h3>
                    <p class="mt-2 text-slate-400"><?= $text ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CATALOG -->
    <section id="catalog" class="mx-auto max-w-7xl px-5 pb-24 md:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
            <div>
                <span class="chip">Course catalog</span>
                <h2 class="font-display mt-4 text-4xl font-bold tracking-tight md:text-5xl">Start learning <span class="gradient-text">today.</span></h2>
            </div>
            <a class="btn-ghost" href="<?= $loggedIn ? "pages/courses.php" : "pages/login.php" ?>">View all courses <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php if (empty($courses)): ?>
                <p class="glass col-span-full rounded-2xl p-8 text-center text-slate-400">No courses published yet — check back soon.</p>
            <?php else: ?>
                <?php foreach ($courses as $i => $row):
                    $cover = sh_cover((int) $row["id"]); ?>
                    <article class="glass glow-card flex flex-col" data-reveal data-delay="<?= ($i % 3) * 0.08 ?>">
                        <div class="relative grid h-40 place-items-center" style="<?= $cover["style"] ?>">
                            <i class="fa-solid <?= $cover["icon"] ?> text-5xl text-white/90 drop-shadow-lg"></i>
                            <span class="absolute left-4 top-4 rounded-full bg-black/30 px-3 py-1 text-xs font-semibold backdrop-blur">New</span>
                        </div>
                        <div class="flex flex-1 flex-col p-6">
                            <h3 class="font-display text-xl font-semibold"><?= sh_e($row["course_name"]) ?></h3>
                            <p class="mt-2 line-clamp-3 flex-1 text-sm text-slate-400"><?= sh_e($row["description"] ?? "") ?></p>
                            <a class="btn-ghost mt-5 !py-2.5 text-sm" href="<?= $loggedIn ? "pages/course-view.php?id=" . (int) $row["id"] : "pages/login.php" ?>">
                                <?= $loggedIn ? "Open course" : "Sign in to enroll" ?> <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA -->
    <?php if (!$loggedIn): ?>
    <section class="mx-auto max-w-5xl px-5 pb-28 md:px-8" data-reveal>
        <div class="glass relative overflow-hidden rounded-3xl p-10 text-center md:p-16">
            <div class="pointer-events-none absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-violet-600/30 blur-3xl"></div>
            <h2 class="font-display relative text-3xl font-bold md:text-5xl">Ready to level up?</h2>
            <p class="relative mx-auto mt-4 max-w-lg text-slate-300">Create your free account and join your first course in under a minute.</p>
            <a class="btn-primary relative mt-8" href="pages/signup.php">Create Free Account <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </section>
    <?php endif; ?>
</main>

<footer class="border-t border-white/10 py-8 text-center text-sm text-slate-500">© 2026 StudentHub · Built for curious minds.</footer>

<?php sh_scripts(["hero"], ""); ?>
