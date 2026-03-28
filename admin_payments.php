<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/layout.php";

require_admin();
refresh_overdue($conn);

$statusFilter = (string)($_GET["status"] ?? "all");
$allowed = ["all", "Paid", "Pending", "Overdue"];
if (!in_array($statusFilter, $allowed, true)) {
    $statusFilter = "all";
}

$where = "";
if ($statusFilter !== "all") {
    $where = "WHERE m.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

$bills = [];
$sql = "
    SELECT m.maintenance_id, m.amount, m.billing_date, m.due_date, m.status,
           CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
           MIN(f.flat_number) AS flat_number
    FROM Maintenance m
    JOIN Flat_Owner o ON o.owner_id = m.owner_id
    LEFT JOIN Flat f ON f.owner_id = m.owner_id
    $where
    GROUP BY m.maintenance_id, m.amount, m.billing_date, m.due_date, m.status, owner_name
    ORDER BY m.due_date DESC, m.maintenance_id DESC
    LIMIT 200
";
if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $bills[] = $row;
    }
}

$totals = ["Paid" => 0.0, "Pending" => 0.0, "Overdue" => 0.0];
if ($res = $conn->query("SELECT status, COALESCE(SUM(amount),0) AS s FROM Maintenance GROUP BY status")) {
    while ($row = $res->fetch_assoc()) {
        $st = (string)($row["status"] ?? "");
        if (isset($totals[$st])) {
            $totals[$st] = (float)$row["s"];
        }
    }
}

layout_start("Payments", "admin_payments");
?>

<div class="notice-card">
    <h4>Summary</h4>
    <p>Paid: ₹<?php echo number_format($totals["Paid"], 2); ?> | Pending: ₹<?php echo number_format($totals["Pending"], 2); ?> | Overdue: ₹<?php echo number_format($totals["Overdue"], 2); ?></p>
    <form method="GET" action="admin_payments.php" style="margin-top:10px;">
        <label><b>Filter by Status</b></label>
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
            <th>Flat</th>
            <th>Owner</th>
            <th>Amount</th>
            <th>Billing</th>
            <th>Due</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($bills) === 0): ?>
            <tr><td colspan="6">No bills found.</td></tr>
        <?php else: ?>
            <?php foreach ($bills as $b): ?>
                <?php
                    $status = (string)$b["status"];
                    $statusClass = $status === "Paid" ? "paid" : "pending";
                ?>
                <tr>
                    <td><?php echo h((string)($b["flat_number"] ?? "-")); ?></td>
                    <td><?php echo h((string)$b["owner_name"]); ?></td>
                    <td>₹<?php echo number_format((float)$b["amount"], 2); ?></td>
                    <td><?php echo h((string)$b["billing_date"]); ?></td>
                    <td><?php echo h((string)$b["due_date"]); ?></td>
                    <td class="<?php echo $statusClass; ?>"><?php echo h($status); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
layout_end();

