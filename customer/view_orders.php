<?php
session_start();
include '../includes/db_connect.php';
require_once '../includes/notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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

// Get all orders for the user
$orders_stmt = $conn->prepare("SELECT order_id, order_date, subtotal, tax, total, payment_method, order_status FROM orders WHERE user_id = ? ORDER BY order_date DESC");
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
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .orders-container h1 {
            color: #e8ff47;
            margin-bottom: 30px;
            font-size: 32px;
        }

        .no-orders {
            background: #1c1c21;
            border: 2px solid #e8ff47;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            color: #b0b0b0;
        }

        .no-orders p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .order-card {
            background: #1c1c21;
            border: 1px solid #2a2a32;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-header {
            background: #0d0d0f;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            border-bottom: 1px solid #2a2a32;
        }

        .order-header-item {
            display: flex;
            flex-direction: column;
        }

        .order-header-label {
            color: #e8ff47;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .order-header-value {
            color: #f0f0f0;
            font-size: 16px;
        }

        .order-status {
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            display: inline-block;
            width: fit-content;
        }

        .status-pending {
            background: #ff9800;
            color: #000;
        }

        .status-processing {
            background: #2196f3;
            color: #fff;
        }

        .status-shipped {
            background: #9c27b0;
            color: #fff;
        }

        .status-delivered {
            background: #4caf50;
            color: #fff;
        }

        .order-body {
            padding: 20px;
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .order-items-table thead {
            background: #0d0d0f;
            border-bottom: 2px solid #e8ff47;
        }

        .order-items-table th {
            color: #e8ff47;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        .order-items-table td {
            color: #b0b0b0;
            padding: 12px;
            border-bottom: 1px solid #2a2a32;
        }

        .order-items-table tr:hover {
            background: #0d0d0f;
        }

        .order-summary {
            display: flex;
            justify-content: flex-end;
            gap: 40px;
            padding: 20px;
            background: #0d0d0f;
            border-top: 1px solid #2a2a32;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .summary-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-value {
            color: #f0f0f0;
            font-size: 18px;
            font-weight: 600;
        }

        .summary-total {
            color: #e8ff47;
            font-size: 24px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-small {
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-details {
            background: #e8ff47;
            color: #000;
            font-weight: 600;
        }

        .btn-details:hover {
            background: #f0ff66;
        }

        .btn-cancel {
            background: #dc3545;
            color: #fff;
            font-weight: 600;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        .btn-cancel:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .order-footer {
            padding: 15px 20px;
            background: #0d0d0f;
            border-top: 1px solid #2a2a32;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .order-header {
                grid-template-columns: 1fr 1fr;
            }

            .order-items-table {
                font-size: 14px;
            }

            .order-items-table th,
            .order-items-table td {
                padding: 8px;
            }

            .order-summary {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .summary-item {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

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

    <?php include '../includes/footer.php'; ?>
</body>
</html>
