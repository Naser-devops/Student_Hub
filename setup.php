<?php
/**
 * StudentHub — one-file setup wizard.
 *
 *   Browser :  http://localhost/StudentHub/setup.php          (XAMPP)
 *              (local browser only; Docker/remote installation must use CLI)
 *   Terminal:  php setup.php            (interactive)
 *              Avoid passing passwords via CLI flags because shell history can retain them.
 *
 * What it does: checks requirements → creates the database + tables (database/schema.sql)
 * → optionally adds demo courses → creates the first admin → writes config/config.local.php
 * → creates config/installed.lock so the wizard cannot be run again by strangers.
 */

declare(strict_types=1);

$root      = __DIR__;
$lockFile  = $root . "/config/installed.lock";
$localFile = $root . "/config/config.local.php";
$isCli     = PHP_SAPI === "cli";

/* ---------- helpers ---------- */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

function requirements(string $root): array
{
    return [
        ["PHP 8.0 or newer (current: " . PHP_VERSION . ")", version_compare(PHP_VERSION, "8.0.0", ">=")],
        ["PDO MySQL extension (pdo_mysql)", extension_loaded("pdo_mysql")],
        ["mbstring extension", extension_loaded("mbstring")],
        ["config/ folder is writable", is_writable($root . "/config")],
        ["database/schema.sql exists", is_file($root . "/database/schema.sql")],
    ];
}

function sql_statements(string $file): array
{
    $sql = (string) file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', "", $sql);
    return array_values(array_filter(array_map("trim", explode(";", (string) $sql)), fn($s) => $s !== ""));
}

/** Returns [ok(bool), messages(array)] */
function install(string $root, array $in): array
{
    $log = [];
    $host = trim($in["host"] ?? "localhost");
    $port = (int) ($in["port"] ?? 3306) ?: 3306;
    $db   = trim($in["db"] ?? "studenthub_db");
    $user = (string) ($in["user"] ?? "root");
    $pass = (string) ($in["pass"] ?? "");
    $aName  = trim($in["admin_name"] ?? "");
    $aEmail = trim($in["admin_email"] ?? "");
    $aPass  = (string) ($in["admin_pass"] ?? "");
    $sample = !empty($in["sample"]);

    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $db)) { return [false, ["Database name may contain only letters, digits and underscore."]]; }
    if ($aEmail !== "" && !filter_var($aEmail, FILTER_VALIDATE_EMAIL)) { return [false, ["Admin email is not valid."]]; }
    if ($aEmail !== "" && (strlen($aPass) < 8 || $aName === "")) { return [false, ["Admin name is required and the password must be at least 8 characters."]]; }

    try {
        $server = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        return [false, ["Cannot connect to MySQL: " . $e->getMessage(), "Is MySQL running (XAMPP → Start MySQL)? Are the user/password correct?"]];
    }
    $log[] = "Connected to MySQL server.";

    try {
        $server->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $log[] = "Database `$db` is ready.";

        foreach (sql_statements($root . "/database/schema.sql") as $stmt) { $pdo->exec($stmt); }
        $log[] = "Tables created (users, courses, enrollments).";

        if ($sample && is_file($root . "/database/sample-data.sql")) {
            if ((int) $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn() === 0) {
                foreach (sql_statements($root . "/database/sample-data.sql") as $stmt) { $pdo->exec($stmt); }
                $log[] = "Demo courses added.";
            } else {
                $log[] = "Courses table is not empty — demo courses skipped.";
            }
        }

        if ($aEmail !== "") {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$aEmail]);
            $existing = $stmt->fetchColumn();
            // Never change the password/role of an existing account from the installer.
            // After the first administrator exists, the installer cannot add more admins.
            $adminExists = (bool) $pdo->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
            if ($existing) {
                return [false, array_merge($log, ["That email already belongs to an account. No changes made to its password or role."])];
            }
            if ($adminExists) {
                return [false, array_merge($log, ["An admin already exists. Use the authenticated user-management page for role changes."])];
            }
            $pdo->prepare("INSERT INTO users (full_name, email, mobile, password_hash, role) VALUES (?, ?, '', ?, 'admin')")
                ->execute([$aName, $aEmail, password_hash($aPass, PASSWORD_DEFAULT)]);
            $log[] = "Admin account created: $aEmail";
        }
    } catch (PDOException $e) {
        return [false, array_merge($log, ["Database error: " . $e->getMessage()])];
    }

    // On Docker, database credentials already come from container environment.
    // Do not duplicate secrets into a web-root PHP file.
    if (getenv("DB_HOST") !== false && getenv("DB_USER") !== false && getenv("DB_NAME") !== false) {
        $log[] = "Database environment variables detected; config.local.php was not generated.";
    } else {
    $config = "<?php\n// Generated by setup.php — do NOT commit this file (it is in .gitignore).\nreturn " . var_export([
        "host" => $host, "port" => $port, "name" => $db, "user" => $user, "pass" => $pass,
    ], true) . ";\n";
    if (file_put_contents($root . "/config/config.local.php", $config, LOCK_EX) === false) {
        return [false, array_merge($log, ["Could not write config/config.local.php (check folder permissions)."])];
    }
    @chmod($root . "/config/config.local.php", 0640);
    // On Linux containers, root writes the CLI file; grant read access to Apache's group.
    if (PHP_OS_FAMILY !== "Windows") { @chgrp($root . "/config/config.local.php", "www-data"); }
    $log[] = "Saved config/config.local.php";
    }

    if (file_put_contents($root . "/config/installed.lock", "installed " . date("c") . "\n", LOCK_EX) === false) {
        return [false, array_merge($log, ["Could not create setup lock; check directory permissions."])];
    }
    $log[] = "Setup locked (config/installed.lock).";
    return [true, $log];
}

