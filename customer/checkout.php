<?php
session_start();
include '../includes/db_connect.php';
require_once '../includes/notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize order
$subtotal = 0;
$cart_items = [];

// Calculate cart totals
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $cart_items[] = [
            'id' => $product_id,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'total' => $item_total
        ];
    }
}

$tax = $subtotal * 0.12; // 12% tax
$total = $subtotal + $tax;

// Handle order placement
$order_success = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $error_message = "Your cart is empty!";
    } else {
        // Get user info for order
        $user_info_stmt = $conn->prepare("SELECT full_name, email, mobile_number, address, zip_code FROM users WHERE user_id = ?");
        $user_info_stmt->bind_param("i", $_SESSION['user_id']);
        $user_info_stmt->execute();
        $user_info_result = $user_info_stmt->get_result();
        $user_info = $user_info_result->fetch_assoc();
        $user_info_stmt->close();

        // Insert order into orders table
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, tax, total, payment_method, order_status, full_name, email, mobile_number, delivery_address, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $payment_method = "COD";
        $order_status = "Pending";
        $order_stmt->bind_param("idddsssssss", $_SESSION['user_id'], $subtotal, $tax, $total, $payment_method, $order_status, $user_info['full_name'], $user_info['email'], $user_info['mobile_number'], $user_info['address'], $user_info['zip_code']);
        
        if ($order_stmt->execute()) {
            $order_id = $order_stmt->insert_id;
            $order_stmt->close();

            // Insert order items into order_items table
            $items_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($cart_items as $item) {
                $item_subtotal = $item['price'] * $item['quantity'];
                $items_stmt->bind_param("iisidi", $order_id, $item['id'], $item['name'], $item['price'], $item['quantity'], $item_subtotal);
                $items_stmt->execute();
            }
            $items_stmt->close();

            // Create notification for order placed
            createNotification(
                $_SESSION['user_id'],
                'order',
                'Order Placed',
                'Your order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . ' has been successfully placed. Total: ₱' . number_format($total, 2),
                $conn,
                $order_id
            );

            // Clear the cart
            $_SESSION['cart'] = [];
            $order_success = true;
        } else {
            $error_message = "Error placing order. Please try again.";
            $order_stmt->close();
        }
    }
}

// Get user information
$user_stmt = $conn->prepare("SELECT full_name, email, mobile_number, address, zip_code FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

?>

<?php include '../includes/header.php'; ?>

<main class="main-content">
    <div class="checkout-container">
        <h2>Checkout</h2>

        <?php
        if ($order_success) {
            echo "<div class='success-message'>";
            echo "<h3>Order Placed Successfully!</h3>";
            echo "<p>Your order has been received. We'll process it shortly.</p>";
            echo "<p><strong>Thank you for your purchase!</strong></p>";
            echo "<div style='display: flex; gap: 10px; margin-top: 20px;'>";
            echo "<a href='view_orders.php' class='btn btn-primary'>View Orders</a>";
            echo "<a href='home.php' class='btn btn-secondary'>Continue Shopping</a>";
            echo "</div>";
            echo "</div>";
        } else {
            if (!empty($error_message)) {
                echo "<div class='error-message'>$error_message</div>";
            }

            if (empty($cart_items)) {
                echo "<div class='empty-checkout'>";
                echo "<p>Your cart is empty</p>";
                echo "<a href='products.php' class='btn btn-primary'>Continue Shopping</a>";
                echo "</div>";
            } else {
                echo "<div class='checkout-wrapper'>";

                // Order Summary
                echo "<div class='checkout-summary'>";
                echo "<h3>Order Summary</h3>";
                echo "<div class='summary-items'>";
                foreach ($cart_items as $item) {
                    $item_total = number_format($item['total'], 2);
                    echo "<div class='summary-item'>";
                    echo "<span>" . htmlspecialchars($item['name']) . " x " . $item['quantity'] . "</span>";
                    echo "<span>₱" . $item_total . "</span>";
                    echo "</div>";
                }
                echo "</div>";

                echo "<div class='summary-totals'>";
                echo "<div class='total-row'>";
                echo "<span>Subtotal:</span>";
                echo "<span>₱" . number_format($subtotal, 2) . "</span>";
                echo "</div>";
                echo "<div class='total-row'>";
                echo "<span>Tax (12%):</span>";
                echo "<span>₱" . number_format($tax, 2) . "</span>";
                echo "</div>";
                echo "<div class='total-row grand-total'>";
                echo "<span>Total:</span>";
                echo "<span>₱" . number_format($total, 2) . "</span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";

                // Billing Information
                echo "<div class='billing-section'>";
                echo "<h3>Billing & Delivery Information</h3>";
                echo "<div class='billing-info'>";
                echo "<div class='info-box'>";
                echo "<h4>Name:</h4>";
                echo "<p>" . htmlspecialchars($user['full_name']) . "</p>";
                echo "</div>";
                echo "<div class='info-box'>";
                echo "<h4>Email:</h4>";
                echo "<p>" . htmlspecialchars($user['email']) . "</p>";
                echo "</div>";
                echo "<div class='info-box'>";
                echo "<h4>Phone:</h4>";
                echo "<p>" . htmlspecialchars($user['mobile_number']) . "</p>";
                echo "</div>";
                echo "<div class='info-box'>";
                echo "<h4>Delivery Address:</h4>";
                echo "<p>" . htmlspecialchars($user['address']) . ", " . htmlspecialchars($user['zip_code']) . "</p>";
                echo "</div>";
                echo "</div>";
                echo "<a href='edit_profile.php' class='btn btn-secondary'>Edit Address</a>";
                echo "</div>";

                // Payment Method
                echo "<div class='payment-section'>";
                echo "<h3>Payment Method</h3>";
                echo "<div class='payment-options'>";
                echo "<div class='payment-option'>";
                echo "<input type='radio' name='payment' id='cod' value='cod' checked>";
                echo "<label for='cod'>Cash on Delivery (COD)</label>";
                echo "</div>";
                echo "</div>";
                echo "<p class='payment-note'>Select your preferred payment method above</p>";
                echo "</div>";

                // Place Order Button
                echo "<div class='place-order-section'>";
                echo "<form method='POST' action='checkout.php'>";
                echo "<button type='submit' name='place_order' value='1' class='btn btn-primary btn-action'>Place Order</button>";
                echo "</form>";
                echo "<a href='cart.php' class='btn btn-secondary btn-action'>Back to Cart</a>";
                echo "</div>";

                echo "</div>";
            }
        }
        ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
