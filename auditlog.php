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

// Admin sees all activity, User only sees activity on issues they reported
if ($role == "admin") {
    $log_sql = "SELECT activity_log.*, users.name FROM activity_log 
                JOIN users ON activity_log.user_id = users.id 
                ORDER BY activity_log.created_at DESC";
} else {
    $log_sql = "SELECT activity_log.*, users.name FROM activity_log 
                JOIN users ON activity_log.user_id = users.id 
                WHERE activity_log.user_id = $user_id
                ORDER BY activity_log.created_at DESC";
}
$log_result = mysqli_query($conn, $log_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log - BugTracker</title>
    <link rel="stylesheet" href="dashboard.css">
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
                    <a href="auditlog.php" class="sidebar-link active">Activity Log</a>
                    <a href="comingsoon.php" class="sidebar-link">Projects</a>
                    <a href="comingsoon.php" class="sidebar-link">Settings</a>
                <?php } else { ?>
                    <a href="dashboard.php" class="sidebar-link">Dashboard</a>
                    <a href="issue.php" class="sidebar-link">Issues</a>
                    <a href="dashboard.php?view=mine" class="sidebar-link">My Issues</a>
                    <a href="createissue.php" class="sidebar-link">+ Create Issue</a>
                    <a href="comingsoon.php" class="sidebar-link">Projects</a>
                    <a href="auditlog.php" class="sidebar-link active">Activity</a>
                     <a href="comingsoon.php" class="sidebar-link">Settings</a>
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

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <h2 class="dashboard-title">Activity Log</h2>

            <div class="recent-box">
                <?php if (mysqli_num_rows($log_result) == 0) { ?>
                    <p class="empty-text">No activity recorded yet.</p>
                <?php } else { ?>
                    <table class="issue-table">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Date/Time</th>
                        </tr>
                        <?php while ($log = mysqli_fetch_assoc($log_result)) { ?>
                        <tr>
                            <td><?php echo $log["name"]; ?></td>
                            <td><?php echo $log["action"]; ?></td>
                            <td><?php echo $log["created_at"]; ?></td>
                        </tr>
                        <?php } ?>
                    </table>
                <?php } ?>
            </div>

        </div>
    </div>
</body>
</html>