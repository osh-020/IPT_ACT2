<?php
session_start();
include ("../includes/db_connect.php");

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Remove from Cart
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['remove_product'])) {
    $product_id = intval($_POST['remove_product']);
    unset($_SESSION['cart'][$product_id]);
    header("Location: cart.php");
    exit;
}

// Handle Update Quantity
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if (isset($_SESSION['cart'][$product_id]) && $quantity > 0) {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }
    header("Location: cart.php");
    exit;
}

// Calculate cart totals
$cartItems = $_SESSION['cart'];
$subtotal = 0;
$tax = 0;
$total = 0;

foreach ($cartItems as $product_id => $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * 0.08; // 8% VAT
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="./css/cart.css">
</head>
<body>
    <div class="shopping-container">
        <div class="shopping-header">
            <h1>🛒 Shopping Cart</h1>
            <p>Review your items before checkout</p>
        </div>

        <div class="cart-wrapper">
            <?php if (count($cartItems) > 0): ?>
                <div class="cart-items-section">
                    <h2>Cart Items (<strong><?php echo count($cartItems); ?></strong>)</h2>
                    
                    <div class="cart-table">
                        <div class="cart-header">
                            <div class="col-image">Image</div>
                            <div class="col-product">Product</div>
                            <div class="col-price">Price</div>
                            <div class="col-quantity">Quantity</div>
                            <div class="col-total">Total</div>
                            <div class="col-action">Action</div>
                        </div>

                        <?php foreach ($cartItems as $product_id => $item): ?>
                            <div class="cart-row">
                                <div class="col-image">
                                    <img src="../admin/uploads/<?php echo htmlspecialchars($product_id); ?>.jpg" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="cart-image"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Crect fill=%22%23f0f0f0%22/%3E%3Ctext x=%2230%22 y=%2230%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2210%22 fill=%22%23999%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                                </div>
                                <div class="col-product">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                </div>
                                <div class="col-price">
                                    ₱<?php echo number_format($item['price'], 2); ?>
                                </div>
                                <div class="col-quantity">
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="999" class="qty-input">
                                        <button type="submit" name="update_quantity" class="btn-update">Update</button>
                                    </form>
                                </div>
                                <div class="col-total">
                                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                                <div class="col-action">
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="remove_product" value="<?php echo $product_id; ?>" 
                                                class="btn-remove" onclick="return confirm('Remove this item?');">Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="cart-summary-section">
                    <h2>Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <strong>₱<?php echo number_format($subtotal, 2); ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Tax (8% VAT):</span>
                        <strong>₱<?php echo number_format($tax, 2); ?></strong>
                    </div>
                    <div class="summary-row total-row">
                        <span>Total:</span>
                        <strong class="total-amount">₱<?php echo number_format($total, 2); ?></strong>
                    </div>

                    <div class="summary-actions">
                        <a href="products.php" class="btn-continue-shopping">Continue Shopping</a>
                        <button class="btn-checkout" onclick="alert('Proceeding to checkout. Please complete your purchase.');">
                            Proceed to Checkout
                        </button>
                    </div>

                    <div class="shipping-info">
                        <p><strong>📦 Shipping:</strong> Free on orders over ₱1,000</p>
                        <p><strong>🔒 Secure:</strong> Your payment information is safe</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <h2>Your cart is empty</h2>
                    <p>Start shopping and add items to your cart</p>
                    <a href="products.php" class="btn-start-shopping">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
