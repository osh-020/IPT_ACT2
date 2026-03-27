<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect to login if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: /IPT_ACT2/login.php?tab=admin");
        exit();
    }
}

// Login admin
function adminLogin($username, $password) {
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        return true;
    }
    return false;
}

// Logout admin
function adminLogout() {
    session_destroy();
}
?>
