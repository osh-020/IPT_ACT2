<?php
session_start();
include ("../includes/db_connect.php");
include ("../includes/customer_notifications.php");
include ("../includes/admin_notifications.php");

$successMessage = '';
$errorMessage = '';

// Get admin notification count
$adminUnreadCount = getAdminUnreadNotificationsCount($conn);

// Check for session messages
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']);
}
if (isset($_SESSION['errorMessage'])) {
    $errorMessage = $_SESSION['errorMessage'];
    unset($_SESSION['errorMessage']);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $orderId = intval($_POST['order_id'] ?? 0);

    if ($orderId <= 0) {
        $_SESSION['errorMessage'] = "Invalid order ID.";
        header("Location: view_order.php");
        exit();
    }

    // Update order status
    if ($_POST['action'] === 'update_status') {
        $allowedStatuses = ['pending', 'preparing', 'shipped', 'completed', 'refunded', 'cancelled'];
        $newStatus = $_POST['status'] ?? '';

        if (!in_array($newStatus, $allowedStatuses)) {
            $_SESSION['errorMessage'] = "Invalid status.";
        } else {
            // Get order details for notification
            $orderQuery = $conn->prepare("SELECT user_id, order_status FROM orders WHERE order_id = ?");
            $orderQuery->bind_param("i", $orderId);
            $orderQuery->execute();
            $orderResult = $orderQuery->get_result();
            $orderData = $orderResult->fetch_assoc();
            $orderQuery->close();
            
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $stmt->bind_param("si", $newStatus, $orderId);
            if ($stmt->execute()) {
                $_SESSION['successMessage'] = "Order #$orderId status updated to " . ucfirst($newStatus) . ".";
                
                // Create notification for customer
                if ($orderData && $orderData['user_id']) {
                    $notificationType = 'order';
                    $notificationTitle = '';
                    $notificationMessage = '';
                    
                    if ($newStatus === 'completed') {
                        $notificationType = 'shipped';
                        $notificationTitle = "Order #$orderId Completed";
                        $notificationMessage = "Your order #$orderId has been completed and is ready for pickup or delivery.";
                    } elseif ($newStatus === 'refunded') {
                        $notificationType = 'cancelled';
                        $notificationTitle = "Order #$orderId Refunded";
                        $notificationMessage = "Your order #$orderId has been refunded. The refund will be processed within 5-7 business days.";
                    } elseif ($newStatus === 'cancelled') {
                        $notificationType = 'cancelled';
                        $notificationTitle = "Order #$orderId Cancelled";
                        $notificationMessage = "Your order #$orderId has been cancelled.";
                    }
                    
                    if ($notificationTitle) {
                        createNotification($orderData['user_id'], $notificationType, $notificationTitle, $notificationMessage, $conn, $orderId);
                    }
                }
                
                // Create admin notification
                $adminNotifType = 'order';
                $adminNotifTitle = '';
                $adminNotifMessage = '';
                
                if ($newStatus === 'pending') {
                    $adminNotifType = 'order';
                    $adminNotifTitle = "New Order #$orderId";
                    $adminNotifMessage = "A new order has been placed. Click to view details.";
                } elseif ($newStatus === 'refunded') {
                    $adminNotifType = 'refund';
                    $adminNotifTitle = "Order #$orderId Refunded";
                    $adminNotifMessage = "Order #$orderId has been marked as refunded.";
                } elseif ($newStatus === 'cancelled') {
                    $adminNotifType = 'cancel';
                    $adminNotifTitle = "Order #$orderId Cancelled";
                    $adminNotifMessage = "Order #$orderId has been cancelled.";
                }
                
                if ($adminNotifTitle) {
                    createAdminNotification($orderId, $adminNotifType, $adminNotifTitle, $adminNotifMessage, $conn);
                }
            } else {
                $_SESSION['errorMessage'] = "Failed to update order status.";
            }
            $stmt->close();
        }
        $qs = http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'search' => $_GET['search'] ?? '']));
        header("Location: view_order.php" . ($qs ? "?$qs" : ''));
        exit();
    }

    // Submit / update rating
    if ($_POST['action'] === 'submit_rating') {
        $_SESSION['errorMessage'] = "Rating feature is only available for customers.";
        $qs = http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'search' => $_GET['search'] ?? '']));
        header("Location: view_order.php" . ($qs ? "?$qs" : ''));
        exit();
    }
}

// Filters
$filterStatus = isset($_GET['status']) ? htmlspecialchars(trim($_GET['status'])) : '';
$searchQuery  = isset($_GET['search'])  ? htmlspecialchars(trim($_GET['search']))  : '';

// Build main query
$query = "
    SELECT o.*
    FROM orders o
    WHERE 1=1
";

if (!empty($filterStatus)) {
    $escapedStatus = $conn->real_escape_string($filterStatus);
    $query .= " AND o.order_status = '$escapedStatus'";
}

if (!empty($searchQuery)) {
    $escapedSearch = $conn->real_escape_string("%$searchQuery%");
    $query .= " AND (o.order_id LIKE '$escapedSearch' OR o.full_name LIKE '$escapedSearch' OR o.email LIKE '$escapedSearch')";
}

$query .= " ORDER BY o.order_date DESC";

$result = $conn->query($query);
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Count per status for summary strip
$statusCounts = [];
$countResult = $conn->query("SELECT order_status as status, COUNT(*) as cnt FROM orders GROUP BY order_status");
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $statusCounts[$row['status']] = $row['cnt'];
    }
}

