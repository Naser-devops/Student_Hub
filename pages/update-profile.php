<?php

session_start();

// 1. Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 2. Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit;
}

// 3. Check CSRF token
$sessionToken = $_SESSION["csrf_token"] ?? null;
$postToken = $_POST["csrf_token"] ?? null;

if (
    !is_string($sessionToken) ||
    !is_string($postToken) ||
    !hash_equals($sessionToken, $postToken)
) {
    http_response_code(403);
    exit("Invalid CSRF Token");
}

// 4. Connect to database
require_once __DIR__ . "/../config/db.php";

// 5. Get form values
$userId = $_SESSION["user_id"];
$fullName = $_POST["full_name"] ?? "";
$mobile = $_POST["mobile"] ?? "";

// 6. Validate input (errors are shown on the profile page)
if (!is_string($fullName) || !is_string($mobile)) {
    $_SESSION["error_message"] = "Invalid input";
    header("Location: profile.php");
    exit;
}

$fullName = trim($fullName);
$mobile = trim($mobile);

if ($fullName === "" || mb_strlen($fullName) > 100) {
    $_SESSION["error_message"] = "Full name is required (max 100 characters)";
    header("Location: profile.php");
    exit;
}

// Same rule as the sign-up form: exactly 14 digits (empty is allowed here)
if ($mobile !== "" && !preg_match('/^[0-9]{14}$/', $mobile)) {
    $_SESSION["error_message"] = "Mobile number must contain exactly 14 digits";
    header("Location: profile.php");
    exit;
}

// 7. Update student information
$stmt = $pdo->prepare(
    "UPDATE users
     SET full_name = ?, mobile = ?
     WHERE id = ?"
);

$stmt->execute([$fullName, $mobile, $userId]);
// MySQL can report 0 changed rows even when values are identical; verify existence.
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$userId]);
if (!$stmt->fetchColumn()) {
    $_SESSION = [];
    session_regenerate_id(true);
    header("Location: login.php");
    exit;
}

// 8. Update session name
$_SESSION["full_name"] = $fullName;

// 9. Return to profile
$_SESSION["success_message"] = "Profile updated successfully!";

header("Location: profile.php");
exit;
