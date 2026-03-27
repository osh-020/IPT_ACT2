<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to shop
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in']) {
    header("Location: ../client/index.php");
    exit();
}

// Initialize demo customers in session if not exists
if (!isset($_SESSION['all_customers'])) {
    $_SESSION['all_customers'] = [
        ['id' => 1, 'email' => 'john@example.com', 'password' => 'pass123', 'name' => 'John Doe', 'phone' => '09123456789', 'address' => '123 Tech St, Manila'],
        ['id' => 2, 'email' => 'jane@example.com', 'password' => 'pass123', 'name' => 'Jane Smith', 'phone' => '09987654321', 'address' => '456 PC Ave, Quezon City'],
    ];
}

$registerError = '';
$registerSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (!$name || !$email || !$password || !$confirmPassword || !$phone || !$address) {
        $registerError = 'All fields are required!';
    } elseif (strlen($password) < 6) {
        $registerError = 'Password must be at least 6 characters!';
    } elseif ($password !== $confirmPassword) {
        $registerError = 'Passwords do not match!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Invalid email format!';
    } else {
        // Check if email already exists
        $emailExists = false;
        foreach ($_SESSION['all_customers'] as $customer) {
            if ($customer['email'] === $email) {
                $emailExists = true;
                break;
            }
        }

        if ($emailExists) {
            $registerError = 'Email already registered!';
        } else {
            // Create new customer
            $newId = max(array_column($_SESSION['all_customers'], 'id')) + 1;
            $newCustomer = [
                'id' => $newId,
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ];

            $_SESSION['all_customers'][] = $newCustomer;

            // Auto-login the new customer
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_id'] = $newCustomer['id'];
            $_SESSION['customer_name'] = $newCustomer['name'];
            $_SESSION['customer_email'] = $newCustomer['email'];
            $_SESSION['customer_phone'] = $newCustomer['phone'];
            $_SESSION['customer_address'] = $newCustomer['address'];

            $registerSuccess = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE — Register</title>
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
            --success: #4caf50;
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

        .register-wrapper {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }

        .register-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2.5rem;
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
        }

        input, textarea {
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

        input:focus, textarea:focus {
            border-color: var(--accent);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
            grid-column: 1 / -1;
        }

        .error-message {
            background: rgba(255, 71, 71, 0.1);
            border: 1px solid var(--danger);
            border-radius: 8px;
            color: var(--danger);
            padding: 0.8rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            grid-column: 1 / -1;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success);
            border-radius: 8px;
            color: var(--success);
            padding: 1rem;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
            grid-column: 1 / -1;
        }

        .btn-register {
            grid-column: 1 / -1;
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

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(232,255,71,0.3);
        }

        .terms-check {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--muted);
        }

        .terms-check input[type="checkbox"] {
            width: auto;
            padding: 0;
            margin: 0;
        }

        .terms-check a {
            color: var(--accent);
            text-decoration: none;
        }

        .terms-check a:hover {
            text-decoration: underline;
        }

        .links {
            grid-column: 1 / -1;
            margin-top: 1rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.15s;
        }

        .links a:hover {
            opacity: 0.8;
        }

        .redirect-message {
            text-align: center;
            color: var(--muted);
            padding: 1rem;
            margin-top: 1rem;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-box">
            <div class="logo">VOLT<span>CORE</span></div>
            <p class="subtitle">Create Your Account</p>

            <form method="POST">
                <div class="form-grid">
                    <?php if ($registerError): ?>
                        <div class="error-message">✗ <?php echo htmlspecialchars($registerError); ?></div>
                    <?php endif; ?>

                    <?php if ($registerSuccess): ?>
                        <div class="success-message">
                            ✓ Account created successfully! Redirecting to shop...
                        </div>
                        <script>
                            setTimeout(() => {
                                window.location.href = '../client/index.php';
                            }, 2000);
                        </script>
                    <?php endif; ?>

                    <div class="form-group full">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" placeholder="Juan dela Cruz" required>
                    </div>

                    <div class="form-group full">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="your@email.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    </div>

                    <div class="form-group full">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="09123456789" required>
                    </div>

                    <div class="form-group full">
                        <label for="address">Shipping Address *</label>
                        <textarea id="address" name="address" placeholder="123 Main Street, City, Province" required></textarea>
                    </div>

                    <div class="terms-check">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms" style="margin: 0;">I agree to the <a href="#">Terms & Conditions</a></label>
                    </div>

                    <button type="submit" class="btn-register">CREATE ACCOUNT</button>

                    <div class="links">
                        Already have an account? <a href="../login.php">Login here</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
