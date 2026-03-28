<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

// This page is for first-time setup only.
// It allows creating the ONE Admin account if no Admin exists yet.

$res = $conn->query("SELECT COUNT(*) AS c FROM System_Users WHERE role='Admin'");
$row = $res ? $res->fetch_assoc() : null;
$adminCount = (int)($row["c"] ?? 0);

if ($adminCount >= 1) {
    flash_set("error", "Admin already exists. Please login.");
    redirect("login.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $username = isset($_POST["username"]) ? trim((string)$_POST["username"]) : "";
    $password = (string)($_POST["password"] ?? "");
    if ($username === "" || $password === "") {
        flash_set("error", "Please enter username and password.");
        redirect("init_admin.php");
    }

    $resMax = $conn->query("SELECT COALESCE(MAX(user_id), 0) AS m FROM System_Users");
    $maxRow = $resMax ? $resMax->fetch_assoc() : null;
    $nextId = (int)($maxRow["m"] ?? 0) + 1;

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = "Admin";

    $stmt = $conn->prepare("INSERT INTO System_Users (user_id, username, password_hash, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        flash_set("error", "Failed to create admin. Please try again.");
        redirect("init_admin.php");
    }
    $stmt->bind_param("isss", $nextId, $username, $hash, $role);
    if ($stmt->execute()) {
        flash_set("success", "Admin created. Please login.");
        redirect("login.php");
    }

    flash_set("error", "Failed to create admin: " . $conn->error);
    redirect("init_admin.php");
}

$errorMsg = flash_get("error");
$successMsg = flash_get("success");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="modal active">
        <form class="modal-content" method="POST" action="init_admin.php" autocomplete="off">
            <div class="imgcontainer">
                <img src="logo.jpg" alt="Society Logo" class="avatar">
                <h1>Create Admin (One Time)</h1>
            </div>

            <div class="container">
                <?php echo csrf_field(); ?>

                <label for="username"><b>Admin Username</b></label>
                <input type="text" name="username" id="username" placeholder="Enter admin username" required>

                <label for="password"><b>Password</b></label>
                <input type="password" name="password" id="password" placeholder="Create admin password" required>

                <?php if ($errorMsg): ?><div class="error"><?php echo h($errorMsg); ?></div><?php endif; ?>
                <?php if ($successMsg): ?><div class="success"><?php echo h($successMsg); ?></div><?php endif; ?>

                <button type="submit">Create Admin</button>
            </div>

            <div class="container footer">
                <span class="psw"><a href="login.php">Back to login</a></span>
            </div>
        </form>
    </div>
</body>
</html>

