<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$userId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"] ?? "Student";

// Course id from URL
$courseId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
if (!$courseId) {
    exit("Invalid Course ID");
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$role = $stmt->fetchColumn();
if ($role === false) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, course_name, description, created_at FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) {
    http_response_code(404);
    exit("Course Not Found");
}

$isEnrolled = false;
if ($role === "student") {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$userId, $courseId]);
    $isEnrolled = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
 * PLACEHOLDER CONTENT — no lessons/instructors/reviews tables exist yet.
 * Swap these arrays for SQL results later (e.g. SELECT ... FROM lessons WHERE course_id = ?).
 * $videoSrc: put a real .mp4 URL here (e.g. "../assets/videos/lesson1.mp4") and the player uses it;
 * when empty the glass player runs in demo mode.
 */
$videoSrc = "";
$sections = [
    ["Getting Started", [["Welcome & course overview", "4:12"], ["Setting up your tools", "8:40"], ["How to get the most out of this course", "5:05"]]],
    ["Core Concepts", [["The big picture", "12:30"], ["Hands-on walkthrough", "18:22"], ["Common mistakes", "9:48"], ["Practice exercise", "15:00"]]],
    ["Building a Real Project", [["Planning the project", "10:15"], ["Implementation — part 1", "22:10"], ["Implementation — part 2", "19:45"]]],
    ["Wrap-up", [["Review & next steps", "6:30"], ["Final quiz", "10:00"]]],
];
$lessonCount = array_sum(array_map(fn($s) => count($s[1]), $sections));
$instructor = ["name" => "StudentHub Instructor", "title" => "Senior Instructor", "bio" => "Passionate about helping students turn curiosity into real, job-ready skills with clear explanations and hands-on practice."];
$reviews = [
    ["Lina A.", 5, "Clear, well paced and genuinely useful. I finished my first project in a week."],
    ["Omar K.", 5, "The lessons are short and focused — perfect between classes."],
    ["Sara M.", 4, "Great structure. I'd love even more practice exercises."],
];
$cover = sh_cover((int) $course["id"]);

sh_head($course["course_name"]);
sh_app_open("courses", $fullName, $role);
?>

<?php sh_flash(); ?>

<nav class="mb-6 flex items-center gap-2 text-sm text-slate-400" aria-label="Breadcrumb" data-reveal>
    <a class="hover:text-white" href="courses.php">Courses</a><i class="fa-solid fa-chevron-right text-[10px]"></i>
    <span class="truncate text-slate-200"><?= sh_e($course["course_name"]) ?></span>
</nav>

