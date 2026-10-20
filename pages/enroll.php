<?php

// 1. Start Session
session_start();

// 2. Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 3. Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: courses.php");
    exit;
}

// Check CSRF Token
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

// Check current user role
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$role = $stmt->fetchColumn();

// Only students can enroll
if ($role !== "student") {
    http_response_code(403);
    exit("Only students can enroll in courses.");
}

// 5. Get User ID from Session
$userId = $_SESSION["user_id"];

// 6. Get Course ID from POST
$courseId = $_POST["course_id"] ?? null;

// 7. Validate Course ID
if (
    $courseId === null ||
    !is_scalar($courseId) ||
    filter_var($courseId, FILTER_VALIDATE_INT) === false ||
    (int) $courseId <= 0
) {
    $_SESSION["error_message"] = "Invalid Course ID";
    header("Location: courses.php");
    exit;
}
$courseId = (int) $courseId;

// 8. Check if course exists
$stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
$stmt->execute([$courseId]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION["error_message"] = "Course not found";
    header("Location: courses.php");
    exit;
}

// 9. Check if user is already enrolled
$stmt = $pdo->prepare(
    "SELECT id FROM enrollments
     WHERE user_id = ? AND course_id = ?"
);
$stmt->execute([$userId, $courseId]);

if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION["error_message"] = "You are already enrolled in this course";
    header("Location: courses.php");
    exit;
}

// 10. Enroll student in course
try {
    $stmt = $pdo->prepare(
        "INSERT INTO enrollments (user_id, course_id)
         VALUES (?, ?)"
    );
    $stmt->execute([$userId, $courseId]);
    $_SESSION["success_message"] = "You enrolled successfully!";
} catch (PDOException $e) {
    // 23000 = duplicate entry (double click / race condition) or constraint violation
    error_log($e->getMessage());
    $_SESSION["error_message"] = $e->getCode() === "23000"
        ? "You are already enrolled in this course"
        : "Could not enroll. Please try again.";
}

header("Location: courses.php");
exit;
