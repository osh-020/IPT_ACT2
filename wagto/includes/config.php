<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voltcore_shop');

// Database connection (optional - for future use)
// Currently using PHP sessions for data storage
$conn = null;

// Uncomment below if you have a MySQL database set up
/*
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
*/

// Admin credentials (demo)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');
?>
