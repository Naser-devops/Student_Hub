<?php

session_start();
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
// Check if student is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Connect to database
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

// Get logged-in student ID
$userId = $_SESSION["user_id"];

// Get student information
$stmt = $pdo->prepare(
    "SELECT full_name, email, mobile, date_of_birth, role
     FROM users
     WHERE id = ?"
);

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit("User not found");
}

$initial = mb_strtoupper(mb_substr($user["full_name"], 0, 1, "UTF-8"), "UTF-8");

sh_head("My Profile");
sh_app_open("profile", $user["full_name"], $user["role"] ?? "student");
?>

<header data-reveal>
    <span class="chip">Account</span>
    <h1 class="font-display mt-3 text-3xl font-bold tracking-tight md:text-4xl">My Profile</h1>
</header>

<div class="mt-6"><?php sh_flash(); ?></div>

<div class="mt-4 grid gap-6 lg:grid-cols-5">
    <section class="profile-card glass glow-card p-8 lg:col-span-2" data-reveal>
        <span class="grid h-20 w-20 place-items-center rounded-full bg-gradient-to-br from-cyan-300 to-violet-400 font-display text-3xl font-bold text-slate-900"><?= sh_e($initial) ?></span>
        <h2 class="font-display mt-5 text-2xl font-semibold"><?= htmlspecialchars($user["full_name"]) ?></h2>
        <dl class="mt-6 space-y-4 text-sm">
            <div class="flex items-center gap-3"><i class="fa-solid fa-envelope w-5 text-cyan-300"></i><div><dt class="text-xs text-slate-500">Email</dt><dd><?= htmlspecialchars($user["email"]) ?></dd></div></div>
            <div class="flex items-center gap-3"><i class="fa-solid fa-phone w-5 text-cyan-300"></i><div><dt class="text-xs text-slate-500">Mobile</dt><dd><?= htmlspecialchars($user["mobile"] ?? "") ?></dd></div></div>
            <div class="flex items-center gap-3"><i class="fa-solid fa-cake-candles w-5 text-cyan-300"></i><div><dt class="text-xs text-slate-500">Date of Birth</dt><dd><?= htmlspecialchars($user["date_of_birth"] ?? "") ?></dd></div></div>
        </dl>
    </section>

    <section class="glass rounded-3xl p-8 lg:col-span-3" data-reveal data-delay="0.1">
        <h2 class="font-display text-xl font-semibold">Edit Profile</h2>
        <!-- Same fields as before: full_name, mobile, csrf_token → update-profile.php -->
        <form action="update-profile.php" method="POST" class="mt-6 space-y-4">
            <div class="fl">
                <input type="text" id="full_name" name="full_name" placeholder=" " required
                    value="<?= htmlspecialchars($user["full_name"], ENT_QUOTES, 'UTF-8') ?>">
                <label for="full_name">Full Name</label>
            </div>
            <div class="fl">
                <input type="text" id="mobile" name="mobile" placeholder=" "
                    value="<?= htmlspecialchars($user["mobile"] ?? "", ENT_QUOTES, 'UTF-8') ?>">
                <label for="mobile">Mobile</label>
            </div>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, 'UTF-8') ?>">
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Save Changes <i class="fa-solid fa-check"></i></button>
                <a href="dashboard.php" class="btn-ghost">Back to Dashboard</a>
            </div>
        </form>
    </section>
</div>

<?php
sh_app_close();
sh_scripts();
