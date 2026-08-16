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
$issue_id = $_GET["id"];

$theme_sql = "SELECT theme FROM users WHERE id = $user_id";
$theme_result = mysqli_query($conn, $theme_sql);
$theme = mysqli_fetch_assoc($theme_result)["theme"];
$css_file = ($theme == "dark") ? "dashboard-dark.css" : "dashboard.css";
// Handle status change - Admin only
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["new_status"])) {

    if ($role == "admin") {
        $new_status = $_POST["new_status"];

        $update_sql = "UPDATE issues SET status = '$new_status' WHERE id = $issue_id";
        mysqli_query($conn, $update_sql);

        $log_action = "Changed issue #$issue_id status to $new_status";
        $log_sql = "INSERT INTO activity_log (user_id, action) VALUES ($user_id, '$log_action')";
        mysqli_query($conn, $log_sql);
    }

    header("Location: issuedetail.php?id=$issue_id");
    exit;
}

// Handle new comment - any logged in user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["comment_text"])) {
    $comment_text = $_POST["comment_text"];

    $comment_sql = "INSERT INTO comments (issue_id, user_id, comment) VALUES ($issue_id, $user_id, '$comment_text')";
    mysqli_query($conn, $comment_sql);

    header("Location: issuedetail.php?id=$issue_id");
    exit;
}

// Get the issue details
$issue_sql = "SELECT * FROM issues WHERE id = $issue_id";
$issue_result = mysqli_query($conn, $issue_sql);
$issue = mysqli_fetch_assoc($issue_result);

// Get comments for this issue
$comments_sql = "SELECT comments.*, users.name FROM comments 
                  JOIN users ON comments.user_id = users.id 
                  WHERE comments.issue_id = $issue_id 
                  ORDER BY comments.created_at ASC";
$comments_result = mysqli_query($conn, $comments_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue Detail - BugTracker</title>
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
                    <a href="issue.php" class="sidebar-link active">All Issues</a>
                    <a href="admin_users.php" class="sidebar-link">Manage Users</a>
                    <a href="auditlog.php" class="sidebar-link">Activity Log</a>
                    <a href="comingsoon.php" class="sidebar-link">Projects</a>
                    <a href="settings.php" class="sidebar-link">Settings</a>
                <?php } else { ?>
                    <a href="dashboard.php" class="sidebar-link">Dashboard</a>
                    <a href="issue.php" class="sidebar-link active">Issues</a>
                    <a href="dashboard.php?view=mine" class="sidebar-link">My Issues</a>
                    <a href="createissue.php" class="sidebar-link">+ Create Issue</a>
                    <a href="comingsoon.php" class="sidebar-link">Projects</a>
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

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <a href="issue.php" class="sidebar-link back-link">&larr; Back to Issues</a>

            <div class="issue-detail-box">
                <h2><?php echo $issue["title"]; ?></h2>
                <p class="issue-meta">Type: <?php echo $issue["type"]; ?> | Priority: <?php echo $issue["priority"]; ?> | Status: <?php echo $issue["status"]; ?></p>
                <p class="issue-description"><?php echo $issue["description"]; ?></p>
                <?php if ($issue["attachment"] != "") { ?>
                    <img src="uploads/<?php echo $issue["attachment"]; ?>" class="issue-screenshot" alt="Bug screenshot">
                <?php } ?>

                <?php if ($role == "admin") { ?>
                    <form method="POST" action="issuedetail.php?id=<?php echo $issue_id; ?>" class="status-form">
                        <label>Change Status</label>
                        <select name="new_status">
                            <option value="Open" <?php if ($issue["status"] == "Open") echo "selected"; ?>>Open</option>
                            <option value="In Progress" <?php if ($issue["status"] == "In Progress") echo "selected"; ?>>In Progress</option>
                            <option value="Resolved" <?php if ($issue["status"] == "Resolved") echo "selected"; ?>>Resolved</option>
                            <option value="Closed" <?php if ($issue["status"] == "Closed") echo "selected"; ?>>Closed</option>
                        </select>
                        <button type="submit" class="btn-blue">Update Status</button>
                    </form>
                <?php } else { ?>
                    <p class="empty-text" style="text-align:left;">Only an Admin can change the status of this issue.</p>
                <?php } ?>
            </div>

            <div class="comments-box">
                <h3 class="section-heading">Comments</h3>

                <?php while ($comment = mysqli_fetch_assoc($comments_result)) { ?>
                    <div class="comment-item">
                        <p class="comment-author"><?php echo $comment["name"]; ?></p>
                        <p class="comment-text"><?php echo $comment["comment"]; ?></p>
                    </div>
                <?php } ?>

                <form method="POST" action="issuedetail.php?id=<?php echo $issue_id; ?>" class="comment-form">
                    <textarea name="comment_text" rows="3" placeholder="Write a comment..." required></textarea>
                    <button type="submit" class="btn-blue">Post Comment</button>
                </form>
            </div>

        </div>
    </div>
</body>
</html>