// Helpers
function statusBadge($status) {
    $map = [
        'pending'   => ['label' => 'Pending',   'class' => 'badge-pending'],
        'preparing' => ['label' => 'Preparing', 'class' => 'badge-preparing'],
        'shipped'   => ['label' => 'Shipped',   'class' => 'badge-shipped'],
        'completed' => ['label' => 'Completed', 'class' => 'badge-completed'],
        'refunded'  => ['label' => 'Refunded',  'class' => 'badge-refunded'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'badge-cancelled'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-pending'];
    return "<span class=\"status-badge {$s['class']}\">{$s['label']}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders</title>
    <link rel="icon" type="image/png" href="../includes/website_pic/logo.png">
    <link rel="stylesheet" href="../includes/admin_style.css">
</head>
<body class="view_orders">

<!-- Admin Header -->
<header class="admin-header">
    <div class="admin-header-container">
        <!-- Logo Section -->
        <div class="admin-logo">
            <a href="index.php" class="admin-logo-link">
                <img src="../includes/website_pic/logo.png" alt="COMPUTRONIUM Logo" class="admin-logo-img">
                <h1>COMPUTRONIUM Admin</h1>
            </a>
        </div>

        <!-- Admin Navigation -->
        <nav class="admin-nav">
            <ul class="admin-nav-menu">
                <li><a href="index.php" class="admin-nav-link">Dashboard</a></li>
                <li><a href="manage_product.php" class="admin-nav-link">Products</a></li>
                <li><a href="view_order.php" class="admin-nav-link active">Orders</a></li>
                <li><a href="notifications.php" class="admin-nav-link">Notifications<?php if ($adminUnreadCount > 0): ?> <span style="display: inline-block; background: #ff4444; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; font-size: 12px; margin-left: 5px;"><?php echo $adminUnreadCount; ?></span><?php endif; ?></a></li>
            </ul>
        </nav>

        <!-- Admin Actions -->
        <div class="admin-actions">
            <a href="upload_product.php" class="admin-btn-primary">New Product</a>
        </div>
    </div>
</header>

<div class="container">
    <h2>View Orders</h2>

    <?php if ($successMessage): ?>
        <div class="success-message"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="error-message"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <!-- ── Summary Strip ── -->
    <div class="summary-strip">
        <?php
        $allStatuses = [
            ''          => 'All Orders',
            'pending'   => 'Pending',
            'preparing' => 'Preparing',
            'shipped'   => 'Shipped',
            'completed' => 'Completed',
            'refunded'  => 'Refunded',
            'cancelled' => 'Cancelled',
        ];
        $totalAll = array_sum($statusCounts);
        foreach ($allStatuses as $key => $label):
            $count    = ($key === '') ? $totalAll : ($statusCounts[$key] ?? 0);
            $isActive = ($filterStatus === $key);
            $href     = 'view_order.php' . ($key !== '' ? '?status=' . $key : '');
            if (!empty($searchQuery)) $href .= (strpos($href,'?') !== false ? '&' : '?') . 'search=' . urlencode($searchQuery);
        ?>
            <a href="<?php echo $href; ?>" class="summary-card <?php echo $isActive ? 'active' : ''; ?>">
                <div class="sc-count"><?php echo $count; ?></div>
                <div class="sc-label"><?php echo $label; ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ── Filter Bar ── -->
    <form method="GET" class="filter-bar">
        <div class="search-wrapper">
            <input type="text" name="search" class="search-input"
                   placeholder="Search by order ID, name, or email..."
                   value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn-search-icon">Search</button>
        </div>
        <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (array_slice($allStatuses, 1, null, true) as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($searchQuery) || !empty($filterStatus)): ?>
            <a href="view_order.php" class="btn-clear">Clear</a>
        <?php endif; ?>
    </form>

    <!-- ── Orders Table ── -->
    <?php if (count($orders) > 0): ?>
        <div class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Update Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <!-- Order ID -->
                        <td><span class="order-id">#<?php echo intval($order['order_id']); ?></span></td>

                        <!-- Customer -->
                        <td>
                            <div><?php echo htmlspecialchars($order['full_name']); ?></div>
                            <div class="customer-email"><?php echo htmlspecialchars($order['email']); ?></div>
                        </td>

                        <!-- Total -->
                        <td class="order-total">₱<?php echo number_format(floatval($order['total']), 2); ?></td>

                        <!-- Date -->
                        <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>

                        <!-- Status Badge -->
                        <td><?php echo statusBadge($order['order_status']); ?></td>

                        <!-- Update Status (inline dropdown) -->
                        <td>
                            <form method="POST" class="status-select-form">
                                <input type="hidden" name="action"   value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                                <select name="status">
                                    <?php foreach (['pending','preparing','shipped','completed','refunded','cancelled'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $order['order_status'] === $s ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($s); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-status-update">Save</button>
                            </form>
                        </td>

                        <!-- Quick Actions -->
                        <td>
                            <div class="actions-cell">
                                <?php if (!in_array($order['order_status'], ['refunded','cancelled'])): ?>
                                    <form method="POST" onsubmit="return confirm('Mark Order #<?php echo intval($order['order_id']); ?> as Refunded?')">
                                        <input type="hidden" name="action"   value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                                        <input type="hidden" name="status"   value="refunded">
                                        <button type="submit" class="btn-quick btn-refund">Refund</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!in_array($order['order_status'], ['completed','cancelled','refunded'])): ?>
                                    <form method="POST" onsubmit="return confirm('Mark Order #<?php echo intval($order['order_id']); ?> as Completed?')">
                                        <input type="hidden" name="action"   value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                                        <input type="hidden" name="status"   value="completed">
                                        <button type="submit" class="btn-quick btn-complete">Complete</button>
                                    </form>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-message">
            <p>No orders found<?php echo !empty($filterStatus) ? " with status <strong>" . htmlspecialchars(ucfirst($filterStatus)) . "</strong>" : ''; ?>.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>