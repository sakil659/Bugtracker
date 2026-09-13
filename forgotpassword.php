<?php
session_start();
include "db.php";
include "smtp_mailer.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $check_sql = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 1) {

        $user = mysqli_fetch_assoc($check_result);

        // Generate a random reset token, valid for 1 hour (let MySQL calculate the expiry)
        $reset_token = bin2hex(random_bytes(32));

        $update_sql = "UPDATE users SET reset_token = '$reset_token', reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = " . $user["id"];
        mysqli_query($conn, $update_sql);

        $reset_link = "http://localhost/bugtracker/resetpassword.php?token=" . $reset_token;

        $subject = "Reset your BugTracker password";
        $body = "Hi " . $user["name"] . ",<br><br>Click the link below to reset your password:<br>
                 <a href='$reset_link'>$reset_link</a><br><br>
                 This link will expire in 1 hour. If you didn't request this, ignore this email.";

        send_email($email, $subject, $body);

        $message = "If an account exists with that email, a reset link has been sent.";

    } else {
        // Same message shown either way, so people can't guess which emails are registered
        $message = "If an account exists with that email, a reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - BugTracker</title>
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-page">

    <div class="login-wrapper">
        <p class="login-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

        <h2>Forgot Password</h2>
        <p class="login-subtext">Enter your email to receive a reset link</p>

        <?php if ($message != "") { ?>
            <p class="login-success"><?php echo $message; ?></p>
        <?php } else { ?>
            <form method="POST" action="forgotpassword.php">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <button type="submit" class="login-btn-submit">Send Reset Link</button>
            </form>
        <?php } ?>

        <p class="login-footer-text">Remembered your password? <a href="login.php" class="blue-text">Login</a></p>
    </div>

</body>
</html>