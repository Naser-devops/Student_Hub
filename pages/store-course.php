<?php
session_start();
// 1. Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 2. Allow POST only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.php");
    exit;
}

// 3. Connect to database
require_once __DIR__ . "/../config/db.php";




$stmt=$pdo->prepare(
    "SELECT role FROM users WHERE id=?"
);

$stmt->execute([$_SESSION["user_id"]]);

$user=$stmt->fetch(PDO::FETCH_ASSOC);



if (!$user || $user["role"] !== "admin") {
    http_response_code(403);
    exit("Access Denied");
}


// 5. Check CSRF token
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


$courseName = $_POST["course_name"] ?? "";
$description = $_POST["description"] ?? "";


if (!is_string($courseName) || !is_string($description)) {
    $_SESSION["error_message"] = "Invalid input";
    header("Location: " . "add-course.php");
    exit;
}

$courseName = trim($courseName);
$description = trim($description);

if ($courseName === "" || mb_strlen($courseName) > 100) {
    $_SESSION["error_message"] = "Course name is required (max 100 characters)";
    header("Location: " . "add-course.php");
    exit;
}

if ($description === "" || mb_strlen($description) > 5000) {
    $_SESSION["error_message"] = "Description is required (max 5000 characters)";
    header("Location: " . "add-course.php");
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO courses (course_name, description)
     VALUES (?, ?)"
);

$stmt->execute([$courseName, $description]);


// 9. Save success message
$_SESSION["success_message"] = "Course added successfully!";

// 10. Redirect
header("Location: admin.php");
exit;
