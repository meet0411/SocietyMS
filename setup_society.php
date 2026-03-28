<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

require_admin();

// Usage:
// - Open /society_maintenance/setup_society.php to insert if not already created.
// - Open /society_maintenance/setup_society.php?reset=1 to wipe and recreate Society/Building/Flat data.
$doReset = isset($_GET["reset"]) && $_GET["reset"] === "1";

$society_id = 1;
$society = [
    "name" => "Techville Residency",
    "city" => "Mira Bhayandar",
    "area" => "Station Road",
    "pincode" => "401107",
];

$building_names = ["A", "B"]; // 2 buildings
$total_floors = 3;           // 3 floors each
$flats_per_floor = 4;        // 4 flats per floor
$default_flat_price = 4500000.00;

function out(string $html): void
{
    echo $html . "<br>\n";
}

try {
    $conn->begin_transaction();

    if ($doReset) {
        out("<h3>Reset mode ON</h3>");
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $conn->query("TRUNCATE TABLE Flat");
        $conn->query("TRUNCATE TABLE Building");
        $conn->query("TRUNCATE TABLE Society");
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        out("Cleared Flat, Building, Society tables.");
    }

    // Society (upsert-like)
    $stmt = $conn->prepare("SELECT society_id FROM Society WHERE society_id = ? LIMIT 1");
    $stmt->bind_param("i", $society_id);
    $stmt->execute();
    $existing = $stmt->get_result();

    if ($existing && $existing->num_rows === 1) {
        $stmtUp = $conn->prepare("UPDATE Society SET name=?, city=?, area=?, pincode=? WHERE society_id=?");
        $stmtUp->bind_param("ssssi", $society["name"], $society["city"], $society["area"], $society["pincode"], $society_id);
        $stmtUp->execute();
        out("Society updated (ID $society_id).");
    } else {
        $stmtIns = $conn->prepare("INSERT INTO Society (society_id, name, city, area, pincode) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->bind_param("issss", $society_id, $society["name"], $society["city"], $society["area"], $society["pincode"]);
        $stmtIns->execute();
        out("Society created (ID $society_id).");
    }

    if (!$doReset) {
        $check = $conn->prepare("SELECT COUNT(*) AS c FROM Building WHERE society_id = ?");
        $check->bind_param("i", $society_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $existingBuildings = (int)($row["c"] ?? 0);
        if ($existingBuildings > 0) {
            out("<b>Buildings already exist for this society.</b> Nothing inserted.");
            out("If you want to recreate exactly 2 buildings + 24 flats, re-run with <code>?reset=1</code>.");
            $conn->commit();
            exit;
        }
    }

    // Buildings + Flats
    $building_id = 1;
    $flat_id = 1;

    foreach ($building_names as $b_name) {
        $building_name = "Wing " . $b_name;

        $stmtB = $conn->prepare("INSERT INTO Building (building_id, society_id, building_name, total_floors) VALUES (?, ?, ?, ?)");
        $stmtB->bind_param("iisi", $building_id, $society_id, $building_name, $total_floors);
        $stmtB->execute();

        for ($floor = 1; $floor <= $total_floors; $floor++) {
            for ($flat = 1; $flat <= $flats_per_floor; $flat++) {
                $flat_number = $b_name . "-" . $floor . "0" . $flat; // A-101 .. A-304, B-101 .. B-304
                $owner_id = null;

                $stmtF = $conn->prepare("INSERT INTO Flat (flat_id, building_id, owner_id, flat_number, floor_number, flat_price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtF->bind_param("iiisid", $flat_id, $building_id, $owner_id, $flat_number, $floor, $default_flat_price);
                $stmtF->execute();

                $flat_id++;
            }
        }

        $building_id++;
    }

    $conn->commit();

    $totalFlats = count($building_names) * $total_floors * $flats_per_floor;
    out("<h3>Setup Complete!</h3>");
    out("Created 2 buildings, $total_floors floors each, $flats_per_floor flats per floor.");
    out("Total flats created: <b>$totalFlats</b>.");
    out("Tip: Re-run with <code>?reset=1</code> if you want to wipe and recreate.");
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Setup failed: " . h($e->getMessage());
}
