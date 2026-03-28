<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/layout.php";

require_user();
refresh_overdue($conn);

$user = current_user();
$ownerId = (int)$user["user_id"];

$flatInfo = null;
$stmt = $conn->prepare("
    SELECT f.flat_number, f.floor_number, b.building_name, s.name AS society_name, s.city, s.area
    FROM Flat f
    JOIN Building b ON b.building_id = f.building_id
    JOIN Society s ON s.society_id = b.society_id
    WHERE f.owner_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$flatInfo = $stmt->get_result()->fetch_assoc();

$pendingCount = 0;
$overdueCount = 0;
$pendingAmount = 0.0;

$stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending_c,
        SUM(CASE WHEN status='Overdue' THEN 1 ELSE 0 END) AS overdue_c,
        COALESCE(SUM(CASE WHEN status IN ('Pending','Overdue') THEN amount ELSE 0 END), 0) AS pending_s
    FROM Maintenance
    WHERE owner_id = ?
");
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if ($row) {
    $pendingCount = (int)($row["pending_c"] ?? 0);
    $overdueCount = (int)($row["overdue_c"] ?? 0);
    $pendingAmount = (float)($row["pending_s"] ?? 0);
}

layout_start("User Dashboard", "user_dashboard");
?>

<?php if (!$flatInfo): ?>
    <div class="notice-card">
        <h4>Flat Not Assigned Yet</h4>
        <p>Your account is registered, but the Admin has not assigned you to a flat.</p>
        <p>Please wait for the Admin to assign your <b>User ID: <?php echo (int)$ownerId; ?></b> to a flat.</p>
    </div>
<?php else: ?>
    <div class="notice-card">
        <h4>Welcome</h4>
        <p><b>Society:</b> <?php echo h((string)$flatInfo["society_name"]); ?> (<?php echo h((string)$flatInfo["city"]); ?>, <?php echo h((string)$flatInfo["area"]); ?>)</p>
        <p><b>Building:</b> <?php echo h((string)$flatInfo["building_name"]); ?> | <b>Flat:</b> <?php echo h((string)$flatInfo["flat_number"]); ?> | <b>Floor:</b> <?php echo (int)$flatInfo["floor_number"]; ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Pending Bills</h3>
            <div class="stat-number pending"><?php echo (int)$pendingCount; ?></div>
        </div>
        <div class="stat-card">
            <h3>Overdue Bills</h3>
            <div class="stat-number pending"><?php echo (int)$overdueCount; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Due</h3>
            <div class="stat-number">₹<?php echo number_format((float)$pendingAmount, 2); ?></div>
        </div>
        <div class="stat-card">
            <h3>Quick Links</h3>
            <div style="margin-top:10px;">
                <a href="user_dues.php">My Dues</a> | <a href="user_profile.php">Profile</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
layout_end();

