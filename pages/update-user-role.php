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

// 3. Database connection
require_once __DIR__ . "/../config/db.php";

// 4. Check admin permission
$stmt = $pdo->prepare(
    "SELECT role FROM users WHERE id = ?"
);

$stmt->execute([$_SESSION["user_id"]]);

$currentRole = $stmt->fetchColumn();

if ($currentRole !== "admin") {
    http_response_code(403);
    exit("Access Denied");
}

// 5. CSRF protection
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

// 6. Validate target user ID
$targetUserId = filter_input(
    INPUT_POST,
    "user_id",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if (!$targetUserId) {
    http_response_code(400);
    exit("Invalid User ID");
}

// 7. Validate new role
$newRole = $_POST["role"] ?? null;

if (
    !is_string($newRole) ||
    !in_array($newRole, ["student", "admin"], true)
) {
    http_response_code(400);
    exit("Invalid Role");
}

// 8. Prevent changing your own role
if ($targetUserId === (int) $_SESSION["user_id"]) {
    $_SESSION["error_message"] =
        "You cannot change your own role.";

    header("Location: manage-users.php");
    exit;
}

try {

    // 9. Start transaction
    $pdo->beginTransaction();

    // Lock current admin row
    $stmt = $pdo->prepare(
        "SELECT role FROM users WHERE id = ? FOR UPDATE"
    );

    $stmt->execute([$_SESSION["user_id"]]);

    if ($stmt->fetchColumn() !== "admin") {
        $pdo->rollBack();
        http_response_code(403);
        exit("Access Denied");
    }

    // 10. Get target user
    $stmt = $pdo->prepare(
        "SELECT role FROM users WHERE id = ? FOR UPDATE"
    );

    $stmt->execute([$targetUserId]);

    $targetRole = $stmt->fetchColumn();

    if ($targetRole === false) {
        $pdo->rollBack();

        $_SESSION["error_message"] =
            "User not found.";

        header("Location: manage-users.php");
        exit;
    }

    // 11. Update user role
    $stmt = $pdo->prepare(
        "UPDATE users
         SET role = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $newRole,
        $targetUserId
    ]);

    // 12. Commit transaction
    $pdo->commit();

    $_SESSION["success_message"] =
        "User role updated successfully!";

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION["error_message"] =
        "Could not update user role.";
}

// 13. Redirect
header("Location: manage-users.php");
exit;
