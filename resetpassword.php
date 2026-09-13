<?php
session_start();
include "db.php";

$token = isset($_GET["token"]) ? $_GET["token"] : "";
$error = "";
$success = "";
$valid_token = false;

if ($token != "") {
    $check_sql = "SELECT * FROM users WHERE reset_token = '$token' AND reset_expires > NOW()";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 1) {
        $valid_token = true;
        $user = mysqli_fetch_assoc($check_result);
    } else {
        $error = "This reset link is invalid or has expired.";
    }
} else {
    $error = "No reset token provided.";
}

if ($valid_token && $_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if ($new_password != $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $update_sql = "UPDATE users SET password_hash = '$new_hash', reset_token = NULL, reset_expires = NULL WHERE id = " . $user["id"];
        mysqli_query($conn, $update_sql);

        $success = "Your password has been reset. You can now log in.";
        $valid_token = false; // hide the form after success
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - BugTracker</title>
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-page">

    <div class="login-wrapper">
        <p class="login-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

        <h2>Reset Password</h2>

        <?php if ($error != "") { ?>
            <p class="login-error"><?php echo $error; ?></p>
        <?php } ?>
        <?php if ($success != "") { ?>
            <p class="login-success"><?php echo $success; ?></p>
        <?php } ?>

        <?php if ($valid_token) { ?>
            <form method="POST" action="resetpassword.php?token=<?php echo $token; ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="login-btn-submit">Reset Password</button>
            </form>
        <?php } ?>

        <p class="login-footer-text"><a href="login.php" class="blue-text">Back to Login</a></p>
    </div>

</body>
</html>