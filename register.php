<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";

$errorMsg = flash_get("error");
$successMsg = flash_get("success");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Society Maintenance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

    <div class="modal active">
        <form class="modal-content" method="POST" action="register_process.php" autocomplete="off">
            <div class="imgcontainer">
                <img src="logo.jpg" alt="Society Logo" class="avatar">
                <h1>Create Account</h1>
            </div>

            <div class="container">
                <?php echo csrf_field(); ?>
                <label for="user_id"><b>User ID (number)</b></label>
                <input type="number" name="user_id" id="user_id" placeholder="Example: 1" required>

                <label for="username"><b>Username</b></label>
                <input type="text" name="username" id="username" placeholder="Choose a username" required>

                <label for="password"><b>Password</b></label>
                <input type="password" name="password" id="password" placeholder="Create a password" required>

                <label for="first_name"><b>First Name</b></label>
                <input type="text" name="first_name" id="first_name" placeholder="Your first name" required>

                <label for="last_name"><b>Last Name</b></label>
                <input type="text" name="last_name" id="last_name" placeholder="Your last name" required>

                <label for="dob"><b>Date of Birth</b></label>
                <input type="date" name="dob" id="dob" required>

                <div class="error" style="<?php echo $errorMsg ? '' : 'display:none;'; ?>"><?php echo $errorMsg ? h($errorMsg) : ''; ?></div>
                <div class="success" style="<?php echo $successMsg ? '' : 'display:none;'; ?>"><?php echo $successMsg ? h($successMsg) : ''; ?></div>

                <button type="submit">Register</button>
            </div>

            <div class="container footer">
                <span class="psw">Already have an account? <a href="login.php">Login</a></span>
            </div>
        </form>
    </div>

</body>
</html>
