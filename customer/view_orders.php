<?php
session_start();
include '../includes/db_connect.php';
require_once '../includes/customer_notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle order rating submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_rating'])) {
    $order_id = intval($_POST['order_id']);
    $rating = intval($_POST['rating'] ?? 0);
    $review = htmlspecialchars(trim($_POST['review'] ?? ''));
    
    // Verify order belongs to user
    $verify_stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0 && $rating >= 1 && $rating <= 5) {
        $insert_stmt = $conn->prepare("
            INSERT INTO order_ratings (order_id, rating, review, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review), created_at = NOW()
        ");
        $insert_stmt->bind_param("iis", $order_id, $rating, $review);
        $insert_stmt->execute();
        $insert_stmt->close();
        
        $rating_success = "Thank you for rating your order!";
    }
    $verify_stmt->close();
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    
    // Verify order belongs to user and is pending
    $verify_stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $order_check = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    if ($order_check && $order_check['order_status'] === 'Pending') {
        // Update order status to cancelled
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?");
        $update_stmt->bind_param("i", $order_id);
        
        if ($update_stmt->execute()) {
            // Create notification
            createNotification(
                $user_id,
                'cancelled',
                'Order Cancelled',
                'Your order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . ' has been cancelled.',
                $conn,
                $order_id
            );
            
            $cancel_success = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " has been cancelled successfully.";
        }
        $update_stmt->close();
    }
}

// Get all orders for the user with ratings
$orders_stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.subtotal, o.tax, o.total, o.payment_method, o.order_status,
           COALESCE(r.rating, 0) AS rating, COALESCE(r.review, '') AS review
    FROM orders o
    LEFT JOIN order_ratings r ON r.order_id = o.order_id
    WHERE o.user_id = ? 
    ORDER BY o.order_date DESC
");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - COMPUTRONIUM</title>
    <link rel="stylesheet" href="../includes/customer_style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="main-content">
        <div class="orders-container">
            <h1>Your Orders</h1>

            <?php if (!empty($cancel_success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($cancel_success); ?></div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-header-item">
                                <span class="order-header-label">Order ID</span>
                                <span class="order-header-value">#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="order-header-item">
                                <span class="order-header-label">Order Date</span>
                                <span class="order-header-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="order-header-item">
                                <span class="order-header-label">Total Amount</span>
                                <span class="order-header-value">₱<?php echo number_format($order['total'], 2); ?></span>
                            </div>
                            <div class="order-header-item">
                                <span class="order-header-label">Status</span>
                                <span class="order-status status-<?php echo strtolower($order['order_status']); ?>"><?php echo $order['order_status']; ?></span>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="order-body">
                            <table class="order-items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $items_stmt = $conn->prepare("SELECT product_name, price, quantity, subtotal FROM order_items WHERE order_id = ?");
                                    $items_stmt->bind_param("i", $order['order_id']);
                                    $items_stmt->execute();
                                    $items_result = $items_stmt->get_result();
                                    $items = $items_result->fetch_all(MYSQLI_ASSOC);
                                    $items_stmt->close();

                                    foreach ($items as $item):
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Order Summary -->
                        <div class="order-summary">
                            <div class="summary-item">
                                <span class="summary-label">Subtotal</span>
                                <span class="summary-value">₱<?php echo number_format($order['subtotal'], 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Tax (12%)</span>
                                <span class="summary-value">₱<?php echo number_format($order['tax'], 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total</span>
                                <span class="summary-value summary-total">₱<?php echo number_format($order['total'], 2); ?></span>
                            </div>
                        </div>

                        <!-- Order Actions -->
                        <div class="order-footer">
                            <?php if ($order['order_status'] === 'Pending'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="cancel_order" class="btn-cancel">Cancel Order</button>
                                </form>
                            <?php elseif ($order['order_status'] === 'Cancelled'): ?>
                                <span style="color: #dc3545; font-weight: 600;">Order Cancelled</span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 14px;">Cannot cancel - Order is <?php echo $order['order_status']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>

