<?php

session_start();

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Connect to database
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

// Get current student ID
$userId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"] ?? "Student";

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$role = $stmt->fetchColumn();
if ($role === false) {
    $_SESSION = [];
    session_regenerate_id(true);
    header("Location: login.php");
    exit;
}

// Get student's enrolled courses (courses.id added so cards can link to the course page)
$stmt = $pdo->prepare(
    "SELECT courses.id, courses.course_name, courses.description
     FROM enrollments
     INNER JOIN courses
     ON enrollments.course_id = courses.id
     WHERE enrollments.user_id = ?"
);

$stmt->execute([$userId]);

$myCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

sh_head("My Courses");
sh_app_open("my", $fullName, $role);
?>

<?php sh_flash(); ?>

<header class="flex flex-wrap items-end justify-between gap-4" data-reveal>
    <div>
        <span class="chip">Library</span>
        <h1 class="font-display mt-3 text-3xl font-bold tracking-tight md:text-4xl">My Courses</h1>
        <p class="mt-1 text-slate-400"><?= count($myCourses) ?> course<?= count($myCourses) === 1 ? "" : "s" ?> in progress</p>
    </div>
    <a class="btn-primary" href="courses.php"><i class="fa-solid fa-compass"></i> Explore Courses</a>
</header>

<?php if (empty($myCourses)): ?>

    <div class="glass mt-10 rounded-3xl p-12 text-center" data-reveal>
        <i class="fa-solid fa-seedling text-4xl text-cyan-300"></i>
        <p class="mt-4 text-lg text-slate-300">You haven't enrolled in any courses yet.</p>
        <a class="btn-primary mt-6" href="courses.php">Browse the catalog</a>
    </div>

<?php else: ?>

    <div class="mt-8 grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
        <?php foreach ($myCourses as $i => $course):
            $cover = sh_cover((int) $course["id"]);
            $p = 20 + (abs(crc32((string) $course["id"])) % 71); // placeholder progress ?>
            <article class="glass glow-card flex flex-col" data-reveal data-delay="<?= ($i % 3) * 0.08 ?>">
                <div class="relative grid h-36 place-items-center" style="<?= $cover["style"] ?>">
                    <i class="fa-solid <?= $cover["icon"] ?> text-5xl text-white/90"></i>
                </div>
                <div class="flex flex-1 flex-col p-6">
                    <h2 class="font-display text-xl font-semibold"><?= htmlspecialchars($course["course_name"]) ?></h2>
                    <p class="mt-2 line-clamp-2 flex-1 text-sm text-slate-400"><?= htmlspecialchars($course["description"] ?? "") ?></p>
                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400"><span>Progress</span><span><?= $p ?>%</span></div>
                    <div class="bar mt-1.5"><i data-width="<?= $p ?>"></i></div>
                    <a class="btn-ghost mt-5 !py-2.5 text-sm" href="course-view.php?id=<?= (int) $course["id"] ?>"><i class="fa-solid fa-play"></i> Continue</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php
sh_app_close();
sh_scripts();
