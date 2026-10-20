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

// 4. Check admin permission
$stmt = $pdo->prepare(
    "SELECT role FROM users WHERE id = ?"
);

$stmt->execute([$_SESSION["user_id"]]);

$role = $stmt->fetchColumn();

if ($role !== "admin") {
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

// 6. Validate course ID
$courseId = filter_input(
    INPUT_POST,
    "course_id",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if (!$courseId) {
    exit("Invalid Course ID");
}

// 7. Get form data
$courseName = $_POST["course_name"] ?? "";
$description = $_POST["description"] ?? "";

if (!is_string($courseName) || !is_string($description)) {
    $_SESSION["error_message"] = "Invalid input";
    header("Location: " . "edit-course.php?id=" . $courseId);
    exit;
}

$courseName = trim($courseName);
$description = trim($description);

// 8. Validate data
if ($courseName === "" || mb_strlen($courseName) > 100) {
    $_SESSION["error_message"] = "Course name is required (max 100 characters)";
    header("Location: " . "edit-course.php?id=" . $courseId);
    exit;
}

if ($description === "" || mb_strlen($description) > 5000) {
    $_SESSION["error_message"] = "Description is required (max 5000 characters)";
    header("Location: " . "edit-course.php?id=" . $courseId);
    exit;
}

// 9. Check if course exists
$stmt = $pdo->prepare(
    "SELECT id FROM courses WHERE id = ?"
);

$stmt->execute([$courseId]);

if (!$stmt->fetchColumn()) {
    http_response_code(404);
    exit("Course Not Found");
}

// 10. Update course
$stmt = $pdo->prepare(
    "UPDATE courses
     SET course_name = ?, description = ?
     WHERE id = ?"
);

$stmt->execute([
    $courseName,
    $description,
    $courseId
]);

// 11. Save success message
$_SESSION["success_message"] = "Course updated successfully!";

// 12. Redirect
header("Location: admin.php");
exit;
