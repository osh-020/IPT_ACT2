<?php
session_start();
include '../includes/db_connect.php';

$error = '';
$redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'home.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: $redirect");
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
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect to intended page or home
            header("Location: $redirect");
            exit;
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<main class="main-content">
    <div class="auth-container">
        <div class="auth-box">
            <h2>Login to Your Account</h2>

            <?php
            if (!empty($error)) {
                echo "<div class='error-message'>$error</div>";
            }
            
            if ($redirect !== 'home.php') {
                echo "<div class='info-message'>Please log in to add items to your cart</div>";
            }
            ?>

            <form method="POST" action="login.php" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
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

<?php include '../includes/footer.php'; ?>
