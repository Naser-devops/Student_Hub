<?php

session_start();

// 1. Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 2. Allow POST only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method Not Allowed");
}

// 3. Connect to database
require_once __DIR__ . "/../config/db.php";

// 4. Check admin role
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
    http_response_code(400);
    exit("Invalid Course ID");
}

try {

    // 7. Start transaction
    $pdo->beginTransaction();

    // 8. Lock the course row
    $stmt = $pdo->prepare(
        "SELECT id FROM courses WHERE id = ? FOR UPDATE"
    );

    $stmt->execute([$courseId]);

    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(404);
        exit("Course Not Found");
    }

    // 9. Check course enrollments
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM enrollments WHERE course_id = ?"
    );

    $stmt->execute([$courseId]);

    $enrollmentCount = (int) $stmt->fetchColumn();

    if ($enrollmentCount > 0) {

        $pdo->rollBack();

        $_SESSION["error_message"] =
            "Cannot delete a course with enrolled students.";

        header("Location: admin.php");
        exit;
    }

    // 10. Delete course
    $stmt = $pdo->prepare(
        "DELETE FROM courses WHERE id = ?"
    );

    $stmt->execute([$courseId]);

    // 11. Commit changes
    $pdo->commit();

    $_SESSION["success_message"] =
        "Course deleted successfully!";
} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION["error_message"] =
        "Could not delete the course.";
}

// 12. Redirect
header("Location: admin.php");
exit;
