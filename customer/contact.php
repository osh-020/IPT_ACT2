<?php
session_start();

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $subject = isset($_POST['subject']) ? htmlspecialchars(trim($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

    // Simple validation
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        // Display success message
        $success_message = "Thank you for your message! We'll get back to you soon.";
    }
}

include 'header.php';
?>

<main class="main-content">
    <div class="contact-container">
        <section class="contact-hero">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you. Get in touch with us today!</p>
        </section>

        <div class="contact-wrapper">
            <!-- Contact Form -->
            <section class="contact-form-section">
                <h2>Send us a Message</h2>

                <?php
                if (!empty($success_message)) {
                    echo "<div class='success-message'>$success_message</div>";
                }
                ?>

                <form method="POST" action="contact.php" class="contact-form">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" placeholder="Your Full Name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="your@email.com" required>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" placeholder="What is this about?" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="6" placeholder="Tell us more..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </section>

            <!-- Contact Information -->
            <section class="contact-info-section">
                <h2>Contact Information</h2>

                <div class="contact-info-box">
                    <h3>Address</h3>
                    <p>COMPUTRONIUM Store<br>Lingayen, Pangasinan<br>Philippines</p>
                </div>

                <div class="contact-info-box">
                    <h3>Phone</h3>
                    <p>
                        <strong>Support:</strong> +63-75-123-4567<br>
                        <strong>Sales:</strong> +63-75-123-4568
                    </p>
                </div>

                <div class="contact-info-box">
                    <h3>Email</h3>
                    <p>
                        <strong>Support:</strong> support@computronium.ph<br>
                        <strong>Sales:</strong> sales@computronium.ph<br>
                        <strong>General:</strong> info@computronium.ph
                    </p>
                </div>

                <div class="contact-info-box">
                    <h3>Business Hours</h3>
                    <p>
                        <strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM (Philippine Time)<br>
                        <strong>Saturday:</strong> 10:00 AM - 4:00 PM<br>
                        <strong>Sunday:</strong> Closed
                    </p>
                </div>

                <div class="contact-info-box">
                    <h3>Follow Us</h3>
                    <p>
                        Facebook | Twitter | Instagram | YouTube
                    </p>
                </div>
            </section>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

