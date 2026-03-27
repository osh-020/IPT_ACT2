<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Computronium</title>
</head>
<body>

    <!-- Navigation -->
    <nav>
        <h1>Computronium</h1>
        <ul>
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Home Content -->
    <main>
        <section>
            <h2>Welcome to Computronium</h2>
            <p>Your premier destination for high-quality computer hardware and components.</p>
        </section>

        <section>
            <h3>Featured Products</h3>
            <p>Browse our wide selection of CPUs, GPUs, RAM, and more.</p>
            <a href="shop.php">Shop Now</a>
        </section>

        <section>
            <h3>Why Choose Us?</h3>
            <ul>
                <li>High-quality components</li>
                <li>Competitive prices</li>
                <li>Fast shipping</li>
                <li>Expert customer support</li>
            </ul>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Computronium. All rights reserved.</p>
        <p>Contact: info@computronium.com</p>
    </footer>

</body>
</html>
