<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";

if (isset($_SESSION["user"])) {
    $role = (string)($_SESSION["user"]["role"] ?? "");
    redirect($role === "Admin" ? "admin_dashboard.php" : "user_dashboard.php");
}

redirect("login.php");
