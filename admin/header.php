<?php
require_once dirname(__FILE__) . '/../includes/db_connect.php';
require_once dirname(__FILE__) . '/../includes/admin_notifications.php';

// Get admin notification count
$adminUnreadCount = getAdminUnreadNotificationsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - COMPUTRONIUM</title>
    <link rel="icon" type="image/png" href="../includes/website_pic/logo.png">
    <link rel="stylesheet" href="../includes/admin_style.css">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <!-- Logo Section -->
            <div class="admin-logo">
                <a href="dashboard.php" class="admin-logo-link">
                    <img src="../includes/website_pic/logo.png" alt="COMPUTRONIUM Logo" class="admin-logo-img">
                    <h1>COMPUTRONIUM Admin</h1>
                </a>
            </div>

            <!-- Admin Navigation -->
            <nav class="admin-nav">
                <ul class="admin-nav-menu">
                    <li><a href="dashboard.php" class="admin-nav-link">Dashboard</a></li>
                    <li><a href="manage_product.php" class="admin-nav-link">Products</a></li>
                    <li><a href="view_order.php" class="admin-nav-link">Orders</a></li>
                    <li><a href="notifications.php" class="admin-nav-link">Notifications<?php if ($adminUnreadCount > 0): ?> <span style="color: #ff6b6b; font-weight: bold;">
 <?php echo $adminUnreadCount; ?></span><?php endif; ?></a></li>
                </ul>
            </nav>

            <!-- Admin Actions -->
            <div class="admin-actions">
                <a href="upload_product.php" class="admin-btn-primary">New Product</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
