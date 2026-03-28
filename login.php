<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

if (isset($_SESSION["user"])) {
    $role = (string)($_SESSION["user"]["role"] ?? "");
    redirect($role === "Admin" ? "admin_dashboard.php" : "user_dashboard.php");
}

$errorMsg = flash_get("error");
$successMsg = flash_get("success");

$adminCount = 0;
if ($res = $conn->query("SELECT COUNT(*) AS c FROM System_Users WHERE role='Admin'")) {
    $adminCount = (int)(($res->fetch_assoc()["c"] ?? 0));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Society Maintenance Management - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

    <div id="loginModal" class="modal active">
        <form class="modal-content" id="loginForm" method="POST" action="login_process.php" autocomplete="off">
            <div class="imgcontainer">
                <img src="logo.jpg" alt="Society Logo" class="avatar">
                <h1>Society Maintenance Management</h1>
            </div>

            <div class="container">
                <?php echo csrf_field(); ?>
                <label for="username"><b>Username</b></label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>

                <label for="password"><b>Password</b></label>
                <div class="eye">
                    <input type="password" id="password" name="password" placeholder="Enter Password" required>
                    <span class="eye-icon" id="eyeIcon">👁️</span>
                </div>

                <div id="errorMsg" class="error" style="<?php echo $errorMsg ? '' : 'display:none;'; ?>"><?php echo $errorMsg ? h($errorMsg) : ''; ?></div>
                <div id="successMsg" class="success" style="<?php echo $successMsg ? '' : 'display:none;'; ?>"><?php echo $successMsg ? h($successMsg) : ''; ?></div>

                <button type="submit">Login</button>
            </div>

            <div class="container footer">
                <span class="psw">Don’t have an account? <a href="register.php">Register</a></span>
                <?php if ($adminCount === 0): ?>
                    <div style="margin-top:8px;">
                        <a href="init_admin.php">Create Admin (one time)</a>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eyeIcon = document.getElementById('eyeIcon');
            const passwordField = document.getElementById('password');
            if (!eyeIcon || !passwordField) return;

            eyeIcon.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    eyeIcon.textContent = '🙈';
                } else {
                    passwordField.type = 'password';
                    eyeIcon.textContent = '👁️';
                }
            });
        });
    </script>
</body>
</html>
