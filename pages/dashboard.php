<?php

session_start();

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

$fullName = $_SESSION["full_name"] ?? "Student";
$userId = $_SESSION["user_id"];

// Role (used only to show the Admin link in the sidebar)
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$role = $stmt->fetchColumn();
if ($role === false) {
    $_SESSION = [];
    session_regenerate_id(true);
    header("Location: login.php");
    exit;
}

// Enrolled courses (real data)
$stmt = $pdo->prepare(
    "SELECT courses.id, courses.course_name, courses.description
     FROM enrollments
     INNER JOIN courses ON enrollments.course_id = courses.id
     WHERE enrollments.user_id = ?
     ORDER BY courses.id DESC"
);
$stmt->execute([$userId]);
$myCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total courses in the catalog (real data)
$totalCourses = (int) $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

/*
 * PLACEHOLDER METRICS — your database has no progress/lessons/hours tables yet.
 * The values below are deterministic demo numbers so the UI looks alive.
 * Replace $progressFor() and the $week arrays with real SQL when those tables exist.
 */
$progressFor = function (int $courseId): int {
    return 20 + (abs(crc32((string) $courseId)) % 71); // 20..90
};
$enrolledCount = count($myCourses);
$progressList = [];
foreach ($myCourses as $c) {
    $progressList[(int) $c["id"]] = $progressFor((int) $c["id"]);
}
$overall = $enrolledCount ? (int) round(array_sum($progressList) / $enrolledCount) : 0;
$lessonsDone = $enrolledCount * 7 + (int) round($overall / 10);
$studyHours = $enrolledCount * 6 + 2;

$chartData = [
    "week"    => ["labels" => ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], "values" => [1.5, 2.2, 1.8, 3.1, 2.6, 4.0, 2.9]],
    "month"   => ["labels" => ["W1", "W2", "W3", "W4"], "values" => [9.5, 12.8, 11.2, 16.4]],
    "overall" => $overall,
    "courses" => [
        "labels" => array_map(fn($c) => mb_strimwidth($c["course_name"], 0, 22, "…", "UTF-8"), $myCourses),
        "values" => array_values($progressList),
    ],
];

$hour = (int) date("G");
$greeting = $hour < 12 ? "Good morning" : ($hour < 18 ? "Good afternoon" : "Good evening");

sh_head("Dashboard");
sh_app_open("dashboard", $fullName, $role);
?>

<?php sh_flash(); ?>

<header class="flex flex-wrap items-end justify-between gap-4" data-reveal>
    <div>
        <span class="chip">Dashboard</span>
        <h1 class="font-display mt-3 text-3xl font-bold tracking-tight md:text-4xl">
            <?= $greeting ?>, <span class="gradient-text"><?= sh_e(explode(" ", trim($fullName))[0]) ?></span> 👋
        </h1>
        <p class="mt-1 text-slate-400">You have successfully logged in to StudentHub. Here's your learning snapshot.</p>
    </div>
    <a class="btn-primary" href="courses.php"><i class="fa-solid fa-compass"></i> Explore Courses</a>
</header>

<!-- Stat counters -->
<section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Statistics">
    <?php
    $stats = [
        ["fa-book-open", "Enrolled Courses", $enrolledCount, "", "from-violet-500/40 to-violet-400/10"],
        ["fa-layer-group", "Available Courses", $totalCourses, "", "from-cyan-400/40 to-cyan-300/10"],
        ["fa-circle-check", "Lessons Completed", $lessonsDone, "", "from-pink-500/40 to-pink-400/10"],
        ["fa-clock", "Study Hours", $studyHours, "h", "from-emerald-400/40 to-emerald-300/10"],
    ];
    foreach ($stats as $i => [$icon, $label, $value, $suffix, $grad]): ?>
        <article class="glass glow-card p-5" data-reveal data-delay="<?= $i * 0.08 ?>">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400"><?= $label ?></p>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br <?= $grad ?>"><i class="fa-solid <?= $icon ?>"></i></span>
            </div>
            <p class="font-display mt-3 text-4xl font-bold" data-count="<?= (int) $value ?>" data-suffix="<?= $suffix ?>">0<?= $suffix ?></p>
        </article>
    <?php endforeach; ?>
</section>

<!-- Charts -->
<section class="mt-6 grid gap-6 xl:grid-cols-3">
    <article class="glass rounded-3xl p-6 xl:col-span-2" data-reveal>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">Learning activity</h2>
                <p class="text-sm text-slate-400">Hours studied</p>
            </div>
            <div class="flex gap-2">
                <button type="button" data-range="week" class="btn-primary !px-4 !py-2 text-sm">Week</button>
                <button type="button" data-range="month" class="btn-ghost !px-4 !py-2 text-sm">Month</button>
            </div>
        </div>
        <div class="relative mt-5 h-64"><canvas id="chart-activity" aria-label="Learning activity chart" role="img"></canvas></div>
    </article>

    <article class="glass rounded-3xl p-6" data-reveal data-delay="0.1">
        <h2 class="font-display text-xl font-semibold">Overall progress</h2>
        <div class="relative mx-auto mt-5 h-52 w-52">
            <canvas id="chart-donut" aria-label="Overall progress" role="img"></canvas>
            <div class="absolute inset-0 grid place-items-center text-center">
                <div><p class="font-display text-4xl font-bold" data-count="<?= $overall ?>" data-suffix="%">0%</p><p class="text-xs text-slate-400">complete</p></div>
            </div>
        </div>
    </article>
</section>

<!-- Course progress -->
<section class="mt-6 grid gap-6 xl:grid-cols-5">
    <article class="glass rounded-3xl p-6 xl:col-span-3" data-reveal>
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold">Continue learning</h2>
            <a class="text-sm font-semibold text-cyan-300 hover:underline" href="my-courses.php">View all</a>
        </div>
        <?php if (empty($myCourses)): ?>
            <div class="mt-6 rounded-2xl border border-dashed border-white/15 p-8 text-center">
                <i class="fa-solid fa-seedling text-3xl text-cyan-300"></i>
                <p class="mt-3 text-slate-300">You haven't enrolled in any courses yet.</p>
                <a class="btn-primary mt-4" href="courses.php">Find your first course</a>
            </div>
        <?php else: ?>
            <ul class="mt-5 space-y-3">
                <?php foreach (array_slice($myCourses, 0, 5) as $course):
                    $p = $progressList[(int) $course["id"]]; ?>
                    <li>
                        <a class="glass glow-card flex items-center gap-4 rounded-2xl p-4" href="course-view.php?id=<?= (int) $course["id"] ?>">
                            <div class="ring shrink-0" data-p="<?= $p ?>"><span><?= $p ?>%</span></div>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate font-semibold"><?= sh_e($course["course_name"]) ?></h3>
                                <p class="truncate text-sm text-slate-400"><?= sh_e($course["description"] ?? "") ?></p>
                                <div class="bar mt-2"><i data-width="<?= $p ?>"></i></div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-slate-500"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="glass rounded-3xl p-6 xl:col-span-2" data-reveal data-delay="0.1">
        <h2 class="font-display text-xl font-semibold">Progress by course</h2>
        <?php if (empty($myCourses)): ?>
            <p class="mt-4 text-slate-400">Enroll in a course to see your progress chart.</p>
        <?php else: ?>
            <div class="relative mt-4" style="height: <?= max(160, count($myCourses) * 46) ?>px"><canvas id="chart-courses" aria-label="Progress per course" role="img"></canvas></div>
        <?php endif; ?>
    </article>
</section>

<script id="sh-chart-data" type="application/json"><?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

<?php
sh_app_close();
sh_scripts(["dashboard"]);
