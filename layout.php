<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";

function layout_start(string $title, string $activeKey): void
{
    require_login();

    $user = current_user();
    $username = (string)$user["username"];
    $role = (string)$user["role"];

    $items = [];
    if ($role === "Admin") {
        $items = [
            "admin_dashboard" => ["label" => "Dashboard", "href" => "admin_dashboard.php", "icon" => "🏠"],
            "admin_flats" => ["label" => "Flats & Owners", "href" => "admin_flats.php", "icon" => "🏢"],
            "admin_bills" => ["label" => "Generate Bills", "href" => "admin_bills.php", "icon" => "🧾"],
            "admin_payments" => ["label" => "Payments", "href" => "admin_payments.php", "icon" => "💳"],
        ];
    } else {
        $items = [
            "user_dashboard" => ["label" => "Dashboard", "href" => "user_dashboard.php", "icon" => "🏠"],
            "user_profile" => ["label" => "Profile", "href" => "user_profile.php", "icon" => "👤"],
            "user_dues" => ["label" => "My Dues", "href" => "user_dues.php", "icon" => "💰"],
        ];
    }

    $flashError = flash_get("error");
    $flashSuccess = flash_get("success");

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo h($title); ?></title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="dashboard-body" data-username="<?php echo h($username); ?>" data-role="<?php echo h($role); ?>">
        <header>
            <img src="logo.jpg" alt="Logo" class="header-logo">
            <h1>Society Maintenance Management System</h1>
            <div class="user-info">
                <span id="welcomeUser"></span>
                <button onclick="logout()" class="logout-btn">Logout</button>
            </div>
        </header>

        <nav class="sidebar">
            <ul>
                <?php foreach ($items as $key => $item): ?>
                    <li>
                        <a href="<?php echo h($item["href"]); ?>" class="<?php echo $key === $activeKey ? "active" : ""; ?>">
                            <i><?php echo h($item["icon"]); ?></i> <?php echo h($item["label"]); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <main class="dashboard">
            <div class="content-sections">
                <section class="section active">
                    <h2><?php echo h($title); ?></h2>

                    <?php if ($flashError): ?>
                        <div class="error"><?php echo h($flashError); ?></div>
                    <?php endif; ?>
                    <?php if ($flashSuccess): ?>
                        <div class="success"><?php echo h($flashSuccess); ?></div>
                    <?php endif; ?>
    <?php
}

function layout_end(): void
{
    ?>
                </section>
            </div>
        </main>

        <script src="script.js"></script>
    </body>
    </html>
    <?php
}

