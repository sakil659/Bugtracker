<?php
session_start();
include "db.php";
include "smtp_mailer.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $wants_developer = isset($_POST["wants_developer"]) ? 1 : 0;
    $role = ($wants_developer == 1) ? "developer" : "user";

    // Only add verify_expires column if it does not exist yet (safe, runs once)
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'verify_expires'");
    if (mysqli_num_rows($col_check) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN verify_expires DATETIME NULL AFTER verify_token");
    }

    // 1) Name cannot start with a number or symbol. First letter must be A-Z.
    $first_letter = substr($name, 0, 1);
    $is_small = ($first_letter >= "a" && $first_letter <= "z");
    $is_big = ($first_letter >= "A" && $first_letter <= "Z");
    if ($first_letter >= "0" && $first_letter <= "9") {
        $error = "Name cannot start with a number.";
    } elseif (!$is_small && !$is_big) {
        $error = "Name cannot start with a symbol.";
    } else {
        // Check password using simple loops only (no preg_match, beginner friendly)

        // 2) Password must be at least 6 characters long
        $pass_ok = true;
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
            $pass_ok = false;
        }

        // 3) Password must contain at least one number (0-9)
        $has_number = false;
        for ($i = 0; $i < strlen($password); $i++) {
            $ch = $password[$i];
            if ($ch >= "0" && $ch <= "9") {
                $has_number = true;
                break;
            }
        }
        if ($pass_ok && !$has_number) {
            $error = "Password must contain at least one number (0-9).";
            $pass_ok = false;
        }

        // 4) Password must contain at least one symbol (anything that is not a letter or number)
        $has_symbol = false;
        for ($i = 0; $i < strlen($password); $i++) {
            $ch = $password[$i];
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

    $check_sql = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {

        $existing_user = mysqli_fetch_assoc($check_result);

        if ($existing_user["email_verified"] == 0) {
            // Make a new 6-digit OTP using a simple loop + rand()
            $new_otp = "";
            for ($i = 0; $i < 6; $i++) {
                $new_otp = $new_otp . rand(0, 9);
            }
            mysqli_query($conn, "UPDATE users SET verify_token = '$new_otp', verify_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = " . $existing_user["id"]);

            $subject = "Your BugTracker verification code";
            $body = "Hi " . $existing_user["name"] . ",<br><br>Your new verification code is: <strong>$new_otp</strong><br><br>This code will expire in 5 minutes.";
            send_email($email, $subject, $body);

            $_SESSION["pending_verify_id"] = $existing_user["id"];
            header("Location: verifyemail.php");
            exit;

        } else {
            $error = "An account with this email already exists.";
        }

    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Make a 6-digit OTP using a simple loop + rand()
        // Each loop adds one random digit (0-9), so after 6 loops we have 6 digits.
        $otp_code = "";
        for ($i = 0; $i < 6; $i++) {
            $otp_code = $otp_code . rand(0, 9);
        }

        $insert_sql = "INSERT INTO users (name, email, password_hash, role, wants_developer, verify_token, verify_expires, email_verified)
                       VALUES ('$name', '$email', '$password_hash', '$role', $wants_developer, '$otp_code', DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)";

        if (mysqli_query($conn, $insert_sql)) {

            $new_user_id = mysqli_insert_id($conn);

            $subject = "Your BugTracker verification code";
            $body = "Hi $name,<br><br>Your verification code is: <strong>$otp_code</strong><br><br>
                    Enter this code on the verification page to activate your account.<br>
                    This code will expire in 5 minutes.";

            send_email($email, $subject, $body);

            $_SESSION["pending_verify_id"] = $new_user_id;

            header("Location: verifyemail.php");
            exit;

        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
    } // end if pass_ok
    } // end name-check else
} // end POST check
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - BugTracker</title>
    <link rel="stylesheet" href="login.css">
    <style>
        /* Small live hint text under the inputs (plain CSS, no library) */
        .hint-text { font-size: 13px; color: rgb(107, 114, 128); margin: 6px 0 0 0; }
        .hint-ok { color: green; }
        .hint-bad { color: rgb(239, 68, 68); }
        #password-hints p { font-size: 13px; margin: 4px 0; color: rgb(107, 114, 128); }
    </style>
</head>
<body class="login-page">

    <div class="login-wrapper">
        <p class="login-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

        <h2>Create Account</h2>
        <p class="login-subtext">Sign up to get started</p>

        <?php if ($error != "") { ?>
            <p class="register-error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="register.php" id="register-form">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" id="fullname" placeholder="Enter your name" required pattern="[A-Za-z][A-Za-z0-9 ]*" title="Name cannot start with a number or symbol.">
                <p id="name-hint" class="hint-text"></p>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" placeholder="Create a password" required>
                <div id="password-hints">
                    <p id="hint-length">• At least 6 characters long</p>
                    <p id="hint-number">• Contains at least one number (0-9)</p>
                    <p id="hint-symbol">• Contains at least one symbol (like ! @ # $ %)</p>
                </div>
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

<script>
// Simple live checking while the user is typing (plain JavaScript, no library)

// 1) Name check: first letter cannot be a number or symbol
document.getElementById("fullname").onkeyup = function() {
    var name_value = this.value;
    var name_hint = document.getElementById("name-hint");
    if (name_value == "") {
        name_hint.innerHTML = "";
    } else if (name_value[0] >= "0" && name_value[0] <= "9") {
        name_hint.innerHTML = "Name cannot start with a number.";
        name_hint.className = "hint-text hint-bad";
    } else if (!((name_value[0] >= "a" && name_value[0] <= "z") || (name_value[0] >= "A" && name_value[0] <= "Z"))) {
        name_hint.innerHTML = "Name cannot start with a symbol.";
        name_hint.className = "hint-text hint-bad";
    } else {
        name_hint.innerHTML = "Good, name starts with a letter.";
        name_hint.className = "hint-text hint-ok";
    }
};

// 2) Password check: 6 characters + number + symbol
document.getElementById("password").onkeyup = function() {
    var pass = this.value;

    // length check
    if (pass.length >= 6) {
        document.getElementById("hint-length").style.color = "green";
    } else {
        document.getElementById("hint-length").style.color = "rgb(239, 68, 68)";
    }

    // number check (does it contain 0-9?)
    var has_number = false;
    for (var i = 0; i < pass.length; i++) {
        if (pass[i] >= "0" && pass[i] <= "9") {
            has_number = true;
        }
    }
    if (has_number) {
        document.getElementById("hint-number").style.color = "green";
    } else {
        document.getElementById("hint-number").style.color = "rgb(239, 68, 68)";
    }

    // symbol check (anything that is not a letter or number)
    var has_symbol = false;
    for (var j = 0; j < pass.length; j++) {
        var ch = pass[j];
        var is_letter = (ch >= "a" && ch <= "z") || (ch >= "A" && ch <= "Z");
        var is_number = (ch >= "0" && ch <= "9");
        if (!is_letter && !is_number) {
            has_symbol = true;
        }
    }
    if (has_symbol) {
        document.getElementById("hint-symbol").style.color = "green";
    } else {
        document.getElementById("hint-symbol").style.color = "rgb(239, 68, 68)";
    }
};
</script>

</body>
</html>