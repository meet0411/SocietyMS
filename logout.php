<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";

no_cache();
$_SESSION = [];
session_destroy();
redirect("login.php");
?>
