<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'];

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header("Location: ../login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];

// Calculate totals
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$shipping = $subtotal > 0 ? 50 : 0;
$tax = $subtotal * 0.12;
$total = $subtotal + $shipping + $tax;

// Process checkout
$orderPlaced = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && count($cart) > 0) {
    // Generate order ID
    $orderId = 'ORD-' . date('YmdHis');
    
    // Store order in session
    if (!isset($_SESSION['orders'])) {
        $_SESSION['orders'] = [];
    }
    
    $_SESSION['orders'][] = [
        'id' => $orderId,
        'customer_name' => $_SESSION['customer_name'],
        'customer_email' => $_SESSION['customer_email'],
        'items' => $cart,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $total,
        'status' => 'Pending',
        'date' => date('Y-m-d H:i:s')
    ];
    
    // Clear cart
    $_SESSION['cart'] = [];
    $orderPlaced = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE — Checkout</title>
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
            --success: #4caf50;
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
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .success-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .success-message h2 {
            font-family: 'Space Mono', monospace;
            color: var(--success);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .order-id {
            font-family: 'Space Mono', monospace;
            font-size: 1.2rem;
            color: var(--accent);
            margin: 1rem 0;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .section-title {
            font-family: 'Space Mono', monospace;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: var(--accent);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        input, select {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            padding: 0.8rem;
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus, select:focus {
            border-color: var(--accent);
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
        }

        .item-price {
            color: var(--accent);
            font-weight: 700;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .summary-row.total {
            font-weight: 700;
            font-family: 'Space Mono', monospace;
            border-bottom: none;
            padding-top: 1rem;
            font-size: 1.1rem;
        }

        .summary-row.total .amount {
            color: var(--accent);
        }

        .btn-place-order {
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

        .btn-place-order:hover {
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-block;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
            transition: all 0.15s;
        }

        .btn-back:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">VOLT<span>CORE</span></div>
        <div class="nav-right">
            <a href="../client/index.php" class="nav-link">Shop</a>
            <a href="../client/cart.php" class="nav-link">Cart</a>
            <a href="../client/profile.php" class="nav-link">Profile</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($orderPlaced): ?>
            <div class="success-message">
                <div class="success-icon">✓</div>
                <h2>Order Placed Successfully!</h2>
                <p>Thank you for your purchase. Your order has been confirmed.</p>
                <div class="order-id">Order ID: ORD-<?php echo date('YmdHis'); ?></div>
                <p style="color: var(--muted); margin-top: 1rem;">A confirmation email has been sent to <?php echo htmlspecialchars($_SESSION['customer_email']); ?></p>
                <a href="../client/index.php" class="btn-back" style="display: inline-block;">Continue Shopping →</a>
            </div>
        <?php else: ?>
            <h1 style="font-family: 'Space Mono', monospace; margin-bottom: 2rem;">Checkout</h1>

            <div class="checkout-grid">
                <!-- Billing Information -->
                <div class="section">
                    <div class="section-title">Billing Information</div>
                    <form method="POST" id="checkoutForm">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['customer_name']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($_SESSION['customer_email']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" value="<?php echo htmlspecialchars($_SESSION['customer_phone']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Shipping Address</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['customer_address']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Payment Method</label>
                            <select required>
                                <option value="">Select Payment Method</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="gcash">GCash</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="section">
                    <div class="section-title">Order Summary</div>

                    <div style="margin-bottom: 1.5rem;">
                        <?php foreach ($cart as $item): ?>
                            <div class="order-item">
                                <div>
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--muted);">Qty: <?php echo $item['qty']; ?></div>
                                </div>
                                <div class="item-price">$<?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Tax (12%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="amount">$<?php echo number_format($total, 2); ?></span>
                    </div>

                    <form method="POST">
                        <button type="submit" class="btn-place-order">PLACE ORDER</button>
                    </form>

                    <a href="../client/cart.php" class="btn-back">← Back to Cart</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
