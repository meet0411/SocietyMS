<?php
declare(strict_types=1);

// XAMPP default database connection settings
$servername = "localhost";
$username = "root";
$password = ""; // By default, XAMPP has no password for the root user
$database = "society_maintenance_system";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function refresh_overdue(mysqli $conn): void
{
    $conn->query("UPDATE Maintenance SET status='Overdue' WHERE status='Pending' AND due_date < CURDATE()");
}

function maintenance_has_paid_date(mysqli $conn): bool
{
    static $cached = null;
    if (is_bool($cached)) {
        return $cached;
    }
    $res = $conn->query("SHOW COLUMNS FROM Maintenance LIKE 'paid_date'");
    $cached = $res && $res->num_rows > 0;
    return $cached;
}
?>
