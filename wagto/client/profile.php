<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logout customer
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$isLoggedIn = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE — My Profile</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
        }

        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 64px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: 'Space Mono', monospace;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent);
        }

        .logo span { color: var(--text); }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            color: var(--text);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.15s;
        }

        .nav-link:hover {
            color: var(--accent);
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #000;
        }

        .profile-info h2 {
            font-family: 'Space Mono', monospace;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            color: var(--muted);
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }

        .info-section {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-title {
            font-family: 'Space Mono', monospace;
            font-size: 0.9rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-item label {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
        }

        .info-item p {
            font-size: 0.95rem;
            margin-top: 0.25rem;
        }

        .not-logged-in {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--muted);
        }

        .not-logged-in h2 {
            font-family: 'Space Mono', monospace;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .btn-login {
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 0.8rem 1.5rem;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1rem;
            transition: transform 0.15s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .btn-logout {
            background: #ff4747;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            transition: opacity 0.15s;
        }

        .btn-logout:hover {
            opacity: 0.9;
        }

        .btn-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn:hover {
            background: var(--accent);
            color: #000;
            border-color: var(--accent);
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">VOLT<span>CORE</span></div>
        <div class="nav-right">
            <a href="../client/index.php" class="nav-link">Shop</a>
            <a href="../client/cart.php" class="nav-link">Cart</a>
            <a href="../client/profile.php" class="nav-link" style="color: var(--accent); font-weight: 600;">Profile</a>
            <?php if (!$isLoggedIn): ?>
                <a href="../login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php if ($isLoggedIn): ?>
            <div class="profile-box">
                <div class="profile-header">
                    <div class="profile-avatar">👤</div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($_SESSION['customer_name']); ?></h2>
                        <p><?php echo htmlspecialchars($_SESSION['customer_email']); ?></p>
                        <p>Customer ID: #<?php echo $_SESSION['customer_id']; ?></p>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <div class="info-title">Personal Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <p><?php echo htmlspecialchars($_SESSION['customer_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($_SESSION['customer_email']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($_SESSION['customer_phone']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Member Since</label>
                        <p>March 2026</p>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <div class="info-title">Shipping Address</div>
                <div class="info-item">
                    <label>Address</label>
                    <p><?php echo htmlspecialchars($_SESSION['customer_address']); ?></p>
                </div>
            </div>

            <div class="btn-actions">
                <a href="../client/cart.php" class="btn">🛒 View Cart</a>
                <a href="../client/index.php" class="btn">🛍️ Continue Shopping</a>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="btn-logout">LOGOUT</button>
                </form>
            </div>
        <?php else: ?>
            <div class="not-logged-in">
                <h2>Not Logged In</h2>
                <p>Please login to view your profile information</p>
                <a href="../login.php"><button class="btn-login">LOGIN NOW</button></a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
