<?php
session_start();
require_once dirname(__FILE__) . '/../includes/db_connect.php';
require_once dirname(__FILE__) . '/../includes/admin_notifications.php';

// Get admin notification count
$adminUnreadCount = getAdminUnreadNotificationsCount($conn);

// Since no login required, just proceed
// In production, you would check session/auth here
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
                    <li><a href="dashboard.php" class="admin-nav-link active">Dashboard</a></li>
                    <li><a href="manage_product.php" class="admin-nav-link">Products</a></li>
                    <li><a href="view_order.php" class="admin-nav-link">Orders</a></li>
                    <li><a href="notifications.php" class="admin-nav-link">Notifications<?php if ($adminUnreadCount > 0): ?> <span style="display: inline-block; background: #ff4444; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; font-size: 12px; margin-left: 5px;"><?php echo $adminUnreadCount; ?></span><?php endif; ?></a></li>
                </ul>
            </nav>

            <!-- Admin Actions -->
            <div class="admin-actions">
                <a href="upload_product.php" class="admin-btn-primary">New Product</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-container">
            <!-- Dashboard Header -->
            <section class="dashboard-header">
                <h2>Admin Dashboard</h2>
            </section>

            <!-- Stats Grid -->
            <section class="stats-grid">
                <?php
                // Get statistics from database
                $productsQuery = "SELECT COUNT(*) as count FROM products";
                $ordersQuery = "SELECT COUNT(*) as count FROM orders";
                $totalRevenueQuery = "SELECT SUM(total) as total FROM orders WHERE order_status = 'Completed'";

                $productCount = 0;
                $orderCount = 0;
                $totalRevenue = 0;

                if ($result = mysqli_query($conn, $productsQuery)) {
                    $row = mysqli_fetch_assoc($result);
                    $productCount = $row['count'];
                }

                if ($result = mysqli_query($conn, $ordersQuery)) {
                    $row = mysqli_fetch_assoc($result);
                    $orderCount = $row['count'];
                }

                if ($result = mysqli_query($conn, $totalRevenueQuery)) {
                    $row = mysqli_fetch_assoc($result);
                    $totalRevenue = $row['total'] ? number_format($row['total'], 2) : '0.00';
                }
                ?>

                <!-- Total Products Stat -->
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-content">
                        <h3>Total Products</h3>
                        <p class="stat-number"><?php echo $productCount; ?></p>
                        <a href="manage_product.php" class="stat-link">View All</a>
                    </div>
                </div>

                <!-- Total Orders Stat -->
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-content">
                        <h3>Total Orders</h3>
                        <p class="stat-number"><?php echo $orderCount; ?></p>
                        <a href="view_order.php" class="stat-link">View All</a>
                    </div>
                </div>

                <!-- Total Revenue Stat -->
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-content">
                        <h3>Total Revenue</h3>
                        <p class="stat-number">₱<?php echo $totalRevenue; ?></p>
                        <a href="view_order.php" class="stat-link">Details</a>
                    </div>
                </div>

                <!-- Categories Stat -->
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-content">
                        <h3>Categories</h3>
                        <p class="stat-number">
                            <?php
                            $catQuery = "SELECT COUNT(DISTINCT category) as count FROM products";
                            if ($result = mysqli_query($conn, $catQuery)) {
                                $row = mysqli_fetch_assoc($result);
                                echo $row['count'];
                            }
                            ?>
                        </p>
                        <a href="manage_product.php" class="stat-link">Browse</a>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="actions-grid">
                    <div class="action-card">
                        <div class="action-icon"></div>
                        <h4>Add New Product</h4>
                        <p>Create a new product listing</p>
                        <a href="upload_product.php" class="action-btn">Go</a>
                    </div>
                    <div class="action-card">
                        <div class="action-icon"></div>
                        <h4>Manage Products</h4>
                        <p>Edit or delete existing products</p>
                        <a href="manage_product.php" class="action-btn">Go</a>
                    </div>
                    <div class="action-card">
                        <div class="action-icon"></div>
                        <h4>View Orders</h4>
                        <p>Check and manage customer orders</p>
                        <a href="view_order.php" class="action-btn">Go</a>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
