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

$name = $_SESSION["name"];
$role = $_SESSION["role"];

// Global stats - across ALL issues, not just one user's
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM issues");
$total_count = mysqli_fetch_assoc($total_result)["total"];

$open_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issues WHERE status = 'Open'"))["total"];
$inprogress_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issues WHERE status = 'In Progress'"))["total"];
$resolved_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issues WHERE status = 'Resolved'"))["total"];
$closed_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issues WHERE status = 'Closed'"))["total"];

$high_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issues WHERE priority IN ('High','Critical')"))["total"];
$medium_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issues WHERE priority = 'Medium'"))["total"];
$low_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issues WHERE priority = 'Low'"))["total"];

// Total users
$users_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))["total"];

// Recent issues - ALL issues, not filtered by user
$issues_sql = "SELECT * FROM issues ORDER BY created_at DESC LIMIT 10";
$issues_result = mysqli_query($conn, $issues_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - BugTracker</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="app-layout">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <p class="sidebar-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

            <div class="sidebar-links">
                <a href="admindashboard.php" class="sidebar-link active">Dashboard</a>
                <a href="issue.php" class="sidebar-link">All Issues</a>
                <a href="admin_users.php" class="sidebar-link">Manage Users</a>
                <a href="auditlog.php" class="sidebar-link">Activity Log</a>
                <a href="comingsoon.php" class="sidebar-link">Projects</a>
                <a href="comingsoon.php" class="sidebar-link">Settings</a>
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
            <h2 class="dashboard-title">Admin Dashboard</h2>

            <div class="stats-row">
                <div class="stat-card">
                    <p class="stat-label">Total Issues</p>
                    <p class="stat-number"><?php echo $total_count; ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Open</p>
                    <p class="stat-number stat-red"><?php echo $open_count; ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">In Progress</p>
                    <p class="stat-number stat-blue"><?php echo $inprogress_count; ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Resolved</p>
                    <p class="stat-number stat-green"><?php echo $resolved_count; ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Total Users</p>
                    <p class="stat-number"><?php echo $users_count; ?></p>
                </div>
            </div>

            <div class="mid-row">
                <div class="priority-box">
                    <h3 class="section-heading">Issues by Priority</h3>

                    <div class="priority-line">
                        <span class="dot dot-red"></span> High/Critical
                        <span class="priority-count"><?php echo $high_count; ?></span>
                    </div>
                    <div class="priority-line">
                        <span class="dot dot-orange"></span> Medium
                        <span class="priority-count"><?php echo $medium_count; ?></span>
                    </div>
                    <div class="priority-line">
                        <span class="dot dot-green"></span> Low
                        <span class="priority-count"><?php echo $low_count; ?></span>
                    </div>
                </div>

                <div class="status-box">
                    <h3 class="section-heading">Issues by Status</h3>
                    <div class="status-mini-grid">
                        <div class="status-mini-card">
                            <p class="stat-number stat-blue"><?php echo $open_count; ?></p>
                            <p class="stat-label">Open</p>
                        </div>
                        <div class="status-mini-card">
                            <p class="stat-number stat-orange"><?php echo $inprogress_count; ?></p>
                            <p class="stat-label">In Progress</p>
                        </div>
                        <div class="status-mini-card">
                            <p class="stat-number stat-green"><?php echo $resolved_count; ?></p>
                            <p class="stat-label">Resolved</p>
                        </div>
                        <div class="status-mini-card">
                            <p class="stat-number stat-gray"><?php echo $closed_count; ?></p>
                            <p class="stat-label">Closed</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-box">
                <h3 class="section-heading">Recent Issues (All Users)</h3>

                <?php if (mysqli_num_rows($issues_result) == 0) { ?>
                    <p class="empty-text">No issues yet.</p>
                <?php } else { ?>
                    <table class="issue-table">
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php while ($issue = mysqli_fetch_assoc($issues_result)) { ?>
                        <tr>
                            <td><?php echo $issue["title"]; ?></td>
                            <td><?php echo $issue["type"]; ?></td>
                            <td><?php echo $issue["priority"]; ?></td>
                            <td><?php echo $issue["status"]; ?></td>
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