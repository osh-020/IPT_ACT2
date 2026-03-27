<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'];
$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $errorMessage = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        // Here you could save to database or send email
        $successMessage = "Thank you for your inquiry! We will get back to you soon.";
        // Clear form fields after success
        $name = $email = $subject = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Computronium</title>
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

    <!-- Contact Content -->
    <main>
        <section>
            <h2>Contact Us</h2>
            <p>Have a question or inquiry? We'd love to hear from you. Fill out the form below and we'll get back to you as soon as possible.</p>
        </section>

        <!-- Contact Information -->
        <section>
            <h3>Our Contact Information</h3>
            <p><strong>Email:</strong> info@computronium.com</p>
            <p><strong>Phone:</strong> +63 (2) 1234-5678</p>
            <p><strong>Address:</strong> 123 Tech Street, Manila, Philippines</p>
            <p><strong>Business Hours:</strong> Monday - Friday, 9:00 AM - 6:00 PM</p>
        </section>

        <!-- Messages -->
        <?php if ($successMessage): ?>
            <div style="color: #4caf50; background-color: #1c2a1c; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div style="color: #ff4747; background-color: #2a1c1c; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Contact Form -->
        <section>
            <h3>Send us a Message</h3>
            <form method="POST" action="contact.php">
                <div>
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $name ?? ''; ?>" required>
                </div>

                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $email ?? ''; ?>" required>
                </div>

                <div>
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" value="<?php echo $subject ?? ''; ?>" required>
                </div>

                <div>
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="6" required><?php echo $message ?? ''; ?></textarea>
                </div>

                <button type="submit">Send Message</button>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Computronium. All rights reserved.</p>
        <p>Contact: info@computronium.com</p>
    </footer>

</body>
</html>
