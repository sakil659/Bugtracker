<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Only Admin allowed here
if ($_SESSION["role"] != "admin") {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];
$role = $_SESSION["role"];

$theme_sql = "SELECT theme FROM users WHERE id = $user_id";
$theme_result = mysqli_query($conn, $theme_sql);
$theme = mysqli_fetch_assoc($theme_result)["theme"];
$css_file = ($theme == "dark") ? "dashboard-dark.css" : "dashboard.css";

// Handle role change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_role"])) {
    $target_user_id = $_POST["user_id"];
    $new_role = $_POST["new_role"];

    $update_sql = "UPDATE users SET role = '$new_role' WHERE id = $target_user_id";
    mysqli_query($conn, $update_sql);

    header("Location: admin_users.php");
    exit;
}

// Handle activate/deactivate
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["toggle_status"])) {
    $target_user_id = $_POST["user_id"];
    $current_status = $_POST["current_status"];

    $new_status = ($current_status == "active") ? "inactive" : "active";

    $update_sql = "UPDATE users SET status = '$new_status' WHERE id = $target_user_id";
    mysqli_query($conn, $update_sql);

    header("Location: admin_users.php");
    exit;
}

// Get all users
$users_sql = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - BugTracker</title>
    <link rel="stylesheet" href="<?php echo $css_file; ?>">
</head>
<body>
    <div class="app-layout">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <p class="sidebar-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

            <div class="sidebar-links">
                <a href="admindashboard.php" class="sidebar-link">Dashboard</a>
                <a href="issue.php" class="sidebar-link">All Issues</a>
                <a href="admin_users.php" class="sidebar-link active">Manage Users</a>
                <a href="auditlog.php" class="sidebar-link">Activity Log</a>
                <a href="comingsoon.php" class="sidebar-link">Projects</a>
                <a href="settings.php" class="sidebar-link">Settings</a>
            </div>

            <div class="sidebar-footer">
                <a href="logout.php" class="sidebar-link">Logout</a>
                <div class="sidebar-user">
                    <div class="user-avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                    <div>
                        <p class="user-name"><?php echo $name; ?></p>
                        <p class="user-role"><?php echo ucfirst($role); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <h2 class="dashboard-title">Manage Users</h2>

            <div class="recent-box">
                <table class="issue-table">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Change Role</th>
                        <th>Action</th>
                    </tr>
                    <?php while ($user = mysqli_fetch_assoc($users_result)) { ?>
                    <tr>
                        <td><?php echo $user["name"]; ?></td>
                        <td><?php echo $user["email"]; ?></td>
                        <td><?php echo ucfirst($user["role"]); ?></td>
                        <td><?php echo ucfirst($user["status"]); ?></td>
                        <td>
                            <form method="POST" action="admin_users.php" class="status-form">
                                <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                <select name="new_role">
                                    <option value="user" <?php if ($user["role"] == "user") echo "selected"; ?>>User</option>
                                    <option value="admin" <?php if ($user["role"] == "admin") echo "selected"; ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role" class="btn-blue">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="admin_users.php">
                                <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $user["status"]; ?>">
                                <button type="submit" name="toggle_status" class="btn-blue">
                                    <?php echo ($user["status"] == "active") ? "Deactivate" : "Activate"; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>

        </div>
    </div>
</body>
</html>