<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/layout.php";

require_admin();
refresh_overdue($conn);

$totalFlats = 0;
$assignedFlats = 0;
$totalOwners = 0;
$totalUsers = 0;
$pendingBills = 0;
$pendingAmount = 0.0;

if ($res = $conn->query("SELECT COUNT(*) AS c FROM Flat")) {
    $totalFlats = (int)(($res->fetch_assoc()["c"] ?? 0));
}
if ($res = $conn->query("SELECT COUNT(*) AS c FROM Flat WHERE owner_id IS NOT NULL")) {
    $assignedFlats = (int)(($res->fetch_assoc()["c"] ?? 0));
}
if ($res = $conn->query("SELECT COUNT(*) AS c FROM Flat_Owner")) {
    $totalOwners = (int)(($res->fetch_assoc()["c"] ?? 0));
}
if ($res = $conn->query("SELECT COUNT(*) AS c FROM System_Users WHERE role='User'")) {
    $totalUsers = (int)(($res->fetch_assoc()["c"] ?? 0));
}
if ($res = $conn->query("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS s FROM Maintenance WHERE status IN ('Pending','Overdue')")) {
    $row = $res->fetch_assoc();
    $pendingBills = (int)($row["c"] ?? 0);
    $pendingAmount = (float)($row["s"] ?? 0);
}

layout_start("Admin Dashboard", "admin_dashboard");
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Flats</h3>
        <div class="stat-number"><?php echo (int)$totalFlats; ?></div>
    </div>
    <div class="stat-card">
        <h3>Assigned Flats</h3>
        <div class="stat-number"><?php echo (int)$assignedFlats; ?></div>
    </div>
    <div class="stat-card">
        <h3>Users Registered</h3>
        <div class="stat-number"><?php echo (int)$totalUsers; ?></div>
    </div>
    <div class="stat-card">
        <h3>Pending / Overdue</h3>
        <div class="stat-number pending"><?php echo (int)$pendingBills; ?></div>
        <div style="margin-top:8px;">₹<?php echo number_format((float)$pendingAmount, 2); ?></div>
    </div>
</div>

<div class="notice-card">
    <h4>Next Actions</h4>
    <p>1) Generate flats: <a href="setup_society.php">Run setup</a> (or <a href="setup_society.php?reset=1">reset</a>)</p>
    <p>2) Assign users to flats: <a href="admin_flats.php">Manage Flats & Owners</a></p>
    <p>3) Create bills: <a href="admin_bills.php">Generate Maintenance Bills</a></p>
    <p>4) Track payments: <a href="admin_payments.php">Payments</a></p>
</div>

<?php
layout_end();

