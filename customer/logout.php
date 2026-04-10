<?php
session_start();

// Handle logout
if (isset($_SESSION['user_id'])) {
    // Destroy the session
    session_destroy();
}

// Redirect to home page
header("Location: home.php");
exit;
?>

