<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit; 
}

$name = $_SESSION["name"];
$role = $_SESSION["role"];

// Basic filter by status (optional, using a dropdown)
$filter_status = isset($_GET["status"]) ? $_GET["status"] : "";

if ($filter_status != "") {
    $issues_sql = "SELECT issues.*, users.name as assignee_name 
                    FROM issues 
                    LEFT JOIN users ON issues.assignee_id = users.id 
                    WHERE issues.status = '$filter_status' 
                    ORDER BY issues.created_at DESC";
} else {
    $issues_sql = "SELECT issues.*, users.name as assignee_name 
                    FROM issues 
                    LEFT JOIN users ON issues.assignee_id = users.id 
                    ORDER BY issues.created_at DESC";
}

$issues_result = mysqli_query($conn, $issues_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issues - BugTracker</title>
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
        <a href="issue.php" class="sidebar-link active">All Issues</a>
        <a href="admin_users.php" class="sidebar-link">Manage Users</a>
        <a href="auditlog.php" class="sidebar-link">Activity Log</a>
        <a href="comingsoon.php" class="sidebar-link">Projects</a>
        <a href="comingsoon.php" class="sidebar-link">Settings</a>
    <?php } else { ?>
        <a href="dashboard.php" class="sidebar-link">Dashboard</a>
        <a href="issue.php" class="sidebar-link active">Issues</a>
        <a href="dashboard.php?view=mine" class="sidebar-link">My Issues</a>
        <a href="createissue.php" class="sidebar-link">+ Create Issue</a>
        <a href="comingsoon.php" class="sidebar-link">Projects</a>
        <a href="auditlog.php" class="sidebar-link">Activity</a>
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
            <h2 class="dashboard-title">All Issues</h2>

            <form method="GET" action="issue.php" style="margin-bottom: 20px;">
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Open" <?php if ($filter_status == "Open") echo "selected"; ?>>Open</option>
                    <option value="In Progress" <?php if ($filter_status == "In Progress") echo "selected"; ?>>In Progress</option>
                    <option value="Resolved" <?php if ($filter_status == "Resolved") echo "selected"; ?>>Resolved</option>
                    <option value="Closed" <?php if ($filter_status == "Closed") echo "selected"; ?>>Closed</option>
                </select>
            </form>

            <div class="recent-box">
                <?php if (mysqli_num_rows($issues_result) == 0) { ?>
                    <p class="empty-text">No issues found. <a href="createissue.php">Create one</a></p>
                <?php } else { ?>
                    <table class="issue-table">
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assignee</th>
                            <th>Action</th>
                        </tr>
                        <?php while ($issue = mysqli_fetch_assoc($issues_result)) { ?>
                        <tr>
                            <td><?php echo $issue["title"]; ?></td>
                            <td><?php echo $issue["type"]; ?></td>
                            <td><?php echo $issue["priority"]; ?></td>
                            <td><?php echo $issue["status"]; ?></td>
                            <td><?php echo $issue["assignee_name"] ? $issue["assignee_name"] : "Unassigned"; ?></td>
                            <td><a href="issuedetail.php?id=<?php echo $issue["id"]; ?>">View</a></td>
                        </tr>
                        <?php } ?>
                    </table>
                <?php } ?>
            </div>

        </div>
    </div>
</body>
</html>