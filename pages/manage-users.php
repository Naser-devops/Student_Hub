<?php

session_start();

// 1. Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 2. Connect to database
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/layout.php";

// 3. Check admin permission
$stmt = $pdo->prepare(
    "SELECT role FROM users WHERE id = ?"
);

$stmt->execute([$_SESSION["user_id"]]);

$role = $stmt->fetchColumn();

if ($role !== "admin") {
    http_response_code(403);
    exit("Access Denied");
}

// 4. Generate CSRF token
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// 5. Get all users
$stmt = $pdo->prepare(
    "SELECT id, full_name, email, role
     FROM users
     ORDER BY id ASC"
);

$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

sh_head("Manage Users");
sh_app_open("admin", $_SESSION["full_name"] ?? "Admin", "admin");
?>

<header data-reveal>
    <a class="text-sm text-slate-400 hover:text-white" href="admin.php"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Admin</a>
    <h1 class="font-display mt-3 text-3xl font-bold tracking-tight md:text-4xl">Manage <span class="gradient-text">Users</span></h1>
</header>

<div class="mt-6"><?php sh_flash(); ?></div>

<div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
    <?php foreach ($users as $i => $user): ?>

        <article class="user-card glass glow-card p-6" data-reveal data-delay="<?= ($i % 3) * 0.06 ?>">
            <div class="flex items-center gap-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-gradient-to-br from-cyan-300 to-violet-400 font-bold text-slate-900"><?= sh_e(mb_strtoupper(mb_substr($user["full_name"], 0, 1, "UTF-8"), "UTF-8")) ?></span>
                <div class="min-w-0">
                    <h3 class="truncate font-semibold"><?= htmlspecialchars($user["full_name"], ENT_QUOTES, "UTF-8") ?></h3>
                    <p class="truncate text-sm text-slate-400"><?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?></p>
                </div>
                <span class="ml-auto rounded-full px-3 py-1 text-xs font-bold uppercase <?= $user["role"] === "admin" ? "bg-violet-500/25 text-violet-200" : "bg-cyan-400/15 text-cyan-200" ?>">
                    <?= htmlspecialchars($user["role"], ENT_QUOTES, "UTF-8") ?>
                </span>
            </div>

            <?php if ((int) $user["id"] === (int) $_SESSION["user_id"]): ?>

                <p class="mt-4 text-sm text-slate-400"><i class="fa-solid fa-lock mr-1"></i>You cannot change your own role.</p>

            <?php else: ?>

                <!-- Same fields as before: user_id, role, csrf_token → update-user-role.php -->
                <form action="update-user-role.php" method="POST" class="mt-4 flex flex-wrap items-center gap-3">
                    <input type="hidden" name="user_id" value="<?= (int) $user["id"] ?>">

                    <div class="fl min-w-[8rem] flex-1">
                        <select name="role" id="role-<?= (int) $user["id"] ?>">
                            <option value="student" <?= $user["role"] === "student" ? "selected" : "" ?>>Student</option>
                            <option value="admin" <?= $user["role"] === "admin" ? "selected" : "" ?>>Admin</option>
                        </select>
                        <label for="role-<?= (int) $user["id"] ?>">Role</label>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">

                    <button type="submit" class="btn-primary !py-3 text-sm">Update Role</button>
                </form>

            <?php endif; ?>
        </article>

    <?php endforeach; ?>
</div>

<?php
sh_app_close();
sh_scripts();
