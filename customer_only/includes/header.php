<?php
// Session already started in calling pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Parts Store</title>
    <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <!-- Logo -->
            <div class="logo">
                <h1>COMPUTRONIUM</h1>
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
                    <input type="text" name="search" placeholder="Search products..." class="search-input">
                    <button type="submit" class="search-btn">Search</button>
                </form>

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
                        echo "<a href='logout.php' class='user-link logout'>Logout</a>";
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
