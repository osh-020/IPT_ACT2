<?php
session_start();
include '../includes/db_connect.php';

$error = '';
$redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'home.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    // Sanitize redirect and ensure it's a valid local page
    $valid_pages = ['home.php', 'products.php', 'cart.php', 'dashboard.php', 'checkout.php'];
    $redirect_page = (in_array($redirect, $valid_pages)) ? $redirect : 'home.php';
    header("Location: " . $redirect_page);
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Username and password are required!";
    } else {
        // Check user in database
        $stmt = $conn->prepare("SELECT user_id, username, full_name, password FROM users WHERE username = ?");
        
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            // Debug: Log the attempt
            error_log("Login attempt - Username: $username, User found: " . ($user ? "YES" : "NO"));
            
            if ($user) {
                error_log("Password hash in DB: " . $user['password']);
                error_log("Password verify result: " . (password_verify($password, $user['password']) ? "PASS" : "FAIL"));
            }

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                error_log("Login successful for user: " . $user['username']);
                
                // Sanitize redirect and ensure it's a valid local page
                $valid_pages = ['home.php', 'products.php', 'cart.php', 'dashboard.php', 'checkout.php'];
                $redirect_page = (in_array($redirect, $valid_pages)) ? $redirect : 'home.php';
                header("Location: " . $redirect_page);
                exit;
            } else {
                if (!$user) {
                    $error = "User not found!";
                } else {
                    $error = "Invalid password!";
                }
                error_log("Login failed - " . $error);
            }
        }
    }
}
?>

<?php include 'header.php'; ?>

<main class="main-content">
    <div class="auth-container">
        <div class="auth-box">
            <h2>Login to Your Account</h2>

            <?php
            if (!empty($error)) {
                echo "<div class='error-message' style='background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb;'>" . htmlspecialchars($error) . "</div>";
            }
            
            if ($redirect !== 'home.php') {
                echo "<div class='info-message' style='background-color: #d1ecf1; color: #0c5460; padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #bee5eb;'>Please log in to add items to your cart</div>";
            }
            ?>

            <form method="POST" action="login.php" class="auth-form">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" minlength="5" maxlength="15" placeholder="Enter your username (5-15 characters)" title="Username must be 5-15 characters" required>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" minlength="8" maxlength="20" placeholder="Enter your password (8-20 characters)" title="Password must be 8-20 characters" required>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="home.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

