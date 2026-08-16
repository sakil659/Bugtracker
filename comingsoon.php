<?php
session_start();
include "db.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION["name"];
$role = $_SESSION["role"];
$user_id = $_SESSION["user_id"];

$theme_sql = "SELECT theme FROM users WHERE id = $user_id";
$theme_result = mysqli_query($conn, $theme_sql);
$theme = mysqli_fetch_assoc($theme_result)["theme"];
$css_file = ($theme == "dark") ? "dashboard-dark.css" : "dashboard.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coming Soon - BugTracker</title>
    <link rel="stylesheet" href="<?php echo $css_file; ?>">
</head>
<body>
    <div class="app-layout">
        <div class="sidebar">
            <p class="sidebar-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

<div class="sidebar-links">
    <?php if ($role == "admin") { ?>
        <a href="admindashboard.php" class="sidebar-link">Dashboard</a>
        <a href="issue.php" class="sidebar-link">All Issues</a>
        <a href="admin_users.php" class="sidebar-link">Manage Users</a>
        <a href="auditlog.php" class="sidebar-link">Activity Log</a>
        <a href="comingsoon.php" class="sidebar-link active">Projects</a>
        <a href="settings.php" class="sidebar-link">Settings</a>
    <?php } else { ?>
        <a href="dashboard.php" class="sidebar-link">Dashboard</a>
        <a href="issue.php" class="sidebar-link">Issues</a>
        <a href="dashboard.php?view=mine" class="sidebar-link">My Issues</a>
        <a href="createissue.php" class="sidebar-link">+ Create Issue</a>
        <a href="comingsoon.php" class="sidebar-link active">Projects</a>
        <a href="auditlog.php" class="sidebar-link">Activity</a>
        <a href="settings.php" class="sidebar-link">Settings</a>
    <?php } ?>
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

        <div class="main-content">
            <div class="recent-box" style="text-align:center; padding: 60px 20px;">
                <h2 class="dashboard-title">Coming Soon</h2>
                <p class="empty-text">This feature is planned for a future update.</p>
            </div>
        </div>
    </div>
</body>
</html>