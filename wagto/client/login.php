<?php
// Redirect to unified login page (customer tab)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Location: ../login.php?tab=customer");
exit();
?>