/* ---------- CLI mode ---------- */
if ($isCli) {
    if (is_file($lockFile) && !in_array("--force", $argv, true)) {
        fwrite(STDERR, "Already installed. Delete config/installed.lock or pass --force to run again.\n");
        exit(1);
    }
    $opts = getopt("", ["host::", "port::", "db::", "user::", "pass::", "admin-name::", "admin-email::", "admin-pass::", "sample", "force"]);
    $ask = function (string $label, string $default = "") use (&$opts): string {
        echo $label . ($default !== "" ? " [$default]" : "") . ": ";
        $line = fgets(STDIN);
        $line = $line === false ? "" : trim($line);
        return $line === "" ? $default : $line;
    };
    $askSecret = static function (string $label): string {
        echo $label . ": ";
        $hide = PHP_OS_FAMILY !== "Windows" && function_exists("posix_isatty")
            && posix_isatty(STDIN);
        if ($hide) { @system("stty -echo"); }
        try { $line = fgets(STDIN); }
        finally { if ($hide) { @system("stty echo"); echo "\n"; } }
        return $line === false ? "" : trim($line);
    };
    $interactive = !isset($opts["db"]);
    $in = [
        "host" => $opts["host"] ?? ($interactive ? $ask("MySQL host", getenv("DB_HOST") ?: "localhost") : "localhost"),
        "port" => $opts["port"] ?? 3306,
        "db"   => $opts["db"]   ?? ($interactive ? $ask("Database name", getenv("DB_NAME") ?: "studenthub_db") : "studenthub_db"),
        "user" => $opts["user"] ?? ($interactive ? $ask("MySQL user", getenv("DB_USER") ?: "root") : "root"),
        "pass" => $opts["pass"] ?? (getenv("DB_PASS") !== false ? (string) getenv("DB_PASS") : ($interactive ? $askSecret("MySQL password (empty for XAMPP)") : "")),
        "admin_name"  => $opts["admin-name"]  ?? ($interactive ? $ask("Admin full name", "Admin") : ""),
        "admin_email" => $opts["admin-email"] ?? ($interactive ? $ask("Admin email", "admin@example.com") : ""),
        "admin_pass"  => $opts["admin-pass"]  ?? ($interactive ? $askSecret("Admin password (min 8 chars)") : ""),
        "sample" => isset($opts["sample"]) || ($interactive && strtolower($ask("Add demo courses? (y/n)", "y")) === "y"),
    ];
    foreach (requirements($root) as [$label, $ok]) { echo ($ok ? "[ OK ] " : "[FAIL] ") . $label . "\n"; if (!$ok) { exit(1); } }
    [$ok, $messages] = install($root, $in);
    foreach ($messages as $m) { echo ($ok ? "✔ " : "✖ ") . $m . "\n"; }
    echo $ok ? "\nDone. Start the server:  php -S localhost:8000   →   http://localhost:8000\n" : "\nSetup failed.\n";
    exit($ok ? 0 : 1);
}

/* ---------- Web mode ---------- */
// Only a true local browser can use the XAMPP setup wizard.
// Docker also denies setup.php at Apache config level.
$remote = $_SERVER["REMOTE_ADDR"] ?? "";
if (!in_array($remote, ["127.0.0.1", "::1"], true) || getenv("DB_HOST") !== false) {
    http_response_code(403);
    exit("Setup is only available locally. On Docker use: docker compose exec web php setup.php");
}
$locked = is_file($lockFile);
$result = null;
$reqs   = requirements($root);
$reqOk  = !in_array(false, array_column($reqs, 1), true);

