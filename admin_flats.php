<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/layout.php";

require_admin();
refresh_overdue($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $action = (string)($_POST["action"] ?? "");

    if ($action === "assign") {
        $flatId = (int)($_POST["flat_id"] ?? 0);
        $ownerId = (int)($_POST["owner_id"] ?? 0);

        if ($flatId <= 0 || $ownerId <= 0) {
            flash_set("error", "Please select a flat and a user.");
            redirect("admin_flats.php");
        }

        // Flat must be unassigned
        $stmt = $conn->prepare("SELECT owner_id FROM Flat WHERE flat_id = ? LIMIT 1");
        $stmt->bind_param("i", $flatId);
        $stmt->execute();
        $flatRow = $stmt->get_result()->fetch_assoc();
        if (!$flatRow) {
            flash_set("error", "Flat not found.");
            redirect("admin_flats.php");
        }
        if (!empty($flatRow["owner_id"])) {
            flash_set("error", "This flat is already assigned.");
            redirect("admin_flats.php");
        }

        // Owner must exist as a User
        $stmt = $conn->prepare("SELECT user_id FROM System_Users WHERE user_id = ? AND role='User' LIMIT 1");
        $stmt->bind_param("i", $ownerId);
        $stmt->execute();
        $uRow = $stmt->get_result()->fetch_assoc();
        if (!$uRow) {
            flash_set("error", "User not found.");
            redirect("admin_flats.php");
        }

        // Owner must not already be assigned to another flat
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM Flat WHERE owner_id = ?");
        $stmt->bind_param("i", $ownerId);
        $stmt->execute();
        $countRow = $stmt->get_result()->fetch_assoc();
        $alreadyAssigned = (int)($countRow["c"] ?? 0);
        if ($alreadyAssigned > 0) {
            flash_set("error", "This user is already assigned to a flat. Unassign first.");
            redirect("admin_flats.php");
        }

        $stmt = $conn->prepare("UPDATE Flat SET owner_id = ? WHERE flat_id = ?");
        $stmt->bind_param("ii", $ownerId, $flatId);
        $stmt->execute();

        flash_set("success", "User assigned to flat successfully.");
        redirect("admin_flats.php");
    }

    if ($action === "unassign") {
        $flatId = (int)($_POST["flat_id"] ?? 0);
        if ($flatId <= 0) {
            flash_set("error", "Invalid flat.");
            redirect("admin_flats.php");
        }
        $stmt = $conn->prepare("UPDATE Flat SET owner_id = NULL WHERE flat_id = ?");
        $stmt->bind_param("i", $flatId);
        $stmt->execute();

        flash_set("success", "Flat unassigned successfully.");
        redirect("admin_flats.php");
    }

    flash_set("error", "Invalid action.");
    redirect("admin_flats.php");
}

// Available users not assigned to any flat
$availableUsers = [];
$sqlUsers = "
    SELECT su.user_id, su.username, fo.first_name, fo.last_name
    FROM System_Users su
    JOIN Flat_Owner fo ON fo.owner_id = su.user_id
    LEFT JOIN Flat f ON f.owner_id = su.user_id
    WHERE su.role='User' AND f.flat_id IS NULL
    ORDER BY su.user_id ASC
";
if ($res = $conn->query($sqlUsers)) {
    while ($row = $res->fetch_assoc()) {
        $availableUsers[] = $row;
    }
}

// Unassigned flats
$unassignedFlats = [];
$sqlUnassigned = "
    SELECT f.flat_id, f.flat_number, f.floor_number, b.building_name
    FROM Flat f
    JOIN Building b ON b.building_id = f.building_id
    WHERE f.owner_id IS NULL
    ORDER BY b.building_name, f.floor_number, f.flat_number
";
if ($res = $conn->query($sqlUnassigned)) {
    while ($row = $res->fetch_assoc()) {
        $unassignedFlats[] = $row;
    }
}

// All flats listing
$flats = [];
$sqlFlats = "
    SELECT
        f.flat_id,
        f.flat_number,
        f.floor_number,
        b.building_name,
        f.owner_id,
        su.username,
        CONCAT(fo.first_name, ' ', fo.last_name) AS owner_name
    FROM Flat f
    JOIN Building b ON b.building_id = f.building_id
    LEFT JOIN System_Users su ON su.user_id = f.owner_id
    LEFT JOIN Flat_Owner fo ON fo.owner_id = f.owner_id
    ORDER BY b.building_name, f.floor_number, f.flat_number
";
if ($res = $conn->query($sqlFlats)) {
    while ($row = $res->fetch_assoc()) {
        $flats[] = $row;
    }
}

layout_start("Manage Flats & Owners", "admin_flats");
?>

<div class="notice-card">
    <h4>Assign a User to a Flat</h4>
    <form method="POST" action="admin_flats.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="assign">

        <label><b>Unassigned Flat</b></label>
        <select name="flat_id" required>
            <option value="">Select flat</option>
            <?php foreach ($unassignedFlats as $f): ?>
                <option value="<?php echo (int)$f["flat_id"]; ?>">
                    <?php echo h($f["building_name"] . " - " . $f["flat_number"] . " (Floor " . $f["floor_number"] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label><b>Registered User (Not Assigned)</b></label>
        <select name="owner_id" required>
            <option value="">Select user</option>
            <?php foreach ($availableUsers as $u): ?>
                <option value="<?php echo (int)$u["user_id"]; ?>">
                    <?php echo h("#" . $u["user_id"] . " - " . $u["username"] . " (" . $u["first_name"] . " " . $u["last_name"] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" style="margin-top:10px;">Assign</button>
    </form>
    <p style="margin-top:10px;color:#666;">If the dropdowns are empty, run <a href="setup_society.php">setup_society</a> and/or register users.</p>
</div>

<h3 style="margin-top:20px;">All Flats</h3>
<table>
    <thead>
        <tr>
            <th>Flat</th>
            <th>Building</th>
            <th>Floor</th>
            <th>Owner</th>
            <th>Username</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($flats) === 0): ?>
            <tr><td colspan="6">No flats found. Run <a href="setup_society.php">setup</a>.</td></tr>
        <?php else: ?>
            <?php foreach ($flats as $f): ?>
                <tr>
                    <td><?php echo h((string)$f["flat_number"]); ?></td>
                    <td><?php echo h((string)$f["building_name"]); ?></td>
                    <td><?php echo (int)$f["floor_number"]; ?></td>
                    <td><?php echo $f["owner_id"] ? h((string)$f["owner_name"]) : "-"; ?></td>
                    <td><?php echo $f["owner_id"] ? h((string)$f["username"]) : "-"; ?></td>
                    <td>
                        <?php if ($f["owner_id"]): ?>
                            <form method="POST" action="admin_flats.php" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="unassign">
                                <input type="hidden" name="flat_id" value="<?php echo (int)$f["flat_id"]; ?>">
                                <button type="submit" class="logout-btn" style="padding:6px 12px;border-radius:10px;">Unassign</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#666;">Unassigned</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
layout_end();

