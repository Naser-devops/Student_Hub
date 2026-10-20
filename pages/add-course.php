<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

// Check admin permission
$stmt = $pdo->prepare(
    "SELECT role FROM users WHERE id = ?"
);

$stmt->execute([$_SESSION["user_id"]]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user["role"] !== "admin") {
    http_response_code(403);
    exit("Access Denied");
}

// Generate CSRF token
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

sh_head("Add Course");
sh_app_open("admin", $_SESSION["full_name"] ?? "Admin", "admin");
?>

<header data-reveal>
    <a class="text-sm text-slate-400 hover:text-white" href="admin.php"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Admin</a>
    <h1 class="font-display mt-3 text-3xl font-bold tracking-tight md:text-4xl">Add New <span class="gradient-text">Course</span></h1>
</header>

<div class="mt-6"><?php sh_flash(); ?></div>

<!-- Same fields as before: course_name, description, csrf_token → store-course.php -->
<form action="store-course.php" method="POST" class="glass mt-4 max-w-2xl space-y-4 rounded-3xl p-8" data-reveal>
    <div class="fl">
        <input type="text" id="course_name" name="course_name" maxlength="100" placeholder=" " required>
        <label for="course_name">Course Name</label>
    </div>
    <div class="fl">
        <textarea id="description" name="description" placeholder=" " required></textarea>
        <label for="description">Description</label>
    </div>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, 'UTF-8') ?>">
    <div class="flex flex-wrap gap-3">
        <button type="submit" class="btn-primary">Add Course <i class="fa-solid fa-plus"></i></button>
        <a class="btn-ghost" href="admin.php">Cancel</a>
    </div>
</form>

<?php
sh_app_close();
sh_scripts();