if (!$locked && $_SERVER["REQUEST_METHOD"] === "POST" && $reqOk) {
    session_start();
    $token = $_POST["token"] ?? "";
    if (!isset($_SESSION["setup_token"]) || !is_string($token) || !hash_equals($_SESSION["setup_token"], $token)) {
        http_response_code(403);
        exit("Invalid form token. Reload the page and try again.");
    }
    $result = install($root, $_POST);
    $locked = $result[0];
} else {
    session_start();
    $_SESSION["setup_token"] = bin2hex(random_bytes(16));
}
$val = fn(string $k, string $d = "") => h((string) ($_POST[$k] ?? $d));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>StudentHub — Setup</title>
<style>
 body{margin:0;font-family:system-ui,Segoe UI,sans-serif;background:#05070f;color:#edf3ff;display:grid;place-items:center;min-height:100vh;padding:24px}
 .card{width:100%;max-width:560px;background:#0d1424;border:1px solid #26324b;border-radius:20px;padding:32px}
 h1{margin:0 0 4px}.muted{color:#93a3bd;margin:0 0 20px}
 label{display:block;margin:14px 0 6px;font-size:14px;color:#c3cfe3}
 input[type=text],input[type=password],input[type=email],input[type=number]{width:100%;box-sizing:border-box;padding:12px 14px;border-radius:12px;border:1px solid #2c3a57;background:#111a2d;color:#fff}
 .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
 button,.btn{display:inline-block;margin-top:22px;padding:13px 22px;border:0;border-radius:12px;background:linear-gradient(115deg,#7966f5,#4f7cff);color:#fff;font-weight:700;cursor:pointer;text-decoration:none}
 .ok{color:#8ff0c2}.bad{color:#ff9aab}ul{padding-left:0;list-style:none}li{margin:6px 0}
 .box{border:1px solid #26324b;border-radius:12px;padding:12px 16px;margin:16px 0;background:#0a1020}
 h3{margin:22px 0 0;font-size:15px;color:#b9afff}
</style>
</head>
<body>
<div class="card">
<h1>StudentHub Setup</h1>
<p class="muted">Creates the database, tables and your first admin account.</p>

<ul class="box">
<?php foreach ($reqs as [$label, $ok]): ?>
  <li class="<?= $ok ? "ok" : "bad" ?>"><?= $ok ? "✔" : "✖" ?> <?= h($label) ?></li>
<?php endforeach; ?>
</ul>

<?php if ($result): ?>
  <div class="box">
    <?php foreach ($result[1] as $m): ?><div class="<?= $result[0] ? "ok" : "bad" ?>"><?= $result[0] ? "✔" : "✖" ?> <?= h($m) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($locked): ?>
  <?php if ($result && $result[0]): ?>
    <p class="ok"><strong>Setup completed 🎉</strong></p>
    <p>For safety, <strong>delete <code>setup.php</code></strong> from the server when you are done.</p>
    <a class="btn" href="index.php">Open StudentHub →</a>
    <a class="btn" style="background:#1b2740" href="pages/login.php">Sign in</a>
  <?php else: ?>
    <p class="bad"><strong>Already installed.</strong> To run the wizard again delete <code>config/installed.lock</code>.</p>
    <a class="btn" href="index.php">Open StudentHub →</a>
  <?php endif; ?>
<?php elseif (!$reqOk): ?>
  <p class="bad">Fix the failed requirements above, then reload this page.</p>
<?php else: ?>
<form method="post" autocomplete="off">
  <input type="hidden" name="token" value="<?= h($_SESSION["setup_token"] ?? "") ?>">
  <h3>1 · Database</h3>
  <div class="row">
    <div><label for="host">MySQL host</label><input id="host" name="host" type="text" value="<?= $val("host", getenv("DB_HOST") ?: "localhost") ?>" required></div>
    <div><label for="db">Database name</label><input id="db" name="db" type="text" value="<?= $val("db", getenv("DB_NAME") ?: "studenthub_db") ?>" required></div>
  </div>
  <div class="row">
    <div><label for="user">MySQL user</label><input id="user" name="user" type="text" value="<?= $val("user", getenv("DB_USER") ?: "root") ?>" required></div>
    <div><label for="pass">MySQL password</label><input id="pass" name="pass" type="password" value="" placeholder="empty on XAMPP"></div>
  </div>
  <input type="hidden" name="port" value="<?= h((string) (getenv("DB_PORT") ?: 3306)) ?>">

  <h3>2 · First admin account</h3>
  <label for="admin_name">Full name</label><input id="admin_name" name="admin_name" type="text" value="<?= $val("admin_name", "Admin") ?>" required>
  <div class="row">
    <div><label for="admin_email">Email</label><input id="admin_email" name="admin_email" type="email" value="<?= $val("admin_email") ?>" required></div>
    <div><label for="admin_pass">Password (min 8)</label><input id="admin_pass" name="admin_pass" type="password" minlength="8" required></div>
  </div>

  <label style="margin-top:18px"><input type="checkbox" name="sample" value="1" checked> Add demo courses</label>
  <button type="submit">Install StudentHub</button>
</form>
<?php endif; ?>
</div>
</body>
</html>
