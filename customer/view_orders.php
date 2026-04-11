<?php
session_start();
include '../includes/db_connect.php';
require_once '../includes/customer_notifications.php';
require_once '../includes/admin_notifications.php';
require_once '../includes/product_rating.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$review_success = '';
$show_rating_form = false;
$rating_product_id = 0;
$rating_order_id = 0;

// Check if user is requesting rating form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['show_rating_form'])) {
    $show_rating_form = true;
    $rating_product_id = intval($_POST['product_id']);
    $rating_order_id = intval($_POST['order_id']);
}

// Get existing review for editing
$existing_review = null;
if ($show_rating_form && $rating_product_id > 0 && $rating_order_id > 0) {
    $review_stmt = $conn->prepare("SELECT rating, review FROM order_ratings WHERE order_id = ? AND product_id = ?");
    $review_stmt->bind_param("ii", $rating_order_id, $rating_product_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    $existing_review = $review_result->fetch_assoc();
    $review_stmt->close();
}

// Handle product rating submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_product_rating'])) {
    $order_id = intval($_POST['order_id']);
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating'] ?? 0);
    $review = htmlspecialchars(trim($_POST['review'] ?? ''));
    
    // Verify order belongs to user and product is in order
    $verify_stmt = $conn->prepare("
        SELECT oi.product_id FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        WHERE o.order_id = ? AND o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Completed'
    ");
    $verify_stmt->bind_param("iii", $order_id, $user_id, $product_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $verify_stmt->close();
    
    if ($verify_result->num_rows > 0 && $rating >= 1 && $rating <= 5) {
        // Insert into order_ratings with product_id
        $insert_stmt = $conn->prepare("
            INSERT INTO order_ratings (order_id, product_id, rating, review, created_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review), created_at = NOW()
        ");
        $insert_stmt->bind_param("iiis", $order_id, $product_id, $rating, $review);
        
        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            // Redirect to refresh the page and show updated button
            header("Location: view_orders.php", true, 303);
            exit;
        } else {
            $review_success = "⚠ Error saving review. Please try again.";
            $insert_stmt->close();
        }
    } else {
        $review_success = "⚠ Unable to save review.";
    }
}

// Handle refund request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_refund'])) {
    $order_id = intval($_POST['order_id']);
    
    // Verify order belongs to user and is delivered
    $verify_stmt = $conn->prepare("SELECT order_status, total FROM orders WHERE order_id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $order_check = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    if ($order_check && $order_check['order_status'] === 'Completed') {
        // Update order status to indicate refund requested
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = 'Refund Requested' WHERE order_id = ?");
        $update_stmt->bind_param("i", $order_id);
        
        if ($update_stmt->execute()) {
            // Get user info for notification
            $user_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_info = $user_result->fetch_assoc();
            $user_stmt->close();
            
            // Create customer notification
            createNotification(
                $user_id,
                'refund',
                'Refund Request Submitted',
                'Your refund request for order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . ' has been submitted. Total: ₱' . number_format($order_check['total'], 2),
                $conn,
                $order_id
            );
            
            // Create admin notification
            createAdminNotification(
                $order_id,
                'refund',
                'Refund Request - Order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT),
                'Customer ' . htmlspecialchars($user_info['full_name']) . ' has requested a refund for order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . ' (₱' . number_format($order_check['total'], 2) . ')',
                $conn
            );
            
            $refund_success = "Refund request submitted successfully. Admin will review and process your request.";
        }
        $update_stmt->close();
    }
}

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

// Get filter from query parameter
$filter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';

// Map filter to status
$status_map = [
    'to_pay' => 'Pending',
    'to_ship' => 'Processing',
    'to_receive' => 'Shipped',
    'completed' => 'Completed',
    'return_refund' => ['Refunded', 'Refund Requested'],
    'cancelled' => 'Cancelled'
];

// Get all orders for the user with ratings
$query = "
    SELECT o.order_id, o.order_date, o.subtotal, o.tax, o.total, o.payment_method, o.order_status,
           COALESCE(r.rating, 0) AS rating, COALESCE(r.review, '') AS review
    FROM orders o
    LEFT JOIN order_ratings r ON r.order_id = o.order_id
    WHERE o.user_id = ?";

if ($filter !== 'all' && isset($status_map[$filter])) {
    $status_value = $status_map[$filter];
    if (is_array($status_value)) {
        // Handle multiple statuses for return_refund
        $statuses = array_map(function($s) use ($conn) { return "'" . $conn->real_escape_string($s) . "'"; }, $status_value);
        $query .= " AND o.order_status IN (" . implode(',', $statuses) . ")";
    } else {
        $query .= " AND o.order_status = '" . $conn->real_escape_string($status_value) . "'";
    }
}

$query .= " ORDER BY o.order_date DESC";

$orders_stmt = $conn->prepare($query);
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

            <?php if (!empty($review_success)): ?>
                <div class="success-message" style="background-color: #28a745; color: white; padding: 15px; border-radius: 0; margin-bottom: 20px; border-left: 4px solid #1e7e34;">
                    ✓ <?php echo htmlspecialchars($review_success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($cancel_success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($cancel_success); ?></div>
            <?php endif; ?>

            <?php if (!empty($refund_success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($refund_success); ?></div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div style="display: flex; gap: 10px; margin-bottom: 25px; margin-top: 20px; flex-wrap: wrap;">
                <?php
                $filters = [
                    'all' => 'All',
                    'to_pay' => 'To Pay',
                    'to_ship' => 'To Ship',
                    'to_receive' => 'To Receive',
                    'completed' => 'Completed',
                    'return_refund' => 'Return/Refund',
                    'cancelled' => 'Cancelled'
                ];
                foreach ($filters as $filter_key => $filter_label):
                    $isActive = $filter === $filter_key;
                    $filter_url = 'view_orders.php?filter=' . $filter_key;
                    $style = $isActive ? 'background-color: #e8ff47; color: #000; font-weight: bold;' : 'background-color: #2a2a32; color: #fff;';
                ?>
                    <a href="<?php echo $filter_url; ?>" style="<?php echo $style; ?> padding: 10px 15px; border-radius: 0; text-decoration: none; border: 1px solid #e8ff47; cursor: pointer; transition: all 0.3s;">
                        <?php echo $filter_label; ?>
                    </a>
                <?php endforeach; ?>
            </div>

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
                                        <?php if ($order['order_status'] === 'Completed'): ?>
                                            <th>Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $order_items = [];
                                    $items_stmt = $conn->prepare("SELECT oi.product_id, oi.product_name, oi.price, oi.quantity, oi.subtotal FROM order_items oi WHERE oi.order_id = ?");
                                    $items_stmt->bind_param("i", $order['order_id']);
                                    $items_stmt->execute();
                                    $items_result = $items_stmt->get_result();
                                    $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
                                    $items_stmt->close();

                                    foreach ($order_items as $item):
                                        $hasReview = userHasReviewedProduct($user_id, $item['product_id'], $conn, $order['order_id']);
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                            <?php if ($order['order_status'] === 'Completed'): ?>
                                                <td>
                                                    <?php if (!$hasReview): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                            <input type="hidden" name="show_rating_form" value="1">
                                                            <button type="submit" class="btn-rate-review" style="padding: 8px 12px; background-color: #e8ff47; color: #000; border: none; border-radius: 0; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                                                Rate/Review
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button type="button" onclick="confirmEditReview(<?php echo $item['product_id']; ?>, <?php echo $order['order_id']; ?>)" class="btn-rate-review" style="padding: 8px 12px; background-color: #90ee90; color: #000; border: none; border-radius: 0; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                                            ✓ Reviewed
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
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
                        <div class="order-footer" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php if ($order['order_status'] === 'Pending'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="cancel_order" class="btn-cancel">Cancel Order</button>
                                </form>
                            <?php elseif ($order['order_status'] === 'Completed'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Request a refund for this order?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="request_refund" class="btn-refund" style="background-color: #ff9800; color: white; padding: 10px 20px; border: none; border-radius: 0; cursor: pointer; font-weight: 600;">Request Refund</button>
                                </form>
                            <?php elseif ($order['order_status'] === 'Cancelled'): ?>
                                <span style="color: #dc3545; font-weight: 600;">Order Cancelled</span>
                            <?php elseif ($order['order_status'] === 'Refund Requested'): ?>
                                <span style="color: #ff9800; font-weight: 600;">Refund Request Pending</span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 14px;">Cannot cancel - Order is <?php echo $order['order_status']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Simple Rating Form (shown when user clicks Rate/Review) -->
    <?php if ($show_rating_form && $rating_product_id > 0): ?>
        <?php
            // Get product name
            $product_name = '';
            $product_name_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
            $product_name_stmt->bind_param("i", $rating_product_id);
            $product_name_stmt->execute();
            $product_result = $product_name_stmt->get_result();
            if ($product_data = $product_result->fetch_assoc()) {
                $product_name = $product_data['name'];
            }
            $product_name_stmt->close();
            
            $isEditing = $existing_review !== null;
            $currentRating = $isEditing ? $existing_review['rating'] : 5;
            $currentReview = $isEditing ? $existing_review['review'] : '';
        ?>
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); z-index: 1000; display: flex; align-items: center; justify-content: center;">
            <div style="background-color: #0d0d0f; border: 2px solid #e8ff47; padding: 30px; max-width: 500px; width: 90%; border-radius: 0;">
                <h2 style="color: #e8ff47; margin-bottom: 10px; margin-top: 0;">Rate & Review Product</h2>
                <p style="color: #d0d0d0; margin-bottom: 20px; font-size: 14px;"><?php echo htmlspecialchars($product_name); ?></p>
                
                <form method="POST" style="display: flex; flex-direction: column; gap: 15px;" onsubmit="return validateReviewForm()">
                    <input type="hidden" name="order_id" value="<?php echo $rating_order_id; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $rating_product_id; ?>">
                    <input type="hidden" name="submit_product_rating" value="1">
                    
                    <div>
                        <label style="color: #e8ff47; font-weight: 600; display: block; margin-bottom: 10px;">Rating (1-5 stars):</label>
                        <div style="display: flex; gap: 15px; font-size: 28px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="cursor: pointer;" class="star-label" data-rating="<?php echo $i; ?>" onclick="selectRating(<?php echo $i; ?>)">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo $i === $currentRating ? 'checked' : ''; ?> style="display: none;" onchange="updateStarDisplay(this)">
                                    <span class="star-display" style="font-weight: 600; color: <?php echo $i <= $currentRating ? '#ffb800' : '#666'; ?>;">★</span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label style="color: #e8ff47; font-weight: 600; display: block; margin-bottom: 10px;">Your Review (Optional):</label>
                        <textarea name="review" placeholder="Share your experience with this product..." style="width: 100%; height: 100px; padding: 10px; background-color: #2a2a32; color: #f0f0f0; border: 1px solid #e8ff47; border-radius: 0; font-family: Arial; resize: vertical;"><?php echo htmlspecialchars($currentReview); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" style="flex: 1; padding: 12px 20px; background-color: #28a745; color: white; border: none; border-radius: 0; cursor: pointer; font-weight: 600; font-size: 16px;"><?php echo $isEditing ? 'Update Review' : 'Submit Review'; ?></button>
                        <a href="view_orders.php" style="flex: 1; padding: 12px 20px; background-color: #dc3545; color: white; border: none; border-radius: 0; cursor: pointer; font-weight: 600; font-size: 16px; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function selectRating(rating) {
            const radioInput = document.querySelector(`input[name="rating"][value="${rating}"]`);
            radioInput.checked = true;
            updateStarDisplay(radioInput);
        }

        function updateStarDisplay(input) {
            const ratingValue = input.value;
            document.querySelectorAll('.star-label').forEach((label, index) => {
                const starDisplay = label.querySelector('.star-display');
                if (index < ratingValue) {
                    starDisplay.style.color = '#ffb800';
                } else {
                    starDisplay.style.color = '#666';
                }
            });
        }
        
        function validateReviewForm() {
            const rating = document.querySelector('input[name="rating"]:checked');
            if (!rating) {
                alert('Please select a rating (1-5 stars)');
                return false;
            }
            return true;
        }

        function confirmEditReview(productId, orderId) {
            if (confirm('Want to edit your review?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="order_id" value="${orderId}">
                    <input type="hidden" name="product_id" value="${productId}">
                    <input type="hidden" name="show_rating_form" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Initialize star display
        document.addEventListener('DOMContentLoaded', function() {
            const checked = document.querySelector('input[name="rating"]:checked');
            if (checked) {
                updateStarDisplay(checked);
            }
        });
    </script>

</body>
</html>