<div class="grid gap-6 xl:grid-cols-3">
    <!-- LEFT: player + tabs -->
    <div class="min-w-0 xl:col-span-2">
        <div class="player" data-player data-duration="768" data-reveal>
            <?php if ($videoSrc !== ""): ?><video src="<?= sh_e($videoSrc) ?>" playsinline preload="metadata"></video><?php endif; ?>
            <div class="absolute inset-0 grid place-items-center opacity-30" style="<?= $cover["style"] ?>; mix-blend-mode:overlay"><i class="fa-solid <?= $cover["icon"] ?> text-[9rem] text-white/50"></i></div>
            <div class="glass absolute left-4 top-4 rounded-full px-4 py-1.5 text-sm"><i class="fa-solid fa-circle-play mr-2 text-cyan-300"></i><span data-lesson-title><?= sh_e($sections[0][1][0][0]) ?></span></div>
            <button type="button" class="big-play" data-play-big aria-label="Play"><i class="fa-solid fa-play ml-1"></i></button>
            <div class="player-controls glass-strong">
                <button type="button" data-play aria-label="Play / pause"><i class="fa-solid fa-play"></i></button>
                <div class="seek" role="slider" aria-label="Seek" tabindex="0"><i></i></div>
                <span class="text-xs tabular-nums text-slate-200" data-time>0:00 / 12:48</span>
                <button type="button" data-speed class="!w-auto px-2 text-xs font-bold" aria-label="Playback speed">1x</button>
                <button type="button" data-mute data-muted="0" aria-label="Mute"><i class="fa-solid fa-volume-high"></i></button>
                <button type="button" data-fs aria-label="Fullscreen"><i class="fa-solid fa-expand"></i></button>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-start justify-between gap-4" data-reveal>
            <div>
                <h1 class="font-display text-3xl font-bold tracking-tight md:text-4xl"><?= sh_e($course["course_name"]) ?></h1>
                <p class="mt-2 text-sm text-slate-400"><i class="fa-solid fa-star text-amber-300"></i> 4.8 · <?= $lessonCount ?> lessons · Updated <?= sh_e(substr((string) $course["created_at"], 0, 10)) ?></p>
            </div>

            <?php if ($role === "student"): ?>
                <?php if ($isEnrolled): ?>
                    <span class="btn-ghost cursor-default"><i class="fa-solid fa-circle-check text-emerald-300"></i> You're enrolled</span>
                <?php else: ?>
                    <!-- Same enrollment form as courses.php (course_id + csrf_token → enroll.php) -->
                    <form action="enroll.php" method="POST">
                        <input type="hidden" name="course_id" value="<?= (int) $course["id"] ?>">
                        <input type="hidden" name="csrf_token" value="<?= sh_e($_SESSION["csrf_token"]) ?>">
                        <button type="submit" class="btn-primary">Enroll Now <i class="fa-solid fa-bolt"></i></button>
                    </form>
                <?php endif; ?>
            <?php elseif ($role === "admin"): ?>
                <a class="btn-primary" href="edit-course.php?id=<?= (int) $course["id"] ?>"><i class="fa-solid fa-pen"></i> Edit Course</a>
            <?php endif; ?>
        </div>

        <!-- Tabs -->
        <section class="glass mt-6 rounded-3xl" data-tabs data-reveal>
            <div class="flex overflow-x-auto border-b border-white/10 px-2" role="tablist">
                <button type="button" class="tab-btn" role="tab" data-tab="description">Description</button>
                <button type="button" class="tab-btn" role="tab" data-tab="instructor">Instructor</button>
                <button type="button" class="tab-btn" role="tab" data-tab="reviews">Reviews <span class="ml-1 rounded-full bg-white/10 px-2 text-xs"><?= count($reviews) ?></span></button>
            </div>
            <div class="p-6 md:p-8">
                <div data-panel="description">
                    <h2 class="font-display text-xl font-semibold">About this course</h2>
                    <p class="mt-3 whitespace-pre-line leading-relaxed text-slate-300"><?= sh_e($course["description"] ?? "") ?></p>
                    <ul class="mt-6 grid gap-3 text-sm text-slate-300 sm:grid-cols-2">
                        <li><i class="fa-solid fa-circle-check mr-2 text-cyan-300"></i>Learn at your own pace</li>
                        <li><i class="fa-solid fa-circle-check mr-2 text-cyan-300"></i><?= $lessonCount ?> bite-sized lessons</li>
                        <li><i class="fa-solid fa-circle-check mr-2 text-cyan-300"></i>Hands-on practice</li>
                        <li><i class="fa-solid fa-circle-check mr-2 text-cyan-300"></i>Progress tracking</li>
                    </ul>
                </div>
                <div data-panel="instructor" hidden>
                    <div class="flex items-center gap-4">
                        <span class="grid h-16 w-16 place-items-center rounded-full bg-gradient-to-br from-cyan-300 to-violet-400 text-2xl font-bold text-slate-900"><?= sh_e(mb_substr($instructor["name"], 0, 1, "UTF-8")) ?></span>
                        <div><h2 class="font-display text-xl font-semibold"><?= sh_e($instructor["name"]) ?></h2><p class="text-sm text-slate-400"><?= sh_e($instructor["title"]) ?></p></div>
                    </div>
                    <p class="mt-4 leading-relaxed text-slate-300"><?= sh_e($instructor["bio"]) ?></p>
                </div>
                <div data-panel="reviews" hidden>
                    <ul class="space-y-4">
                        <?php foreach ($reviews as [$who, $stars, $text]): ?>
                            <li class="rounded-2xl border border-white/10 bg-white/[.03] p-4">
                                <div class="flex items-center justify-between"><strong><?= sh_e($who) ?></strong>
                                    <span class="text-amber-300" aria-label="<?= (int) $stars ?> out of 5"><?= str_repeat('<i class="fa-solid fa-star"></i>', $stars) . str_repeat('<i class="fa-regular fa-star"></i>', 5 - $stars) ?></span></div>
                                <p class="mt-2 text-sm text-slate-300"><?= sh_e($text) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
    </div>

    <!-- RIGHT: lessons accordion -->
    <aside class="min-w-0" data-reveal data-delay="0.1">
        <div class="glass sticky top-6 rounded-3xl p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Course content</h2>
                <span class="text-xs text-slate-400"><?= count($sections) ?> sections · <?= $lessonCount ?> lessons</span>
            </div>
            <div data-accordion class="space-y-3">
                <?php $first = true; foreach ($sections as $si => [$secTitle, $lessons]): ?>
                    <div class="acc-item overflow-hidden rounded-2xl border border-white/10 bg-white/[.03]">
                        <button type="button" data-acc-trigger aria-expanded="false" class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left font-semibold">
                            <span><span class="mr-2 text-cyan-300"><?= $si + 1 ?>.</span><?= sh_e($secTitle) ?></span>
                            <i class="fa-solid fa-chevron-down acc-chevron text-xs text-slate-400"></i>
                        </button>
                        <div class="acc-panel">
                            <ul class="px-2 pb-3">
                                <?php foreach ($lessons as [$lTitle, $lTime]): ?>
                                    <li class="lesson <?= $first ? "current" : "" ?>" data-lesson="<?= sh_e($lTitle) ?>" tabindex="0">
                                        <i class="fa-regular fa-circle-play text-cyan-300"></i>
                                        <span class="flex-1 text-sm"><?= sh_e($lTitle) ?></span>
                                        <span class="text-xs text-slate-500"><?= sh_e($lTime) ?></span>
                                    </li>
                                    <?php $first = false; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</div>

<?php
sh_app_close();
sh_scripts();
