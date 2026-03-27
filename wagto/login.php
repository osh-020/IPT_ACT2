<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin credentials
$ADMIN_CREDENTIALS = [
    ['username' => 'admin', 'password' => 'admin123']
];

// Demo customers (seed data)
$DEMO_CUSTOMERS = [
    ['id' => 1, 'email' => 'john@example.com', 'password' => 'pass123', 'name' => 'John Doe', 'phone' => '09123456789', 'address' => '123 Tech St, Manila'],
    ['id' => 2, 'email' => 'jane@example.com', 'password' => 'pass123', 'name' => 'Jane Smith', 'phone' => '09987654321', 'address' => '456 PC Ave, Quezon City'],
];

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header("Location: ./admin/dashboard.php");
    exit();
}

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in']) {
    header("Location: ./client/index.php");
    exit();
}

// Initialize registered customers in session if not exists
if (!isset($_SESSION['all_customers'])) {
    $_SESSION['all_customers'] = $DEMO_CUSTOMERS;
}

$loginError = '';
$userType = ''; // Will be 'admin' or 'customer'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginAs = $_POST['login_as'] ?? 'auto'; // auto-detect by default

    // Auto-detect if username/email looks like admin or customer
    if ($loginAs === 'auto') {
        // If input contains @ it's likely email (customer), otherwise check as admin username
        if (strpos($username, '@') !== false) {
            $email = $username;
            $loginAs = 'customer';
        } elseif ($username) {
            $loginAs = 'admin';
        }
    }

    // Try admin login
    if ($loginAs === 'admin' || (!$email && $username)) {
        if (!$username) {
            $loginError = 'Please enter username or email!';
        } else {
            $adminFound = false;
            foreach ($ADMIN_CREDENTIALS as $admin) {
                if ($admin['username'] === $username && $admin['password'] === $password) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $admin['username'];
                    header("Location: ./admin/dashboard.php");
                    exit();
                }
            }
            if (!$adminFound) {
                $loginError = 'Invalid admin credentials!';
            }
        }
    }
    // Try customer login
    elseif ($loginAs === 'customer' || $email) {
        if (!$email || !$password) {
            $loginError = 'Please enter email and password!';
        } else {
            $customer = null;
            foreach ($_SESSION['all_customers'] as $c) {
                if ($c['email'] === $email && $c['password'] === $password) {
                    $customer = $c;
                    break;
                }
            }

            if ($customer) {
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_name'] = $customer['name'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_phone'] = $customer['phone'];
                $_SESSION['customer_address'] = $customer['address'];
                header("Location: ./client/index.php");
                exit();
            } else {
                $loginError = 'Invalid email or password!';
            }
        }
    } else {
        $loginError = 'Please enter your credentials!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0d0d0f;
            --surface: #141417;
            --surface2: #1c1c21;
            --border: #2a2a32;
            --accent: #e8ff47;
            --accent2: #47d4ff;
            --text: #f0f0f0;
            --muted: #888;
            --danger: #ff4747;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }

        .login-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 3rem;
        }

        .logo {
            font-family: 'Space Mono', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .logo span { color: var(--accent); }

        .subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 1rem 0;
            font-size: 0.9rem;
            font-weight: 600;
            border-bottom: 2px solid transparent;
            transition: all 0.15s;
        }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .tab-btn:hover {
            color: var(--text);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
        }

        input {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            padding: 0.8rem;
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus {
            border-color: var(--accent);
        }

        .error-message {
            background: rgba(255, 71, 71, 0.1);
            border: 1px solid var(--danger);
            border-radius: 8px;
            color: var(--danger);
            padding: 0.8rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .btn-login {
            width: 100%;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 0.9rem;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(232,255,71,0.3);
        }

        .demo-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.8rem;
            color: var(--muted);
        }

        .demo-section strong {
            color: var(--accent);
        }

        .demo-credentials {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.8rem;
            margin-top: 0.8rem;
            font-size: 0.75rem;
        }

        .demo-credentials p {
            margin: 0.25rem 0;
        }

        .links {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.85rem;
        }

        .links a {
            color: var(--accent);
            text-decoration: none;
            transition: opacity 0.15s;
        }

        .links a:hover {
            opacity: 0.8;
        }

        .or-divider {
            text-align: center;
            color: var(--muted);
            margin: 1.5rem 0;
            font-size: 0.8rem;
        }

        .guest-btn {
            width: 100%;
            background: var(--surface2);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.8rem;
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
        }

        .guest-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="logo">VOLT<span>CORE</span></div>
            <p class="subtitle">Login to Your Account</p>

            <?php if ($loginError): ?>
                <div class="error-message">✗ <?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>

            <!-- TABS -->
            <div class="tabs">
                <button type="button" class="tab-btn active" onclick="switchTab('customer', this)">👤 Customer</button>
                <button type="button" class="tab-btn" onclick="switchTab('admin', this)">⚙️ Admin</button>
            </div>

            <!-- CUSTOMER LOGIN -->
            <div id="customer-tab" class="tab-content active">
                <form method="POST">
                    <div class="form-group">
                        <label for="customer-email">Email Address</label>
                        <input type="email" id="customer-email" name="email" placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label for="customer-password">Password</label>
                        <input type="password" id="customer-password" name="password" placeholder="Enter password">
                    </div>

                    <input type="hidden" name="login_as" value="customer">
                    <button type="submit" class="btn-login">LOGIN</button>

                    <div class="or-divider">or</div>
                    <a href="../client/index.php" class="guest-btn" style="display: block; text-decoration: none; text-align: center;">Continue as Guest</a>

                    <div class="links" style="margin-top: 1.5rem;">
                        Don't have an account? <a href="../client/register.php">Register here</a>
                    </div>

                    <div class="demo-section">
                        <p><strong>Demo Customer Accounts:</strong></p>
                        <div class="demo-credentials">
                            <p><strong>Account 1:</strong> john@example.com / pass123</p>
                            <p><strong>Account 2:</strong> jane@example.com / pass123</p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ADMIN LOGIN -->
            <div id="admin-tab" class="tab-content">
                <form method="POST">
                    <div class="form-group">
                        <label for="admin-username">Username</label>
                        <input type="text" id="admin-username" name="username" placeholder="Enter username">
                    </div>

                    <div class="form-group">
                        <label for="admin-password">Password</label>
                        <input type="password" id="admin-password" name="password" placeholder="Enter password">
                    </div>

                    <input type="hidden" name="login_as" value="admin">
                    <button type="submit" class="btn-login">LOGIN AS ADMIN</button>

                    <div class="demo-section">
                        <p><strong>Demo Admin Account:</strong></p>
                        <div class="demo-credentials">
                            <p><strong>Username:</strong> admin</p>
                            <p><strong>Password:</strong> admin123</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab, btn) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

            // Show selected tab
            document.getElementById(tab + '-tab').classList.add('active');
            btn.classList.add('active');
        }

        // Handle tab parameter from URL (e.g., ?tab=admin)
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            
            if (tab === 'admin') {
                const adminBtn = document.querySelector('[onclick="switchTab(\'admin\', this)"]');
                if (adminBtn) {
                    switchTab('admin', adminBtn);
                    document.getElementById('admin-username').focus();
                }
            }
        });
    </script>
</body>
</html>
