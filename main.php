<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";

require_login();
$user = current_user();
$role = (string)($user["role"] ?? "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Society Maintenance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body" data-username="<?php echo h((string)$user["username"]); ?>" data-role="<?php echo h($role); ?>">
    <header>
        <img src="logo.jpg" alt="Logo" class="header-logo">
        <h1>Society Maintenance Management System</h1>
        <div class="user-info">
            <span id="welcomeUser"></span>
            <button onclick="logout()" class="logout-btn">Logout</button>
        </div>
    </header>

    <main class="dashboard" style="margin-left:0;">
        <div class="content-sections">
            <section class="section active">
                <h2>Home</h2>
                <div class="notice-card">
                    <h4>Account</h4>
                    <p>Logged in as <b><?php echo h((string)$user["username"]); ?></b> (<?php echo h($role); ?>)</p>
                    <p><a href="logout.php">Logout</a></p>
                </div>

                <?php if (is_admin()): ?>
                    <div class="notice-card">
                        <h4>Admin Tools</h4>
                        <p><a href="admin_dashboard.php">Admin Dashboard</a></p>
                        <p>Initialize society/building/flat data: <a href="setup_society.php">Run setup</a></p>
                        <p>To wipe and recreate: <a href="setup_society.php?reset=1">Run setup with reset</a></p>
                        <p>Assign users to flats: <a href="admin_flats.php">Manage Flats & Owners</a></p>
                        <p>Create bills: <a href="admin_bills.php">Generate Bills</a></p>
                    </div>
                <?php else: ?>
                    <div class="notice-card">
                        <h4>User Pages</h4>
                        <p><a href="user_dashboard.php">User Dashboard</a></p>
                        <p><a href="user_profile.php">Profile</a> | <a href="user_dues.php">My Dues</a></p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
