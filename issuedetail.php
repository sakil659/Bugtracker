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

$theme_sql = "SELECT theme, profile_pic FROM users WHERE id = $user_id";
$theme_result = mysqli_query($conn, $theme_sql);
$theme_row = mysqli_fetch_assoc($theme_result);
$theme = $theme_row["theme"];
$profile_pic = "";
if (isset($theme_row["profile_pic"])) {
    $profile_pic = $theme_row["profile_pic"];
}
$css_file = ($theme == "dark") ? "dashboard-dark.css" : "dashboard.css";

// Handle assignment - Admin only
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["assign_to"])) {

    if ($role == "admin") {
        $assign_to = $_POST["assign_to"];

        if ($assign_to == "") {
            $update_sql = "UPDATE issues SET assignee_id = NULL WHERE id = $issue_id";
        } else {
            $update_sql = "UPDATE issues SET assignee_id = $assign_to WHERE id = $issue_id";
        }
        mysqli_query($conn, $update_sql);

        $log_action = "Updated assignment on issue #$issue_id";
        $log_sql = "INSERT INTO activity_log (user_id, action) VALUES ($user_id, '$log_action')";
        mysqli_query($conn, $log_sql);
    }

    header("Location: issuedetail.php?id=$issue_id");
    exit;
}

// Handle delete issue - Admin only
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_issue"])) {

    if ($role == "admin") {

        // Delete comments attached to this issue first
        $delete_comments_sql = "DELETE FROM comments WHERE issue_id = $issue_id";
        mysqli_query($conn, $delete_comments_sql);

        // Now safe to delete the issue itself
        $delete_sql = "DELETE FROM issues WHERE id = $issue_id";
        mysqli_query($conn, $delete_sql);

        $log_action = "Deleted issue #$issue_id";
        $log_sql = "INSERT INTO activity_log (user_id, action) VALUES ($user_id, '$log_action')";
        mysqli_query($conn, $log_sql);
    }

    header("Location: issue.php");
    exit;
}
// Handle status change - Admin can change any issue,
// Developer can only change an issue assigned to them
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["new_status"])) {

    $can_change = false;
    if ($role == "admin") {
        $can_change = true;
    } elseif ($role == "developer") {
        $owner_sql = "SELECT assignee_id FROM issues WHERE id = $issue_id";
        $owner_result = mysqli_query($conn, $owner_sql);
        $owner_row = mysqli_fetch_assoc($owner_result);
        if ($owner_row["assignee_id"] == $user_id) {
            $can_change = true;
        }
    }

    if ($can_change) {
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

// Get all active regular users for assignment dropdown
$users_sql = "SELECT id, name FROM users WHERE role = 'developer' AND status = 'active'";
$users_result = mysqli_query($conn, $users_sql);

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
                <?php } elseif ($role == "developer") { ?>
                    <a href="dashboard.php" class="sidebar-link">Dashboard</a>
                    <a href="issue.php" class="sidebar-link active">Issues</a>
                    <a href="dashboard.php?view=assigned" class="sidebar-link">My Assigned Bugs</a>
                    <a href="comingsoon.php" class="sidebar-link">Projects</a>
                    <a href="auditlog.php" class="sidebar-link">Activity</a>
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
                    <?php if ($profile_pic != "") { ?>
                        <img src="uploads/<?php echo $profile_pic; ?>" class="user-avatar-img">
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

                    <form method="POST" action="issuedetail.php?id=<?php echo $issue_id; ?>" class="status-form">
                        <label>Assign To</label>
                        <select name="assign_to">
                            <option value="">Unassigned</option>
                            <?php while ($u = mysqli_fetch_assoc($users_result)) { ?>
                                <option value="<?php echo $u["id"]; ?>" <?php if ($issue["assignee_id"] == $u["id"]) echo "selected"; ?>>
                                    <?php echo $u["name"]; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <button type="submit" class="btn-blue">Assign</button>
                    </form>

                    <form method="POST" action="issuedetail.php?id=<?php echo $issue_id; ?>" onsubmit="return confirm('Are you sure you want to delete this issue?');" style="margin-top:15px;">
                        <button type="submit" name="delete_issue" class="btn-blue" style="background-color: rgb(239,68,68);">Delete Issue</button>
                    </form>
                <?php } elseif ($role == "developer" && $issue["assignee_id"] == $user_id) { ?>
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
                <?php } elseif ($role == "developer") { ?>
                    <p class="empty-text" style="text-align:left;">This issue is not assigned to you.</p>
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