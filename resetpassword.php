<?php
session_start();
include "db.php";
include "smtp_mailer.php";

$error = "";
$success = "";
$otp_mode = false;
$link_mode = false;
$user = null;

// OTP mode: user came from forgotpassword.php, id saved in session
if (isset($_SESSION["pending_reset_id"])) {
    $otp_mode = true;
    $pending_id = $_SESSION["pending_reset_id"];

    $user_sql = "SELECT * FROM users WHERE id = $pending_id";
    $user_result = mysqli_query($conn, $user_sql);
    $user = mysqli_fetch_assoc($user_result);

    if (!$user) {
        unset($_SESSION["pending_reset_id"]);
        header("Location: forgotpassword.php");
        exit;
    }

    // Resend a new OTP with loop + rand()
    if (isset($_POST["resend"])) {
        $new_otp = "";
        for ($i = 0; $i < 6; $i++) {
            $new_otp = $new_otp . rand(0, 9);
        }
        mysqli_query($conn, "UPDATE users SET reset_token = '$new_otp', reset_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = $pending_id");

        $subject = "Your new BugTracker password reset code";
        $body = "Hi " . $user["name"] . ",<br><br>Your new password reset code is: <strong>$new_otp</strong><br><br>This code will expire in 5 minutes.";
        send_email($user["email"], $subject, $body);

        $user_result = mysqli_query($conn, $user_sql);
        $user = mysqli_fetch_assoc($user_result);

        $success = "A new code has been sent to your email. It is valid for 5 minutes.";
    }

    // Verify OTP + save new password
    if (isset($_POST["reset_pass"])) {
        $entered_code = trim($_POST["otp_code"]);
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];

        $user_result = mysqli_query($conn, $user_sql);
        $user = mysqli_fetch_assoc($user_result);
        $saved_code = "";
        if (isset($user["reset_token"]) && $user["reset_token"] != "") {
            $saved_code = trim($user["reset_token"]);
        }

        if ($entered_code == "") {
            $error = "Please enter the code.";
        } else {
            // Check expiry FIRST using MySQL clock (same clock that set it)
            // After 5 minutes, any entry says expired, not incorrect
            $exp_sql = "SELECT * FROM users WHERE id = $pending_id AND reset_expires > NOW()";
            $exp_result = mysqli_query($conn, $exp_sql);
            if (mysqli_num_rows($exp_result) == 0) {
                $error = "OTP code has expired (valid for 5 minutes). Press Resend to get a new one.";
            } elseif ($entered_code != $saved_code) {
                $error = "Incorrect OTP code. Please try again.";
            } elseif ($new_password != $confirm_password) {
                $error = "Passwords do not match.";
            } else {
            // Check new password using simple loops only (beginner friendly)
            $pass_ok = true;
            if (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long.";
                $pass_ok = false;
            }
            $has_number = false;
            for ($i = 0; $i < strlen($new_password); $i++) {
                $ch = $new_password[$i];
                if ($ch >= "0" && $ch <= "9") {
                    $has_number = true;
                    break;
                }
            }
            if ($pass_ok && !$has_number) {
                $error = "Password must contain at least one number (0-9).";
                $pass_ok = false;
            }
            $has_symbol = false;
            for ($i = 0; $i < strlen($new_password); $i++) {
                $ch = $new_password[$i];
                $is_small = ($ch >= "a" && $ch <= "z");
                $is_big = ($ch >= "A" && $ch <= "Z");
                $is_num = ($ch >= "0" && $ch <= "9");
                if (!$is_small && !$is_big && !$is_num) {
                    $has_symbol = true;
                    break;
                }
            }
            if ($pass_ok && !$has_symbol) {
                $error = "Password must contain at least one symbol (like ! @ # $ %).";
                $pass_ok = false;
            }

            if ($pass_ok) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password_hash = '$new_hash', reset_token = NULL, reset_expires = NULL WHERE id = $pending_id");
                unset($_SESSION["pending_reset_id"]);
                header("Location: login.php?reset=1");
                exit;
            }
            } // expiry ok, password saved
        } // passwords match, checked expiry
    }
} else {
    // Old link mode: keep working for links sent before (resetpassword.php?token=...)
    $token = isset($_GET["token"]) ? $_GET["token"] : "";

    if ($token != "") {
        $check_sql = "SELECT * FROM users WHERE reset_token = '$token' AND reset_expires > NOW()";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) == 1) {
            $link_mode = true;
            $user = mysqli_fetch_assoc($check_result);
        } else {
            $error = "This reset link is invalid or has expired.";
        }
    } else {
        $error = "No reset code found. Please go to Forgot Password first.";
    }

    if ($link_mode && isset($_POST["reset_link_pass"])) {
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];

        if ($new_password != $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password_hash = '$new_hash', reset_token = NULL, reset_expires = NULL WHERE id = " . $user["id"]);
            $success = "Your password has been reset. You can now log in.";
            $link_mode = false;
        }
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

        <?php if ($otp_mode) { ?>
            <p class="login-subtext">Enter the 6-digit code from your email. It expires in 5 minutes.</p>
            <form method="POST" action="resetpassword.php">
                <div class="form-group">
                    <label>Reset Code</label>
                    <input type="text" name="otp_code" placeholder="Enter 6-digit code" required maxlength="6">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="reset_pass" class="login-btn-submit">Reset Password</button>
            </form>
            <form method="POST" action="resetpassword.php" style="margin-top: 12px;">
                <button type="submit" name="resend" class="login-btn-submit" style="background-color: rgb(107, 114, 128);">Resend Code</button>
            </form>
        <?php } elseif ($link_mode) { ?>
            <form method="POST" action="resetpassword.php?token=<?php echo $token; ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="reset_link_pass" class="login-btn-submit">Reset Password</button>
            </form>
        <?php } ?>

        <p class="login-footer-text"><a href="login.php" class="blue-text">Back to Login</a></p>
    </div>

</body>
</html>
