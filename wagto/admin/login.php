<?php
// Redirect to unified login page
header("Location: ../login.php?tab=admin");
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE Admin Login</title>
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

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 3rem;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .logo {
            font-family: 'Space Mono', monospace;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .logo span { color: var(--accent); }

        .subtitle {
            color: var(--muted);
            font-size: 0.9rem;
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

        .demo-credentials {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.8rem;
            color: var(--muted);
        }

        .demo-credentials strong {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo">VOLT<span>CORE</span></div>
                <p class="subtitle">Admin Panel</p>
            </div>

            <?php if ($loginError): ?>
                <div class="error-message">✗ <?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn-login">LOGIN TO ADMIN</button>
            </form>

            <div class="demo-credentials">
                <p><strong>Demo Credentials:</strong></p>
                <p>Username: <strong>admin</strong></p>
                <p>Password: <strong>admin123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
