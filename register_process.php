<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("register.php");
}

csrf_verify();

$user_id = isset($_POST["user_id"]) ? (int)$_POST["user_id"] : 0;
$username = isset($_POST["username"]) ? trim((string)$_POST["username"]) : "";
$password = (string)($_POST["password"] ?? "");
$first_name = isset($_POST["first_name"]) ? trim((string)$_POST["first_name"]) : "";
$last_name = isset($_POST["last_name"]) ? trim((string)$_POST["last_name"]) : "";
$dob = (string)($_POST["dob"] ?? "");
$role = "User"; // Registration is for users only

if ($user_id <= 0 || $username === "" || $password === "" || $first_name === "" || $last_name === "" || $dob === "") {
    flash_set("error", "Please fill all fields correctly.");
    redirect("register.php");
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("INSERT INTO System_Users (user_id, username, password_hash, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new RuntimeException("Registration failed. Please try again.");
    }
    $stmt->bind_param("isss", $user_id, $username, $hashed_password, $role);
    $stmt->execute();

    $stmtOwner = $conn->prepare("INSERT INTO Flat_Owner (owner_id, first_name, last_name, dob) VALUES (?, ?, ?, ?)");
    if (!$stmtOwner) {
        throw new RuntimeException("Registration failed. Please try again.");
    }
    $stmtOwner->bind_param("isss", $user_id, $first_name, $last_name, $dob);
    $stmtOwner->execute();

    $conn->commit();
    flash_set("success", "Registration successful. Please login (Admin will assign your flat).");
    redirect("login.php");
} catch (Throwable $e) {
    $conn->rollback();

    if ($conn->errno === 1062) {
        flash_set("error", "User ID or username already exists.");
        redirect("register.php");
    }

    flash_set("error", "Registration failed: " . $e->getMessage());
    redirect("register.php");
}
?>
