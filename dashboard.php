<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] == "admin") {
    header("Location: admindashboard.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];
$role = $_SESSION["role"];
// Check if this is the "My Issues" filtered view
$view = isset($_GET["view"]) ? $_GET["view"] : "all";

// ... rest of the file stays exactly the same
// Total issues related to this user
$total_sql = "SELECT COUNT(*) as total FROM issues WHERE reporter_id = $user_id OR assignee_id = $user_id";
$total_result = mysqli_query($conn, $total_sql);
$total_count = mysqli_fetch_assoc($total_result)["total"];

// Count by status
$open_sql = "SELECT COUNT(*) as total FROM issues WHERE (reporter_id = $user_id OR assignee_id = $user_id) AND status = 'Open'";
$open_count = mysqli_fetch_assoc(mysqli_query($conn, $open_sql))["total"];

$inprogress_sql = "SELECT COUNT(*) as total FROM issues WHERE (reporter_id = $user_id OR assignee_id = $user_id) AND status = 'In Progress'";
$inprogress_count = mysqli_fetch_assoc(mysqli_query($conn, $inprogress_sql))["total"];

$resolved_sql = "SELECT COUNT(*) as total FROM issues WHERE (reporter_id = $user_id OR assignee_id = $user_id) AND status = 'Resolved'";
$resolved_count = mysqli_fetch_assoc(mysqli_query($conn, $resolved_sql))["total"];

$closed_sql = "SELECT COUNT(*) as total FROM issues WHERE (reporter_id = $user_id OR assignee_id = $user_id) AND status = 'Closed'";
$closed_count = mysqli_fetch_assoc(mysqli_query($conn, $closed_sql))["total"];

// Count by priority
$high_sql = "SELECT COUNT(*) as total FROM issues WHERE (reporter_id = $user_id OR assignee_id = $user_id) AND priority IN ('High','Critical')";
$high_count = mysqli_fetch_assoc(mysqli_query($conn, $high_sql))["total"];

$medium_sql = "SELECT COUNT(*) as total FROM issues WHERE (reporter_id = $user_id OR assignee_id = $user_id) AND priority = 'Medium'";
$medium_count = mysqli_fetch_assoc(mysqli_query($conn, $medium_sql))["total"];

$low_sql = "SELECT COUNT(*) as total FROM issues WHERE (reporter_id = $user_id OR assignee_id = $user_id) AND priority = 'Low'";
$low_count = mysqli_fetch_assoc(mysqli_query($conn, $low_sql))["total"];

// Recent issues list
// Recent issues list - filtered if viewing "My Issues"
if ($view == "mine") {
    $issues_sql = "SELECT * FROM issues WHERE reporter_id = $user_id ORDER BY created_at DESC";
} else {
    $issues_sql = "SELECT * FROM issues WHERE reporter_id = $user_id OR assignee_id = $user_id ORDER BY created_at DESC LIMIT 10";
}
$issues_result = mysqli_query($conn, $issues_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - BugTracker</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="app-layout">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <p class="sidebar-logo">🪲 Bug<span class="blue-text">Tracker</span></p>

            <div class="sidebar-links">
                <a href="dashboard.php" class="sidebar-link <?php echo ($view == "all") ? "active" : ""; ?>">Dashboard</a>
                <a href="issue.php" class="sidebar-link">Issues</a>
                <a href="dashboard.php?view=mine" class="sidebar-link <?php echo ($view == "mine") ? "active" : ""; ?>">My Issues</a>
                <a href="createissue.php" class="sidebar-link">+ Create Issue</a>
                <a href="comingsoon.php" class="sidebar-link">Projects</a>
                <a href="auditlog.php" class="sidebar-link">Activity</a>
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
            <h2 class="dashboard-title">Dashboard</h2>

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
                <h3 class="section-heading"><?php echo ($view == "mine") ? "My Reported Issues" : "Recent Issues"; ?></h3>

                <?php if (mysqli_num_rows($issues_result) == 0) { ?>
                    <p class="empty-text">No issues yet. <a href="createissue.php">Create one</a></p>
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