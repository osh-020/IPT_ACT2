<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'];

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove') {
        $productId = (int)$_POST['product_id'];
        if (isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($item) => $item['id'] !== $productId);
        }
    } elseif ($_POST['action'] === 'update_qty') {
        $productId = (int)$_POST['product_id'];
        $qty = (int)$_POST['qty'];
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] === $productId) {
                    $item['qty'] = max(1, $qty);
                    break;
                }
            }
        }
    }
}

$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE — Shopping Cart</title>
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
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        h1 {
            font-family: 'Space Mono', monospace;
            font-size: 2rem;
            margin-bottom: 2rem;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        .cart-items {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            align-items: start;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-icon {
            font-size: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface2);
            border-radius: 8px;
            height: 80px;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .item-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .item-brand {
            font-size: 0.8rem;
            color: var(--muted);
        }

        .item-price {
            font-family: 'Space Mono', monospace;
            color: var(--accent);
            font-weight: 700;
            font-size: 1rem;
        }

        .item-qty {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .qty-btn {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
        }

        .qty-btn:hover {
            border-color: var(--accent);
        }

        .qty-input {
            width: 40px;
            text-align: center;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 0.4rem;
        }

        .btn-remove {
            background: var(--danger);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            cursor: pointer;
            margin-top: 0.75rem;
        }

        .btn-remove:hover {
            opacity: 0.9;
        }

        .cart-summary {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-family: 'Space Mono', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .summary-row.total {
            border-bottom: none;
            font-weight: 700;
            font-family: 'Space Mono', monospace;
            padding-top: 1rem;
        }

        .summary-row.total .amount {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .btn-checkout {
            width: 100%;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: transform 0.15s;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
        }

        .btn-checkout:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: var(--muted);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .continue-shopping {
            display: inline-block;
            background: var(--accent);
            color: #000;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
            font-weight: 600;
            transition: opacity 0.15s;
        }

        .continue-shopping:hover {
            opacity: 0.9;
        }

        .login-prompt {
            background: rgba(71, 212, 255, 0.1);
            border: 1px solid var(--accent2);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .login-prompt a {
            color: var(--accent2);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">VOLT<span>CORE</span></div>
        <div class="nav-right">
            <a href="../client/index.php" class="nav-link">Shop</a>
            <a href="../client/cart.php" class="nav-link" style="color: var(--accent); font-weight: 600;">Cart</a>
            <?php if ($isLoggedIn): ?>
                <a href="../client/profile.php" class="nav-link">Profile</a>
            <?php else: ?>
                <a href="../login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <h1>🛒 Shopping Cart</h1>

        <?php if (!$isLoggedIn): ?>
            <div class="login-prompt">
                💡 <a href="../login.php">Login to your account</a> to proceed with checkout
            </div>
        <?php endif; ?>

        <div class="cart-grid">
            <div class="cart-items">
                <?php if (count($cart) > 0): ?>
                    <?php foreach ($cart as $item): ?>
                        <div class="cart-item">
                            <div class="item-icon"><?php echo $item['icon']; ?></div>
                            <div>
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
                                <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                
                                <div class="item-qty">
                                    <form method="POST" style="display: inline; display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="button" class="qty-btn" onclick="updateQty(this, -1)">−</button>
                                        <input type="number" name="qty" value="<?php echo $item['qty']; ?>" class="qty-input" min="1" onchange="this.form.submit()">
                                        <button type="button" class="qty-btn" onclick="updateQty(this, 1)">+</button>
                                    </form>
                                </div>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-cart">
                        <div class="empty-icon">🛒</div>
                        <h2>Your cart is empty</h2>
                        <p>Start shopping to add items to your cart</p>
                        <a href="../client/index.php" class="continue-shopping">Continue Shopping →</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cart-summary">
                <div class="summary-title">Order Summary</div>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>$<?php echo number_format($total > 0 ? 50 : 0, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Tax (12%)</span>
                    <span>$<?php echo number_format($total * 0.12, 2); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Total</span>
                    <span class="amount">$<?php echo number_format($total + ($total > 0 ? 50 + ($total * 0.12) : 0), 2); ?></span>
                </div>

                <form action="../client/checkout.php" method="POST">
                    <button type="submit" class="btn-checkout" <?php echo count($cart) === 0 ? 'disabled' : ''; ?>>
                        <?php echo $isLoggedIn ? 'PROCEED TO CHECKOUT' : 'LOGIN TO CHECKOUT'; ?>
                    </button>
                </form>

                <?php if (count($cart) === 0): ?>
                    <a href="../client/index.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--accent); text-decoration: none;">← Back to Shop</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function updateQty(btn, delta) {
            const input = btn.parentElement.querySelector('input[name="qty"]');
            let newVal = parseInt(input.value) + delta;
            if (newVal < 1) newVal = 1;
            input.value = newVal;
        }
    </script>
</body>
</html>
