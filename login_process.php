<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

$username = isset($_POST["username"]) ? trim((string)$_POST["username"]) : "";
$password = (string)($_POST["password"] ?? "");

csrf_verify();

if ($username === "" || $password === "") {
    flash_set("error", "Please enter username and password.");
    redirect("login.php");
}

$stmt = $conn->prepare("SELECT user_id, username, password_hash, role FROM System_Users WHERE username = ? LIMIT 1");
if (!$stmt) {
    flash_set("error", "Login failed. Please try again.");
    redirect("login.php");
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();

    if (!is_array($row)) {
        flash_set("error", "Login failed. Please try again.");
        redirect("login.php");
    }

    if (password_verify($password, (string)$row["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["user"] = [
            "user_id" => (int)$row["user_id"],
            "username" => (string)$row["username"],
            "role" => (string)$row["role"],
        ];
        $role = (string)$row["role"];
        redirect($role === "Admin" ? "admin_dashboard.php" : "user_dashboard.php");
    }

    flash_set("error", "Invalid username or password.");
    redirect("login.php");
}

flash_set("error", "Invalid username or password.");
redirect("login.php");
?>
