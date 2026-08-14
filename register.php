<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $role = "user";

    $check_sql = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $error = "An account with this email already exists.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $insert_sql = "INSERT INTO users (name, email, password_hash, role) 
                       VALUES ('$name', '$email', '$password_hash', '$role')";

        if (mysqli_query($conn, $insert_sql)) {
            header("Location: login.php");
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

            <button type="submit" class="login-btn-submit">Sign Up</button>
        </form>

        <p class="login-footer-text">Already have an account? <a href="login.php" class="blue-text">Login</a></p>
    </div>

</body>
</html>