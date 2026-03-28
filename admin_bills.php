<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/layout.php";

require_admin();
refresh_overdue($conn);

function next_int_id(mysqli $conn, string $table, string $col): int
{
    $sql = "SELECT COALESCE(MAX($col), 0) AS m FROM $table";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row["m"] ?? 0) + 1;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    $action = (string)($_POST["action"] ?? "");

    if ($action === "create_bill") {
        $flatId = (int)($_POST["flat_id"] ?? 0);
        $amount = (float)($_POST["amount"] ?? 0);
        $dueDate = (string)($_POST["due_date"] ?? "");
        $type = (string)($_POST["bill_type"] ?? "monthly");

        if ($flatId <= 0 || $amount <= 0 || $dueDate === "") {
            flash_set("error", "Please fill bill details correctly.");
            redirect("admin_bills.php");
        }

        $stmt = $conn->prepare("SELECT owner_id FROM Flat WHERE flat_id = ? LIMIT 1");
        $stmt->bind_param("i", $flatId);
        $stmt->execute();
        $flatRow = $stmt->get_result()->fetch_assoc();
        $ownerId = (int)($flatRow["owner_id"] ?? 0);
        if ($ownerId <= 0) {
            flash_set("error", "This flat has no owner assigned.");
            redirect("admin_bills.php");
        }

        $maintenanceId = next_int_id($conn, "Maintenance", "maintenance_id");
        $mId = next_int_id($conn, "Maintenance", "m_id");

        $isMonthly = $type === "monthly" ? 1 : 0;
        $isYearly = $type === "yearly" ? 1 : 0;
        $isEmergency = $type === "emergency" ? 1 : 0;
        $isDue = 0;

        $billingDate = (new DateTimeImmutable("now"))->format("Y-m-d");
        $status = "Pending";

        $stmt = $conn->prepare("
            INSERT INTO Maintenance
                (maintenance_id, m_id, owner_id, amount, billing_date, due_date, status, is_monthly, is_yearly, is_due, is_emergency)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            flash_set("error", "Failed to create bill. Please try again.");
            redirect("admin_bills.php");
        }
        $stmt->bind_param(
            "iiidsssiiii",
            $maintenanceId,
            $mId,
            $ownerId,
            $amount,
            $billingDate,
            $dueDate,
            $status,
            $isMonthly,
            $isYearly,
            $isDue,
            $isEmergency
        );
        $stmt->execute();

        flash_set("success", "Maintenance bill created (ID: $maintenanceId).");
        redirect("admin_bills.php");
    }

    if ($action === "mark_paid") {
        $maintenanceId = (int)($_POST["maintenance_id"] ?? 0);
        if ($maintenanceId <= 0) {
            flash_set("error", "Invalid bill.");
            redirect("admin_bills.php");
        }
        $sql = maintenance_has_paid_date($conn)
            ? "UPDATE Maintenance SET status='Paid', paid_date=CURDATE() WHERE maintenance_id = ?"
            : "UPDATE Maintenance SET status='Paid' WHERE maintenance_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $maintenanceId);
        $stmt->execute();
        flash_set("success", "Bill marked as Paid.");
        redirect("admin_bills.php");
    }

    flash_set("error", "Invalid action.");
    redirect("admin_bills.php");
}

// Assigned flats dropdown
$assignedFlats = [];
$sqlFlats = "
    SELECT f.flat_id, f.flat_number, b.building_name, f.floor_number, f.owner_id,
           CONCAT(o.first_name, ' ', o.last_name) AS owner_name
    FROM Flat f
    JOIN Building b ON b.building_id = f.building_id
    JOIN Flat_Owner o ON o.owner_id = f.owner_id
    WHERE f.owner_id IS NOT NULL
    ORDER BY b.building_name, f.floor_number, f.flat_number
";
if ($res = $conn->query($sqlFlats)) {
    while ($row = $res->fetch_assoc()) {
        $assignedFlats[] = $row;
    }
}

// Recent bills
$bills = [];
$sqlBills = "
    SELECT m.maintenance_id, m.amount, m.billing_date, m.due_date, m.status, m.is_monthly, m.is_yearly, m.is_emergency,
           CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
           MIN(f.flat_number) AS flat_number
    FROM Maintenance m
    JOIN Flat_Owner o ON o.owner_id = m.owner_id
    LEFT JOIN Flat f ON f.owner_id = m.owner_id
    GROUP BY m.maintenance_id, m.amount, m.billing_date, m.due_date, m.status, m.is_monthly, m.is_yearly, m.is_emergency, owner_name
    ORDER BY m.due_date DESC, m.maintenance_id DESC
    LIMIT 50
";
if ($res = $conn->query($sqlBills)) {
    while ($row = $res->fetch_assoc()) {
        $bills[] = $row;
    }
}

layout_start("Generate Maintenance Bills", "admin_bills");
?>

<div class="notice-card">
    <h4>Create New Bill</h4>
    <form method="POST" action="admin_bills.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create_bill">

        <label><b>Flat</b></label>
        <select name="flat_id" required>
            <option value="">Select flat</option>
            <?php foreach ($assignedFlats as $f): ?>
                <option value="<?php echo (int)$f["flat_id"]; ?>">
                    <?php echo h($f["building_name"] . " - " . $f["flat_number"] . " (" . $f["owner_name"] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label><b>Bill Type</b></label>
        <select name="bill_type" required>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
            <option value="emergency">Emergency</option>
        </select>

        <label><b>Amount</b></label>
        <input type="number" step="0.01" name="amount" required>

        <label><b>Due Date</b></label>
        <input type="date" name="due_date" required>

        <button type="submit" style="margin-top:10px;">Create Bill</button>
    </form>
</div>

<h3 style="margin-top:20px;">Recent Bills</h3>
<table>
    <thead>
        <tr>
            <th>Flat</th>
            <th>Owner</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Billing</th>
            <th>Due</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($bills) === 0): ?>
            <tr><td colspan="8">No bills found.</td></tr>
        <?php else: ?>
            <?php foreach ($bills as $b): ?>
                <?php
                    $type = ($b["is_emergency"] ? "Emergency" : ($b["is_yearly"] ? "Yearly" : "Monthly"));
                    $status = (string)$b["status"];
                    $statusClass = $status === "Paid" ? "paid" : "pending";
                ?>
                <tr>
                    <td><?php echo h((string)($b["flat_number"] ?? "-")); ?></td>
                    <td><?php echo h((string)$b["owner_name"]); ?></td>
                    <td><?php echo h($type); ?></td>
                    <td>₹<?php echo number_format((float)$b["amount"], 2); ?></td>
                    <td><?php echo h((string)$b["billing_date"]); ?></td>
                    <td><?php echo h((string)$b["due_date"]); ?></td>
                    <td class="<?php echo $statusClass; ?>"><?php echo h($status); ?></td>
                    <td>
                        <?php if ($status !== "Paid"): ?>
                            <form method="POST" action="admin_bills.php" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="mark_paid">
                                <input type="hidden" name="maintenance_id" value="<?php echo (int)$b["maintenance_id"]; ?>">
                                <button type="submit" style="padding:6px 12px;border-radius:10px;">Mark Paid</button>
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
