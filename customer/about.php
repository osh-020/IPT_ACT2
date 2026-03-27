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
    <title>About - Computronium</title>
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

    <!-- About Content -->
    <main>
        <section>
            <h2>About Computronium</h2>
            <p>Computronium is a leading provider of premium computer hardware and components. Since our founding, we've been dedicated to providing our customers with the highest quality products and exceptional service.</p>
        </section>

        <section>
            <h3>Our Mission</h3>
            <p>To empower enthusiasts, gamers, and professionals with top-tier computer components and excellent customer support.</p>
        </section>

        <section>
            <h3>Our Values</h3>
            <ul>
                <li><strong>Quality:</strong> We only offer the best components from trusted manufacturers.</li>
                <li><strong>Customer Focus:</strong> Your satisfaction is our priority.</li>
                <li><strong>Integrity:</strong> We operate with transparency and honesty.</li>
                <li><strong>Innovation:</strong> We stay current with the latest technology trends.</li>
            </ul>
        </section>

        <section>
            <h3>Our Story</h3>
            <p>Founded in 2020, Computronium started as a small shop with a big passion for computer hardware. Today, we serve thousands of customers worldwide, providing them with quality components and expert advice.</p>
        </section>

        <section>
            <h3>What We Offer</h3>
            <ul>
                <li>CPUs and Processors</li>
                <li>Graphics Cards (GPUs)</li>
                <li>Memory (RAM)</li>
                <li>Storage Solutions (SSD, HDD)</li>
                <li>Motherboards</li>
                <li>Power Supplies</li>
                <li>Cooling Systems</li>
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
