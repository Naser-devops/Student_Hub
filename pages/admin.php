<?php

session_start();

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Connect to database
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

// Get user role from database
$stmt = $pdo->prepare(
    "SELECT role FROM users WHERE id = ?"
);

$stmt->execute([$_SESSION["user_id"]]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Allow admin only
if (!$user || $user["role"] !== "admin") {
    http_response_code(403);
    exit("Access Denied: Admin Only");
}

// (courses.id added to the SELECT — the original omitted it, so the edit link and delete form had no id)
$stmt = $pdo->prepare(
    "SELECT id, course_name, description FROM courses ORDER BY id DESC"
);

$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fullName = $_SESSION["full_name"] ?? "Admin";

sh_head("Admin");
sh_app_open("admin", $fullName, "admin");
?>

<header class="flex flex-wrap items-end justify-between gap-4" data-reveal>
    <div>
        <span class="chip">Admin</span>
        <h1 class="font-display mt-3 text-3xl font-bold tracking-tight md:text-4xl">Welcome to <span class="gradient-text">Admin Dashboard</span></h1>
        <p class="mt-1 text-slate-400">You can manage courses here.</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a class="btn-ghost" href="manage-users.php"><i class="fa-solid fa-users-gear"></i> Manage Users</a>
        <a class="btn-primary" href="add-course.php"><i class="fa-solid fa-plus"></i> Add New Course</a>
    </div>
</header>

<div class="mt-6"><?php sh_flash(); ?></div>

<h2 class="font-display mt-4 text-xl font-semibold" data-reveal>Manage Courses <span class="ml-2 rounded-full bg-white/10 px-3 py-0.5 text-sm"><?= count($courses) ?></span></h2>

<?php if (empty($courses)): ?>

    <p class="glass mt-6 rounded-2xl p-10 text-center text-slate-400">No courses available.</p>

<?php else: ?>

    <div class="mt-5 grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
        <?php foreach ($courses as $i => $course):
            $cover = sh_cover((int) $course["id"]); ?>
            <article class="course-card glass glow-card flex flex-col p-6" data-reveal data-delay="<?= ($i % 3) * 0.08 ?>">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl text-white" style="<?= $cover["style"] ?>"><i class="fa-solid <?= $cover["icon"] ?>"></i></span>
                    <div class="min-w-0">
                        <h3 class="font-display text-lg font-semibold"><?= htmlspecialchars($course["course_name"]) ?></h3>
                        <p class="mt-1 line-clamp-3 text-sm text-slate-400"><?= htmlspecialchars($course["description"] ?? "") ?></p>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-white/10 pt-4">
                    <a class="btn-ghost !py-2 text-sm" href="edit-course.php?id=<?= (int) $course["id"] ?>"><i class="fa-solid fa-pen"></i> Edit Course</a>

                    <form action="delete-course.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this course?');">
                        <input type="hidden" name="course_id" value="<?= (int) $course["id"] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn-danger text-sm"><i class="fa-solid fa-trash"></i> Delete Course</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php
sh_app_close();
sh_scripts();
