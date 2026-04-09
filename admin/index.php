<?php
session_start();
require_once dirname(__FILE__) . '/../includes/db_connect.php';

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
                <a href="index.php" class="admin-logo-link">
                    <img src="../includes/website_pic/logo.png" alt="COMPUTRONIUM Logo" class="admin-logo-img">
                    <h1>COMPUTRONIUM Admin</h1>
                </a>
            </div>

            <!-- Admin Navigation -->
            <nav class="admin-nav">
                <ul class="admin-nav-menu">
                    <li><a href="index.php" class="admin-nav-link active">Dashboard</a></li>
                    <li><a href="manage_product.php" class="admin-nav-link">Products</a></li>
                    <li><a href="view_order.php" class="admin-nav-link">Orders</a></li>
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

                if ($result = $conn->query($productsQuery)) {
                    $row = $result->fetch_assoc();
                    $productCount = $row['count'];
                }

                if ($result = $conn->query($ordersQuery)) {
                    $row = $result->fetch_assoc();
                    $orderCount = $row['count'];
                }

                if ($result = $conn->query($totalRevenueQuery)) {
                    $row = $result->fetch_assoc();
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
                            if ($result = $conn->query($catQuery)) {
                                $row = $result->fetch_assoc();
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

    <!-- Footer -->
    <footer class="admin-footer">
        <div class="footer-container">
            <p>&copy; 2026 COMPUTRONIUM. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
