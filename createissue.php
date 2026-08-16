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

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = $_POST["title"];
    $description = $_POST["description"];
    $type = $_POST["type"];
    $priority = $_POST["priority"];
    $reporter_id = $_SESSION["user_id"];
    $attachment_name = "";

    // Handle screenshot upload (optional)
    if (isset($_FILES["attachment"]) && $_FILES["attachment"]["name"] != "") {

        $file_tmp = $_FILES["attachment"]["tmp_name"];
        $file_name = time() . "_" . $_FILES["attachment"]["name"];
        $upload_path = "uploads/" . $file_name;

        move_uploaded_file($file_tmp, $upload_path);
        $attachment_name = $file_name;
    }

    $insert_sql = "INSERT INTO issues (title, description, type, priority, status, reporter_id, attachment) 
                   VALUES ('$title', '$description', '$type', '$priority', 'Open', $reporter_id, '$attachment_name')";

    if (mysqli_query($conn, $insert_sql)) {

        $new_issue_id = mysqli_insert_id($conn);
        $log_action = "Created issue #$new_issue_id: $title";
        $log_sql = "INSERT INTO activity_log (user_id, action) VALUES ($reporter_id, '$log_action')";
        mysqli_query($conn, $log_sql);

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Something went wrong. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Issue - BugTracker</title>
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
                    <a href="settings.php" class="sidebar-link">Settings</a>
                <?php } else { ?>
                    <a href="dashboard.php" class="sidebar-link">Dashboard</a>
                    <a href="issue.php" class="sidebar-link">Issues</a>
                    <a href="dashboard.php?view=mine" class="sidebar-link">My Issues</a>
                    <a href="createissue.php" class="sidebar-link active">+ Create Issue</a>
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
            <h2 class="dashboard-title">Report a New Issue</h2>

            <?php if ($error != "") { ?>
                <p class="login-error"><?php echo $error; ?></p>
            <?php } ?>

            <form method="POST" action="createissue.php" enctype="multipart/form-data" class="issue-form">

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="Short summary of the bug" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="5" placeholder="Describe the issue in detail" required></textarea>
                </div>

                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="Bug">Bug</option>
                        <option value="Feature">Feature</option>
                        <option value="Task">Task</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" required>
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Screenshot (optional)</label>
                    <input type="file" name="attachment" accept="image/*">
                </div>

                <button type="submit" class="btn-blue">Submit Issue</button>

            </form>

        </div>
    </div>
</body>
</html>