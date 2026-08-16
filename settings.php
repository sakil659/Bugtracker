<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];
$role = $_SESSION["role"];
$success = "";
$error = "";

// Get current user info
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Handle Edit Name
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_name"])) {
    $new_name = trim($_POST["new_name"]);

    $update_sql = "UPDATE users SET name = '$new_name' WHERE id = $user_id";
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION["name"] = $new_name;
        $success = "Name updated successfully.";
        $name = $new_name;
        $user["name"] = $new_name;
    } else {
        $error = "Could not update name.";
    }
}

// Handle Edit Email
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_email"])) {
    $new_email = trim($_POST["new_email"]);

    $check_sql = "SELECT id FROM users WHERE email = '$new_email' AND id != $user_id";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $error = "That email is already in use.";
    } else {
        $update_sql = "UPDATE users SET email = '$new_email' WHERE id = $user_id";
        if (mysqli_query($conn, $update_sql)) {
            $success = "Email updated successfully.";
            $user["email"] = $new_email;
        } else {
            $error = "Could not update email.";
        }
    }
}

// Handle Change Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $old_password = $_POST["old_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if (!password_verify($old_password, $user["password_hash"])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password != $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password_hash = '$new_hash' WHERE id = $user_id";
        if (mysqli_query($conn, $update_sql)) {
            $success = "Password changed successfully.";
        } else {
            $error = "Could not change password.";
        }
    }
}

// Handle Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_pic"])) {
    if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["name"] != "") {

        $file_tmp = $_FILES["profile_pic"]["tmp_name"];
        $file_name = "profile_" . time() . "_" . $_FILES["profile_pic"]["name"];
        $upload_path = "uploads/" . $file_name;

        move_uploaded_file($file_tmp, $upload_path);

        $update_sql = "UPDATE users SET profile_pic = '$file_name' WHERE id = $user_id";
        mysqli_query($conn, $update_sql);
        $user["profile_pic"] = $file_name;
        $success = "Profile picture updated.";
    }
}

// Handle Theme Toggle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["toggle_theme"])) {
    $new_theme = ($user["theme"] == "dark") ? "light" : "dark";

    $update_sql = "UPDATE users SET theme = '$new_theme' WHERE id = $user_id";
    mysqli_query($conn, $update_sql);
    $user["theme"] = $new_theme;
}

$css_file = ($user["theme"] == "dark") ? "dashboard-dark.css" : "dashboard.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - BugTracker</title>
    <link rel="stylesheet" href="<?php echo $css_file; ?>">
</head>
<body>
    <div class="app-layout">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <p class="sidebar-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

            <div class="sidebar-links">
                <?php if ($role == "admin") { ?>
                    <a href="admindashboard.php" class="sidebar-link">Dashboard</a>
                    <a href="issue.php" class="sidebar-link">All Issues</a>
                    <a href="admin_users.php" class="sidebar-link">Manage Users</a>
                    <a href="auditlog.php" class="sidebar-link">Activity Log</a>
                    <a href="comingsoon.php" class="sidebar-link">Projects</a>
                    <a href="settings.php" class="sidebar-link active">Settings</a>
                <?php } else { ?>
                    <a href="dashboard.php" class="sidebar-link">Dashboard</a>
                    <a href="issue.php" class="sidebar-link">Issues</a>
                    <a href="dashboard.php?view=mine" class="sidebar-link">My Issues</a>
                    <a href="createissue.php" class="sidebar-link">+ Create Issue</a>
                    <a href="comingsoon.php" class="sidebar-link">Projects</a>
                    <a href="auditlog.php" class="sidebar-link">Activity</a>
                    <a href="settings.php" class="sidebar-link active">Settings</a>
                <?php } ?>
            </div>

            <div class="sidebar-footer">
                <a href="logout.php" class="sidebar-link">Logout</a>
                <div class="sidebar-user">
                    <?php if ($user["profile_pic"]) { ?>
                        <img src="uploads/<?php echo $user["profile_pic"]; ?>" class="user-avatar-img">
                    <?php } else { ?>
                        <div class="user-avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                    <?php } ?>
                    <div>
                        <p class="user-name"><?php echo $name; ?></p>
                        <p class="user-role"><?php echo ucfirst($role); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <h2 class="dashboard-title">Settings</h2>

            <?php if ($success != "") { ?>
                <p class="login-success"><?php echo $success; ?></p>
            <?php } ?>
            <?php if ($error != "") { ?>
                <p class="login-error"><?php echo $error; ?></p>
            <?php } ?>

            <!-- PROFILE PICTURE -->
            <div class="issue-detail-box">
                <h3 class="section-heading">Profile Picture</h3>

                <?php if ($user["profile_pic"]) { ?>
                    <img src="uploads/<?php echo $user["profile_pic"]; ?>" class="profile-pic-preview">
                <?php } ?>

                <form method="POST" action="settings.php" enctype="multipart/form-data" style="margin-top:15px;">
                    <div class="form-group">
                        <input type="file" name="profile_pic" accept="image/*">
                    </div>
                    <button type="submit" name="update_pic" class="btn-blue">Upload Picture</button>
                </form>
            </div>

            <!-- EDIT NAME -->
            <div class="issue-detail-box">
                <h3 class="section-heading">Edit Name</h3>
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <input type="text" name="new_name" value="<?php echo $user["name"]; ?>" required>
                    </div>
                    <button type="submit" name="update_name" class="btn-blue">Save Name</button>
                </form>
            </div>

            <!-- EDIT EMAIL -->
            <div class="issue-detail-box">
                <h3 class="section-heading">Edit Email</h3>
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <input type="email" name="new_email" value="<?php echo $user["email"]; ?>" required>
                    </div>
                    <button type="submit" name="update_email" class="btn-blue">Save Email</button>
                </form>
            </div>

            <!-- CHANGE PASSWORD -->
            <div class="issue-detail-box">
                <h3 class="section-heading">Change Password</h3>
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="old_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-blue">Change Password</button>
                </form>
            </div>

            <!-- THEME -->
            <div class="issue-detail-box">
                <h3 class="section-heading">Appearance</h3>
                <p class="issue-meta">Current theme: <?php echo ucfirst($user["theme"]); ?></p>
                <form method="POST" action="settings.php" style="margin-top:10px;">
                    <button type="submit" name="toggle_theme" class="btn-blue">
                        Switch to <?php echo ($user["theme"] == "dark") ? "Light" : "Dark"; ?> Mode
                    </button>
                </form>
            </div>

        </div>
    </div>
</body>
</html>