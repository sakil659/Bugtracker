<?php
session_start();
include "db.php";
include "smtp_mailer.php";

$message = "";
$success = "";

// Only add verify_expires column if it does not exist yet (safe, runs once)
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'verify_expires'");
if (mysqli_num_rows($col_check) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN verify_expires DATETIME NULL AFTER verify_token");
}

if (!isset($_SESSION["pending_verify_id"])) {
    header("Location: register.php");
    exit;
}

$pending_id = $_SESSION["pending_verify_id"];

// Get the user so we can check the OTP and the expiry time
$user_sql = "SELECT * FROM users WHERE id = $pending_id";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    unset($_SESSION["pending_verify_id"]);
    header("Location: register.php");
    exit;
}

// If user pressed "Resend code", make a new OTP with loop + rand() and send email
if (isset($_POST["resend"])) {
    // Make a 6-digit OTP using a simple loop + rand()
    // Each loop adds one random digit (0-9), after 6 loops we have 6 digits.
    $new_otp = "";
    for ($i = 0; $i < 6; $i++) {
        $new_otp = $new_otp . rand(0, 9);
    }

    mysqli_query($conn, "UPDATE users SET verify_token = '$new_otp', verify_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = $pending_id");

    $subject = "Your new BugTracker verification code";
    $body = "Hi " . $user["name"] . ",<br><br>Your new verification code is: <strong>$new_otp</strong><br><br>This code will expire in 5 minutes.";
    send_email($user["email"], $subject, $body);

    // Refresh user data so expiry check uses the new time
    $user_result = mysqli_query($conn, $user_sql);
    $user = mysqli_fetch_assoc($user_result);

    $success = "A new code has been sent to your email. It is valid for 5 minutes.";
}

// If user pressed "Verify", check that the typed code matches the saved code
if (isset($_POST["verify"])) {
    $entered_code = trim($_POST["otp_code"]);

    // Refresh user data from database
    $user_result = mysqli_query($conn, $user_sql);
    $user = mysqli_fetch_assoc($user_result);
    $saved_code = "";
    if (isset($user["verify_token"]) && $user["verify_token"] != "") {
        $saved_code = trim($user["verify_token"]);
    }

    if ($entered_code == "" ) {
        $message = "Please enter the code.";
    } else {
        // Check expiry FIRST using MySQL clock (same clock that set it)
        // After expiry, any entry says expired, not incorrect
        $exp_sql = "SELECT * FROM users WHERE id = $pending_id AND verify_expires > NOW()";
        $exp_result = mysqli_query($conn, $exp_sql);
        if (mysqli_num_rows($exp_result) == 0) {
            $message = "OTP code has expired (valid for 5 minutes). Press Resend to get a new one.";
        } elseif ($entered_code != $saved_code) {
            // Code does not match (also covers old code after Resend)
            $message = "Incorrect OTP code. Please try again.";
        } else {
        // Code matches and is still valid, activate the account
        mysqli_query($conn, "UPDATE users SET email_verified = 1, verify_token = NULL, verify_expires = NULL WHERE id = $pending_id");

        unset($_SESSION["pending_verify_id"]);

        header("Location: login.php?verified=1");
        exit;
        }
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
        <p class="login-subtext">Enter the 6-digit code we sent to your email. It expires in 5 minutes.</p>

        <?php if ($message != "") { ?>
            <p class="login-error"><?php echo $message; ?></p>
        <?php } ?>
        <?php if ($success != "") { ?>
            <p class="login-success"><?php echo $success; ?></p>
        <?php } ?>

        <form method="POST" action="verifyemail.php">
            <div class="form-group">
                <label>Verification Code</label>
                <input type="text" name="otp_code" placeholder="Enter 6-digit code" required maxlength="6">
            </div>
            <button type="submit" name="verify" class="login-btn-submit">Verify</button>
        </form>

        <form method="POST" action="verifyemail.php" style="margin-top: 12px;">
            <button type="submit" name="resend" class="login-btn-submit" style="background-color: rgb(107, 114, 128);">Resend Code</button>
        </form>
    </div>

</body>
</html>
