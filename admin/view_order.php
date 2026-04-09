<?php
session_start();
include ("../includes/db_connect.php");
include ("../includes/notifications.php");

$successMessage = '';
$errorMessage = '';

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
    <link rel="stylesheet" href="../includes/admin_style.css">
    <style>
        /* ===============================================
           VIEW ORDERS PAGE - CUSTOMIZATION STYLES
           =============================================== */

        .view_orders .container { max-width: 1150px; }

        .view_orders h2 {
            border-bottom: 2px solid #e8ff47;
            padding-bottom: 10px;
            text-align: center;
        }

        /* Summary Strip */
        .summary-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .summary-card {
            flex: 1;
            min-width: 120px;
            background: #1c1c21;
            border: 1px solid #2a2a32;
            border-radius: 8px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.2s;
        }

        .summary-card:hover { border-color: #555; }
        .summary-card.active { border-color: #e8ff47; }
        .summary-card .sc-count { font-size: 26px; font-weight: bold; color: #e8ff47; }
        .summary-card .sc-label { font-size: 11px; color: #888; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .badge-pending   { background: #3a3010; color: #e8c547; border: 1px solid #5a4e1a; }
        .badge-preparing { background: #1a2a3a; color: #47b4ff; border: 1px solid #1a4060; }
        .badge-shipped   { background: #1a1a3a; color: #a47dff; border: 1px solid #3a2a6a; }
        .badge-completed { background: #1a2a1a; color: #4caf50; border: 1px solid #2a4a2a; }
        .badge-refunded  { background: #2a2010; color: #ff9f47; border: 1px solid #4a3a10; }
        .badge-cancelled { background: #2a1a1a; color: #ff6b6b; border: 1px solid #4a2a2a; }

        /* Orders Table */
        .orders-table-wrapper {
            overflow-x: auto;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #2a2a32;
        }

        .orders-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .orders-table thead tr { background: #1c1c21; }
        .orders-table th {
            padding: 12px 14px;
            text-align: left;
            color: #e8ff47;
            font-weight: bold;
            border-bottom: 1px solid #2a2a32;
            white-space: nowrap;
        }
        .orders-table td {
            padding: 11px 14px;
            color: #f0f0f0;
            border-bottom: 1px solid #1c1c21;
            vertical-align: middle;
        }
        .orders-table tbody tr { background: #141417; transition: background 0.15s; }
        .orders-table tbody tr:hover { background: #1c1c21; }

        .order-id      { color: #47d4ff; font-weight: bold; }
        .customer-email { color: #888; font-size: 11px; }
        .order-total   { color: #4caf50; font-weight: bold; }

        /* Inline status form */
        .status-select-form { display: flex; gap: 6px; align-items: center; }
        .status-select-form select {
            padding: 6px 8px;
            font-size: 12px;
            border-radius: 4px;
            background: #0d0d0f;
            color: #f0f0f0;
            border: 1px solid #2a2a32;
            width: auto;
        }
        .btn-status-update {
            padding: 6px 12px;
            background: #e8ff47;
            color: #000;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 0;
            width: auto;
        }
        .btn-status-update:hover { background: #f0ff66; }

        /* Stars */
        .star        { font-size: 15px; color: #2a2a32; }
        .star.filled { color: #e8c547; }

        /* Action buttons */
        .actions-cell { display: flex; flex-direction: column; gap: 6px; min-width: 105px; }

        .btn-quick {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 0;
            transition: all 0.2s;
            white-space: nowrap;
            text-align: center;
        }
        .btn-refund   { background: #ff9f47; color: #000; }
        .btn-refund:hover   { background: #ffb266; }
        .btn-complete { background: #4caf50; color: #000; }
        .btn-complete:hover { background: #5cc860; }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            background: #1c1c21;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #2a2a32;
            margin-bottom: 20px;
        }
        .filter-bar .search-wrapper {
            flex: 1;
            min-width: 220px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-bar .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #2a2a32;
            border-radius: 4px;
            background: #0d0d0f;
            color: #f0f0f0;
            font-size: 14px;
            max-width: 400px;
        }
        .filter-bar .search-input:focus { outline: none; border-color: #f1ff99; }
        .filter-bar .btn-search-icon {
            position: static;
            background: #47d4ff;
            border: none;
            color: #000;
            font-size: 14px;
            cursor: pointer;
            padding: 10px 16px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s ease;
            min-width: auto;
            white-space: nowrap;
        }
        .filter-bar .btn-search-icon:hover { background: #5be0ff; }
        .filter-bar select {
            padding: 9px 10px;
            border: 1px solid #2a2a32;
            border-radius: 4px;
            background: #0d0d0f;
            color: #f0f0f0;
            font-size: 13px;
            width: auto;
            min-width: 160px;
        }

        .empty-message { text-align: center; color: #888; padding: 40px 20px; font-style: italic; }

        @media (max-width: 768px) {
            .orders-table th:nth-child(4),
            .orders-table td:nth-child(4) { display: none; }
            .status-select-form { flex-direction: column; }
        }
    </style>
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