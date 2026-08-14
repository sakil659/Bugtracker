<?php
// db.php - Database connection file
// This file is included in every page that needs to talk to the database

$host = "localhost";       // XAMPP MySQL server address
$username = "root";        // default XAMPP username
$password = "";            // default XAMPP password (blank)
$database = "bugtracker";  // our database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>