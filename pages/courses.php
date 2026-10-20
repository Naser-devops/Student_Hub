<?php

// 1. Start Session
session_start();

// 2. Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 3. Connect to database
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

// 4. CSRF Protection
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// 5. Get current user ID
$userId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"] ?? "Student";

// 6. Get current user role
$stmt = $pdo->prepare(
    "SELECT role FROM users WHERE id = ?"
);

$stmt->execute([$userId]);

$role = $stmt->fetchColumn();

// Check if user still exists
if ($role === false) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

// 7. Get all courses
$stmt = $pdo->prepare(
    "SELECT id, course_name, description, created_at
     FROM courses
     ORDER BY created_at DESC, id DESC"
);

$stmt->execute();

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 8. Get enrolled courses (students only)
$enrolledCourses = [];

if ($role === "student") {

    $stmt = $pdo->prepare(
        "SELECT course_id
         FROM enrollments
         WHERE user_id = ?"
    );

    $stmt->execute([$userId]);

    $enrolledCourses = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

sh_head("Courses");
sh_app_open("courses", $fullName, $role);
?>

<header class="flex flex-wrap items-end justify-between gap-4" data-reveal>
    <div>
        <span class="chip">Catalog</span>
        <h1 class="font-display mt-3 text-3xl font-bold tracking-tight md:text-4xl">Available <span class="gradient-text">Courses</span></h1>
        <p class="mt-1 text-slate-400"><?= count($courses) ?> course<?= count($courses) === 1 ? "" : "s" ?> ready for you</p>
    </div>
    <div class="flex flex-wrap items-center gap-3 page-navigation">
        <a class="btn-ghost !py-2.5 text-sm" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        <?php if ($role === "admin"): ?>
            <a class="btn-primary !py-2.5 text-sm" href="admin.php"><i class="fa-solid fa-shield-halved"></i> Admin Dashboard</a>
        <?php endif; ?>
    </div>
</header>

<!-- Success Message -->
<div class="mt-6"><?php sh_flash(); ?></div>

<div class="fl mt-4 max-w-md" data-reveal>
    <input id="course-search" type="search" placeholder=" " data-filter-input autocomplete="off">
    <label for="course-search"><i class="fa-solid fa-magnifying-glass mr-2"></i>Search courses</label>
</div>

<!-- Courses Container -->
<div class="courses-container mt-8 grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">

    <?php if (empty($courses)): ?>

        <p class="glass col-span-full rounded-2xl p-10 text-center text-slate-400">No courses available yet.</p>

    <?php else: ?>

        <?php foreach ($courses as $i => $course):
            $cover = sh_cover((int) $course["id"]);
            $isEnrolled = in_array($course["id"], $enrolledCourses); ?>

            <article class="course-card glass glow-card flex flex-col" data-reveal data-delay="<?= ($i % 3) * 0.08 ?>"
                data-filter-item="<?= sh_e(mb_strtolower($course["course_name"] . " " . ($course["description"] ?? ""), "UTF-8")) ?>">

                <a href="course-view.php?id=<?= (int) $course["id"] ?>" class="relative grid h-40 place-items-center" style="<?= $cover["style"] ?>" aria-label="Open course">
                    <i class="fa-solid <?= $cover["icon"] ?> text-5xl text-white/90 drop-shadow-lg"></i>
                    <?php if ($isEnrolled): ?><span class="absolute right-4 top-4 rounded-full bg-black/35 px-3 py-1 text-xs font-semibold backdrop-blur"><i class="fa-solid fa-check mr-1"></i>Enrolled</span><?php endif; ?>
                </a>

                <div class="flex flex-1 flex-col p-6">
                    <h2 class="font-display text-xl font-semibold">
                        <?= htmlspecialchars($course["course_name"], ENT_QUOTES, "UTF-8") ?>
                    </h2>

                    <p class="mt-2 flex-1 text-sm text-slate-400">
                        <?= htmlspecialchars($course["description"] ?? "", ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <p class="mt-3 text-xs text-slate-500">
                        <i class="fa-regular fa-calendar mr-1"></i>Created at:
                        <?= htmlspecialchars($course["created_at"], ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <a class="btn-ghost !py-2.5 text-sm" href="course-view.php?id=<?= (int) $course["id"] ?>">Details</a>

                        <!-- Student Enrollment (same form fields as before: course_id, csrf_token) -->
                        <?php if ($role === "student"): ?>

                            <?php if ($isEnrolled): ?>

                                <button type="button" class="btn-ghost !py-2.5 text-sm" disabled>
                                    <i class="fa-solid fa-circle-check text-emerald-300"></i> Already Enrolled
                                </button>

                            <?php else: ?>

                                <form action="enroll.php" method="POST">
                                    <input type="hidden" name="course_id" value="<?= (int) $course["id"] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
                                    <button type="submit" class="btn-primary !py-2.5 text-sm">Enroll Now <i class="fa-solid fa-bolt"></i></button>
                                </form>

                            <?php endif; ?>

                        <?php endif; ?>

                        <!-- Admin Information -->
                        <?php if ($role === "admin"): ?>
                            <span class="text-xs text-slate-400">Course Management Available in Admin Dashboard</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

        <?php endforeach; ?>

    <?php endif; ?>
</div>
<p class="glass mt-8 rounded-2xl p-8 text-center text-slate-400" data-filter-empty hidden>No course matches your search.</p>

<?php
sh_app_close();
sh_scripts();
