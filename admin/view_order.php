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
        $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Refund Requested', 'Refunded', 'Cancelled'];
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
                    
                    if ($newStatus === 'Completed') {
                        $notificationType = 'shipped';
                        $notificationTitle = "Order #$orderId Completed";
                        $notificationMessage = "Your order #$orderId has been completed and is ready for pickup or delivery.";
                    } elseif ($newStatus === 'Refunded') {
                        $notificationType = 'cancelled';
                        $notificationTitle = "Order #$orderId Refunded";
                        $notificationMessage = "Your order #$orderId has been refunded. The refund will be processed within 5-7 business days.";
                    } elseif ($newStatus === 'Refund Requested') {
                        $notificationType = 'refund';
                        $notificationTitle = "Refund Request Received";
                        $notificationMessage = "We received your refund request for order #$orderId. Our team is reviewing it.";
                    } elseif ($newStatus === 'Cancelled') {
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
                
                if ($newStatus === 'Pending') {
                    $adminNotifType = 'order';
                    $adminNotifTitle = "New Order #$orderId";
                    $adminNotifMessage = "A new order has been placed. Click to view details.";
                } elseif ($newStatus === 'Refunded') {
                    $adminNotifType = 'refund';
                    $adminNotifTitle = "Order #$orderId Refunded";
                    $adminNotifMessage = "Order #$orderId has been marked as refunded.";
                } elseif ($newStatus === 'Cancelled') {
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
        // Fetch order items for this order
        $itemsQuery = $conn->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
        $itemsQuery->bind_param("i", $row['order_id']);
        $itemsQuery->execute();
        $itemsResult = $itemsQuery->get_result();
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
        $itemsQuery->close();
        $row['items'] = $items;
        $orders[] = $row;
    }
}

$orderItems = [];
foreach ($orders as $order) {
    $oid = intval($order['order_id']);
    $itemResult = $conn->query("SELECT product_name, quantity FROM order_items WHERE order_id = $oid");
    if ($itemResult) {
        while ($item = $itemResult->fetch_assoc()) {
            $orderItems[$oid][] = $item;
        }
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
        'Pending'   => ['label' => 'Pending',   'class' => 'badge-pending'],
        'Processing' => ['label' => 'Processing', 'class' => 'badge-preparing'],
        'Shipped'   => ['label' => 'Shipped',   'class' => 'badge-shipped'],
        'Completed' => ['label' => 'Completed', 'class' => 'badge-completed'],
        'Refunded'  => ['label' => 'Refunded',  'class' => 'badge-refunded'],
        'Cancelled' => ['label' => 'Cancelled', 'class' => 'badge-cancelled'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-pending'];
    return "<span class=\"status-badge {$s['class']}\">{$s['label']}</span>";
}
?>
<?php include("header.php"); ?>

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
            'Pending'   => 'Pending',
            'Processing' => 'Processing',
            'Shipped'   => 'Shipped',
            'Completed' => 'Completed',
            'Refunded'  => 'Refunded',
            'Cancelled' => 'Cancelled',
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
                        <th>Products</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Update Status</th>
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

                        <!-- Products -->
                        <td>
                            <?php if (!empty($order['items'])): ?>
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($order['items'] as $item): ?>
                                    <li style="padding: 2px 0;"><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">No items</span>
                            <?php endif; ?>
                        </td>

                        <!-- Quantity -->
                        <td>
                            <?php 
                            $totalQuantity = 0;
                            if (!empty($order['items'])) {
                                foreach ($order['items'] as $item) {
                                    $totalQuantity += intval($item['quantity']);
                                }
                            }
                            echo $totalQuantity;
                            ?>
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
                                    <?php foreach (['Pending','Processing','Shipped','Completed','Refunded','Cancelled'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $order['order_status'] === $s ? 'selected' : ''; ?>>
                                            <?php echo $s; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-status-update">Save</button>
                            </form>
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
