<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_stmt = $conn->prepare("SELECT full_name, email, username, age, gender, civil_status, mobile_number, address, zip_code, created_at FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

?>

<?php include '../includes/header.php'; ?>

<main class="main-content">
    <div class="dashboard-container">
        <h2>My Account Dashboard</h2>

        <div class="dashboard-grid">
            <!-- User Profile Section -->
            <section class="dashboard-section profile-section">
                <h3>Profile Information</h3>
                <div class="profile-info">
                    <div class="info-row">
                        <span class="label">Full Name:</span>
                        <span class="value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Username:</span>
                        <span class="value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Age:</span>
                        <span class="value"><?php echo htmlspecialchars($user['age']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Gender:</span>
                        <span class="value"><?php echo htmlspecialchars($user['gender']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Civil Status:</span>
                        <span class="value"><?php echo htmlspecialchars($user['civil_status']); ?></span>
                    </div>
                </div>
            </section>

            <!-- Contact Information Section -->
            <section class="dashboard-section contact-section">
                <h3>Contact Information</h3>
                <div class="contact-info">
                    <div class="info-row">
                        <span class="label">Mobile:</span>
                        <span class="value"><?php echo htmlspecialchars($user['mobile_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Address:</span>
                        <span class="value"><?php echo htmlspecialchars($user['address']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Zip Code:</span>
                        <span class="value"><?php echo htmlspecialchars($user['zip_code']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Member Since:</span>
                        <span class="value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </section>
        </div>

        <!-- Order History Section -->
        <section class="dashboard-section order-section">
            <h3>Order History</h3>
            <div class="order-placeholder">
                <p>Track your orders</p>
                <p>View all your past orders and their status</p>
                <a href="view_orders.php" class="btn btn-primary">View Orders</a>
            </div>
        </section>

        <!-- Settings Section -->
        <section class="dashboard-section settings-section">
            <h3>Account Settings</h3>
            <div class="settings-links">
                <a href="edit_profile.php" class="btn btn-secondary">Edit Profile</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </section>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
