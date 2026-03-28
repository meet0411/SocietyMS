<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/layout.php";

require_user();
refresh_overdue($conn);

$user = current_user();
$ownerId = (int)$user["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    $action = (string)($_POST["action"] ?? "");

    if ($action === "pay_now") {
        $maintenanceId = (int)($_POST["maintenance_id"] ?? 0);
        if ($maintenanceId <= 0) {
            flash_set("error", "Invalid bill.");
            redirect("user_dues.php");
        }

        $stmt = $conn->prepare("SELECT status FROM Maintenance WHERE maintenance_id=? AND owner_id=? LIMIT 1");
        $stmt->bind_param("ii", $maintenanceId, $ownerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            flash_set("error", "Bill not found.");
            redirect("user_dues.php");
        }
        if ((string)$row["status"] === "Paid") {
            flash_set("success", "This bill is already paid.");
            redirect("user_dues.php");
        }

        $sql = maintenance_has_paid_date($conn)
            ? "UPDATE Maintenance SET status='Paid', paid_date=CURDATE() WHERE maintenance_id=? AND owner_id=?"
            : "UPDATE Maintenance SET status='Paid' WHERE maintenance_id=? AND owner_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $maintenanceId, $ownerId);
        $stmt->execute();
        flash_set("success", "Payment successful (simulated).");
        redirect("user_dues.php");
    }

    flash_set("error", "Invalid action.");
    redirect("user_dues.php");
}

$statusFilter = (string)($_GET["status"] ?? "all");
$allowed = ["all", "Paid", "Pending", "Overdue"];
if (!in_array($statusFilter, $allowed, true)) {
    $statusFilter = "all";
}

$sql = "
    SELECT maintenance_id, amount, billing_date, due_date, status, is_monthly, is_yearly, is_emergency
    FROM Maintenance
    WHERE owner_id = ?
";
if ($statusFilter !== "all") {
    $sql .= " AND status = ?";
}
$sql .= " ORDER BY due_date DESC, maintenance_id DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if ($statusFilter === "all") {
    $stmt->bind_param("i", $ownerId);
} else {
    $stmt->bind_param("is", $ownerId, $statusFilter);
}
$stmt->execute();
$res = $stmt->get_result();
$bills = [];
while ($res && ($row = $res->fetch_assoc())) {
    $bills[] = $row;
}

layout_start("My Dues", "user_dues");
?>

<div class="notice-card">
    <h4>Filter</h4>
    <form method="GET" action="user_dues.php">
        <label><b>Status</b></label>
        <select name="status" onchange="this.form.submit()">
            <option value="all" <?php echo $statusFilter === "all" ? "selected" : ""; ?>>All</option>
            <option value="Paid" <?php echo $statusFilter === "Paid" ? "selected" : ""; ?>>Paid</option>
            <option value="Pending" <?php echo $statusFilter === "Pending" ? "selected" : ""; ?>>Pending</option>
            <option value="Overdue" <?php echo $statusFilter === "Overdue" ? "selected" : ""; ?>>Overdue</option>
        </select>
        <noscript><button type="submit">Apply</button></noscript>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Amount</th>
            <th>Billing</th>
            <th>Due</th>
            <th>Status</th>
            <th>Pay</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($bills) === 0): ?>
            <tr><td colspan="6">No bills found.</td></tr>
        <?php else: ?>
            <?php foreach ($bills as $b): ?>
                <?php
                    $type = ($b["is_emergency"] ? "Emergency" : ($b["is_yearly"] ? "Yearly" : "Monthly"));
                    $status = (string)$b["status"];
                    $statusClass = $status === "Paid" ? "paid" : "pending";
                ?>
                <tr>
                    <td><?php echo h($type); ?></td>
                    <td>₹<?php echo number_format((float)$b["amount"], 2); ?></td>
                    <td><?php echo h((string)$b["billing_date"]); ?></td>
                    <td><?php echo h((string)$b["due_date"]); ?></td>
                    <td class="<?php echo $statusClass; ?>"><?php echo h($status); ?></td>
                    <td>
                        <?php if ($status !== "Paid"): ?>
                            <form method="POST" action="user_dues.php" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="pay_now">
                                <input type="hidden" name="maintenance_id" value="<?php echo (int)$b["maintenance_id"]; ?>">
                                <button type="submit" style="padding:6px 12px;border-radius:10px;">Pay Now</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
layout_end();
