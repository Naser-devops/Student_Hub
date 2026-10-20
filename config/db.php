<?php
/**
 * Database connection ($pdo).
 *
 * Settings are read in this order:
 *   1) Environment variables: DB_HOST, DB_NAME, DB_USER, DB_PASS   (Docker / hosting panels)
 *   2) config/config.local.php  (created automatically by setup.php, never committed to Git)
 *   3) XAMPP defaults: localhost / studenthub_db / root / (empty password)
 */

$sh_db = [
    "host" => "localhost",
    "name" => "studenthub_db",
    "user" => "root",
    "pass" => "",
    "port" => 3306,
];

$sh_local = __DIR__ . "/config.local.php";
if (is_file($sh_local)) {
    $sh_cfg = require $sh_local;
    if (is_array($sh_cfg)) {
        $sh_db = array_merge($sh_db, $sh_cfg);
    }
}

foreach (["host" => "DB_HOST", "name" => "DB_NAME", "user" => "DB_USER", "pass" => "DB_PASS", "port" => "DB_PORT"] as $key => $env) {
    $value = getenv($env);
    if ($value !== false && ($value !== "" || $key === "pass")) {
        $sh_db[$key] = $value;
    }
}

try {
    $pdo = new PDO(
        "mysql:host={$sh_db["host"]};port={$sh_db["port"]};dbname={$sh_db["name"]};charset=utf8mb4",
        $sh_db["user"],
        $sh_db["pass"],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("StudentHub DB connection failed: " . $e->getMessage());

    if (defined("SH_DB_OPTIONAL")) {
        throw $e; // the caller (e.g. landing page) handles it
    }

    // Friendly message instead of a raw PHP stack trace
    $rootFs = str_replace("\\", "/", dirname(__DIR__));
    $scriptFs = str_replace("\\", "/", (string) realpath($_SERVER["SCRIPT_FILENAME"] ?? ""));
    $rel = ltrim(substr($scriptFs, strlen($rootFs)), "/");
    $prefix = str_repeat("../", substr_count($rel, "/"));
    $remote = $_SERVER["REMOTE_ADDR"] ?? "";
    $needsSetup = !is_file(__DIR__ . "/installed.lock")
        && in_array($remote, ["127.0.0.1", "::1"], true)
        && getenv("DB_HOST") === false;

    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>StudentHub — Database error</title></head>'
        . '<body style="font-family:system-ui,sans-serif;background:#05070f;color:#edf3ff;display:grid;place-items:center;min-height:100vh;margin:0">'
        . '<div style="max-width:520px;padding:32px;border:1px solid #26324b;border-radius:18px;background:#0d1424">'
        . '<h1 style="margin-top:0">Database connection failed</h1>'
        . '<p>Make sure MySQL/MariaDB is running and the credentials are correct.</p>'
        . ($needsSetup ? '<p><a style="color:#59dcff" href="' . htmlspecialchars($prefix . "setup.php", ENT_QUOTES, "UTF-8") . '">Run the setup wizard →</a></p>' : "")
        . '</div></body></html>';
    exit;
}
