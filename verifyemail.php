<?php
session_start();
include "db.php";

$message = "";

if (!isset($_SESSION["pending_verify_id"])) {
    header("Location: register.php");
    exit;
}

$pending_id = $_SESSION["pending_verify_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_code = trim($_POST["otp_code"]);

    $check_sql = "SELECT * FROM users WHERE id = $pending_id AND verify_token = '$entered_code'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 1) {

        $update_sql = "UPDATE users SET email_verified = 1, verify_token = NULL WHERE id = $pending_id";
        mysqli_query($conn, $update_sql);

        unset($_SESSION["pending_verify_id"]);

        header("Location: login.php?verified=1");
        exit;

    } else {
        $message = "Incorrect code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email - BugTracker</title>
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-page">

    <div class="login-wrapper">
        <p class="login-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

        <h2>Verify Your Email</h2>
        <p class="login-subtext">Enter the 6-digit code we sent to your email</p>

        <?php if ($message != "") { ?>
            <p class="login-error"><?php echo $message; ?></p>
        <?php } ?>

        <form method="POST" action="verifyemail.php">
            <div class="form-group">
                <label>Verification Code</label>
                <input type="text" name="otp_code" placeholder="Enter 6-digit code" required maxlength="6">
            </div>
            <button type="submit" class="login-btn-submit">Verify</button>
        </form>
    </div>

</body>
</html>