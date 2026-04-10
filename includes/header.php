<?php
// Session already started in calling pages
require_once dirname(__FILE__) . '/customer_notifications.php';
require_once dirname(__FILE__) . '/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMPUTRONIUM</title>
    <link rel="icon" type="image/png" href="../includes/website_pic/logo.png">
    <link rel="stylesheet" href="../includes/customer_style.css">
    <style>
        .notification-badge {
            position: relative;
            display: inline-block;
        }
        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .notification-link {
            color: inherit;
            text-decoration: none;
            margin-left: 15px;
        }
        .notification-link:hover {
            color: #007bff;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <!-- Logo -->
            <div class="logo">
                <a href="home.php" class="logo-link">
                    <img src="../includes/website_pic/logo.png" alt="COMPUTRONIUM Logo" class="logo-img">
                    <h1>COMPUTRONIUM</h1>
                </a>
            </div>

            <!-- Navigation Menu -->
            <nav class="nav-menu">
                <a href="home.php" class="nav-link">Home</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
            </nav>

            <!-- User Info & Cart -->
            <div class="navbar-right">
                <!-- Search Bar -->
                <form action="products.php" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="SEARCH" class="search-input">
                    <button type="submit" class="search-btn">SEARCH</button>
                </form>

                <!-- Notifications -->
                <?php
                if (isset($_SESSION['user_id'])) {
                    $unread_count = getUnreadNotificationsCount($_SESSION['user_id'], $conn);
                    echo '<a href="notifications.php" class="notification-link notification-badge">';
                    echo 'Notifications';
                    if ($unread_count > 0) {
                        echo '<span class="notification-dot">' . $unread_count . '</span>';
                    }
                    echo '</a>';
                }
                ?>

                <!-- Cart Icon -->
                <a href="cart.php" class="cart-link">
                    Cart
                    <?php
                    $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                    if ($cartCount > 0) {
                        echo "<span class='cart-count'>$cartCount</span>";
                    }
                    ?>
                </a>

                <!-- User Section -->
                <div class="user-section">
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo "<span class='user-name'>Hi, " . htmlspecialchars($_SESSION['username']) . "</span>";
                        echo "<a href='dashboard.php' class='user-link'>Profile</a>";
                    } else {
                        echo "<a href='login.php' class='user-link'>Login</a>";
                        echo "<a href='register.php' class='user-link'>Register</a>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Starts Below Header -->
