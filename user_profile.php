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

    if ($action === "update_profile") {
        $first = trim((string)($_POST["first_name"] ?? ""));
        $last = trim((string)($_POST["last_name"] ?? ""));
        $dob = (string)($_POST["dob"] ?? "");

        if ($first === "" || $last === "" || $dob === "") {
            flash_set("error", "Please fill profile details.");
            redirect("user_profile.php");
        }

        $stmt = $conn->prepare("UPDATE Flat_Owner SET first_name=?, last_name=?, dob=? WHERE owner_id=?");
        $stmt->bind_param("sssi", $first, $last, $dob, $ownerId);
        $stmt->execute();
        flash_set("success", "Profile updated.");
        redirect("user_profile.php");
    }

    if ($action === "add_mobile") {
        $mobile = trim((string)($_POST["mobile_no"] ?? ""));
        if ($mobile === "") {
            flash_set("error", "Enter mobile number.");
            redirect("user_profile.php");
        }
        $stmt = $conn->prepare("INSERT INTO Owner_Mobile (owner_id, mobile_no) VALUES (?, ?)");
        $stmt->bind_param("is", $ownerId, $mobile);
        if ($stmt->execute()) {
            flash_set("success", "Mobile added.");
            redirect("user_profile.php");
        }
        flash_set("error", "Failed to add mobile (maybe duplicate).");
        redirect("user_profile.php");
    }

    if ($action === "delete_mobile") {
        $mobile = trim((string)($_POST["mobile_no"] ?? ""));
        $stmt = $conn->prepare("DELETE FROM Owner_Mobile WHERE owner_id=? AND mobile_no=?");
        $stmt->bind_param("is", $ownerId, $mobile);
        $stmt->execute();
        flash_set("success", "Mobile removed.");
        redirect("user_profile.php");
    }

    if ($action === "add_email") {
        $email = trim((string)($_POST["email"] ?? ""));
        if ($email === "") {
            flash_set("error", "Enter email.");
            redirect("user_profile.php");
        }
        $stmt = $conn->prepare("INSERT INTO Owner_Email (owner_id, email) VALUES (?, ?)");
        $stmt->bind_param("is", $ownerId, $email);
        if ($stmt->execute()) {
            flash_set("success", "Email added.");
            redirect("user_profile.php");
        }
        flash_set("error", "Failed to add email (maybe duplicate).");
        redirect("user_profile.php");
    }

    if ($action === "delete_email") {
        $email = trim((string)($_POST["email"] ?? ""));
        $stmt = $conn->prepare("DELETE FROM Owner_Email WHERE owner_id=? AND email=?");
        $stmt->bind_param("is", $ownerId, $email);
        $stmt->execute();
        flash_set("success", "Email removed.");
        redirect("user_profile.php");
    }

    flash_set("error", "Invalid action.");
    redirect("user_profile.php");
}

$owner = null;
$stmt = $conn->prepare("SELECT first_name, last_name, dob FROM Flat_Owner WHERE owner_id = ? LIMIT 1");
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();

$mobiles = [];
$stmt = $conn->prepare("SELECT mobile_no FROM Owner_Mobile WHERE owner_id = ? ORDER BY mobile_no");
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$res = $stmt->get_result();
while ($res && ($row = $res->fetch_assoc())) {
    $mobiles[] = $row["mobile_no"];
}

$emails = [];
$stmt = $conn->prepare("SELECT email FROM Owner_Email WHERE owner_id = ? ORDER BY email");
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$res = $stmt->get_result();
while ($res && ($row = $res->fetch_assoc())) {
    $emails[] = $row["email"];
}

layout_start("Profile", "user_profile");
?>

<div class="notice-card">
    <h4>Personal Details</h4>
    <form method="POST" action="user_profile.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_profile">

        <label><b>First Name</b></label>
        <input type="text" name="first_name" value="<?php echo h((string)($owner["first_name"] ?? "")); ?>" required>

        <label><b>Last Name</b></label>
        <input type="text" name="last_name" value="<?php echo h((string)($owner["last_name"] ?? "")); ?>" required>

        <label><b>Date of Birth</b></label>
        <input type="date" name="dob" value="<?php echo h((string)($owner["dob"] ?? "")); ?>" required>

        <button type="submit" style="margin-top:10px;">Save</button>
    </form>
</div>

<div class="notice-card">
    <h4>Mobile Numbers</h4>
    <form method="POST" action="user_profile.php" style="margin-bottom:10px;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add_mobile">
        <input type="text" name="mobile_no" placeholder="Add mobile number" required>
        <button type="submit" style="margin-top:10px;">Add</button>
    </form>

    <?php if (count($mobiles) === 0): ?>
        <p>No mobile numbers added.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Mobile</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($mobiles as $m): ?>
                <tr>
                    <td><?php echo h((string)$m); ?></td>
                    <td>
                        <form method="POST" action="user_profile.php" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_mobile">
                            <input type="hidden" name="mobile_no" value="<?php echo h((string)$m); ?>">
                            <button type="submit" class="logout-btn" style="padding:6px 12px;border-radius:10px;">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="notice-card">
    <h4>Email Addresses</h4>
    <form method="POST" action="user_profile.php" style="margin-bottom:10px;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add_email">
        <input type="email" name="email" placeholder="Add email address" required>
        <button type="submit" style="margin-top:10px;">Add</button>
    </form>

    <?php if (count($emails) === 0): ?>
        <p>No emails added.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Email</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($emails as $e): ?>
                <tr>
                    <td><?php echo h((string)$e); ?></td>
                    <td>
                        <form method="POST" action="user_profile.php" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_email">
                            <input type="hidden" name="email" value="<?php echo h((string)$e); ?>">
                            <button type="submit" class="logout-btn" style="padding:6px 12px;border-radius:10px;">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
layout_end();

