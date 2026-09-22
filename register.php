<?php
session_start();
include "db.php";
include "smtp_mailer.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $wants_developer = isset($_POST["wants_developer"]) ? 1 : 0;
    $role = ($wants_developer == 1) ? "developer" : "user";

    $check_sql = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {

        $existing_user = mysqli_fetch_assoc($check_result);

        if ($existing_user["email_verified"] == 0) {
            $new_otp = rand(100000, 999999);
            mysqli_query($conn, "UPDATE users SET verify_token = '$new_otp' WHERE id = " . $existing_user["id"]);

            $subject = "Your BugTracker verification code";
            $body = "Hi " . $existing_user["name"] . ",<br><br>Your new verification code is: <strong>$new_otp</strong>";
            send_email($email, $subject, $body);

            $_SESSION["pending_verify_id"] = $existing_user["id"];
            header("Location: verifyemail.php");
            exit;

        } else {
            $error = "An account with this email already exists.";
        }

    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $otp_code = rand(100000, 999999);

        $insert_sql = "INSERT INTO users (name, email, password_hash, role, wants_developer, verify_token, email_verified) 
                       VALUES ('$name', '$email', '$password_hash', '$role', $wants_developer, '$otp_code', 0)";

        if (mysqli_query($conn, $insert_sql)) {

            $new_user_id = mysqli_insert_id($conn);

            $subject = "Your BugTracker verification code";
            $body = "Hi $name,<br><br>Your verification code is: <strong>$otp_code</strong><br><br>
                    Enter this code on the verification page to activate your account.";

            send_email($email, $subject, $body);

            $_SESSION["pending_verify_id"] = $new_user_id;

            header("Location: verifyemail.php");
            exit;

        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - BugTracker</title>
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-page">

    <div class="login-wrapper">
        <p class="login-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

        <h2>Create Account</h2>
        <p class="login-subtext">Sign up to get started</p>

        <?php if ($error != "") { ?>
            <p class="register-error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Enter your name" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create a password" required>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="wants_developer" value="1">
                    I would like to be a Developer (fix bugs)
                </label>
            </div>

            <button type="submit" class="login-btn-submit">Sign Up</button>
        </form>

        <p class="login-footer-text">Already have an account? <a href="login.php" class="blue-text">Login</a></p>
    </div>

</body>
</html>