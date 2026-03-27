<?php
session_start();
include ("../includes/db_connect.php");

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
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $orderId);
            if ($stmt->execute()) {
                $_SESSION['successMessage'] = "Order #$orderId status updated to " . ucfirst($newStatus) . ".";
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
        $rating = intval($_POST['rating'] ?? 0);
        $review = htmlspecialchars(trim($_POST['review'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            $_SESSION['errorMessage'] = "Rating must be between 1 and 5.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO order_ratings (order_id, rating, review, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review), created_at = NOW()
            ");
            $stmt->bind_param("iis", $orderId, $rating, $review);
            if ($stmt->execute()) {
                $_SESSION['successMessage'] = "Rating submitted for Order #$orderId.";
            } else {
                $_SESSION['errorMessage'] = "Failed to submit rating.";
            }
            $stmt->close();
        }
        $qs = http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'search' => $_GET['search'] ?? '']));
        header("Location: view_order.php" . ($qs ? "?$qs" : ''));
        exit();
    }
}

// Filters
$filterStatus = isset($_GET['status']) ? htmlspecialchars(trim($_GET['status'])) : '';
$searchQuery  = isset($_GET['search'])  ? htmlspecialchars(trim($_GET['search']))  : '';

// Build main query — join with order_ratings
$query = "
    SELECT o.*,
           COALESCE(r.rating, 0)  AS rating,
           COALESCE(r.review, '') AS review
    FROM orders o
    LEFT JOIN order_ratings r ON r.order_id = o.id
    WHERE 1=1
";

if (!empty($filterStatus)) {
    $escapedStatus = $conn->real_escape_string($filterStatus);
    $query .= " AND o.status = '$escapedStatus'";
}

if (!empty($searchQuery)) {
    $escapedSearch = $conn->real_escape_string("%$searchQuery%");
    $query .= " AND (o.id LIKE '$escapedSearch' OR o.customer_name LIKE '$escapedSearch' OR o.customer_email LIKE '$escapedSearch')";
}

$query .= " ORDER BY o.created_at DESC";

$result = $conn->query($query);
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Count per status for summary strip
$statusCounts = [];
$countResult = $conn->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $statusCounts[$row['status']] = $row['cnt'];
    }
}

// Helpers
function statusBadge($status) {
    $map = [
        'pending'   => ['label' => '⏳ Pending',   'class' => 'badge-pending'],
        'preparing' => ['label' => '🔧 Preparing', 'class' => 'badge-preparing'],
        'shipped'   => ['label' => '🚚 Shipped',   'class' => 'badge-shipped'],
        'completed' => ['label' => '✅ Completed', 'class' => 'badge-completed'],
        'refunded'  => ['label' => '↩ Refunded',  'class' => 'badge-refunded'],
        'cancelled' => ['label' => '✗ Cancelled', 'class' => 'badge-cancelled'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-pending'];
    return "<span class=\"status-badge {$s['class']}\">{$s['label']}</span>";
}

function starRating($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating ? '<span class="star filled">★</span>' : '<span class="star">★</span>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders</title>
    <link rel="stylesheet" href="./style.css">
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
            transition: border-color 0.2s, transform 0.2s;
        }

        .summary-card:hover { transform: translateY(-3px); border-color: #555; }
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
        .btn-rate {
            background: none;
            border: 1px solid #e8c547;
            color: #e8c547;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 0;
            transition: all 0.2s;
        }
        .btn-rate:hover { background: #e8c547; color: #000; }

        /* Rating column */
        .review-text {
            font-size: 11px;
            color: #888;
            font-style: italic;
            margin-top: 3px;
            max-width: 130px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

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
            position: relative;
            flex: 1;
            min-width: 220px;
            display: flex;
            align-items: center;
        }
        .filter-bar .search-input {
            flex: 1;
            padding: 9px 42px 9px 10px;
            border: 1px solid #2a2a32;
            border-radius: 4px;
            background: #0d0d0f;
            color: #f0f0f0;
            font-size: 13px;
            width: 100%;
        }
        .filter-bar .search-input:focus { outline: none; border-color: #f1ff99; }
        .filter-bar .btn-search-icon {
            position: absolute;
            right: 8px;
            background: #47d4ff;
            border: none;
            color: #000;
            font-size: 13px;
            cursor: pointer;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin-top: 0;
            font-weight: bold;
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

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.78);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #141417;
            border: 1px solid #2a2a32;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 420px;
            position: relative;
        }
        .modal-box h3 { color: #e8ff47; margin-bottom: 20px; font-size: 18px; }
        .modal-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: none;
            border: none;
            color: #888;
            font-size: 22px;
            cursor: pointer;
            margin-top: 0;
            width: auto;
            padding: 0;
            line-height: 1;
        }
        .modal-close:hover { color: #f0f0f0; background: none; }

        /* Star Picker */
        .star-picker {
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
            /* Right-to-left trick for pure CSS hover chain */
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .star-picker input { display: none; }
        .star-picker label {
            font-size: 34px;
            color: #2a2a32;
            cursor: pointer;
            margin-top: 0;
            transition: color 0.12s;
        }
        .star-picker label:hover,
        .star-picker label:hover ~ label,
        .star-picker input:checked ~ label { color: #e8c547; }

        .modal-box textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #2a2a32;
            border-radius: 4px;
            background: #1c1c21;
            color: #f0f0f0;
            font-size: 13px;
            resize: vertical;
            min-height: 80px;
            font-family: Arial, sans-serif;
            margin-bottom: 16px;
        }
        .modal-box textarea:focus { outline: none; border-color: #f1ff99; }

        .btn-submit-rating {
            background: #e8ff47;
            color: #000;
            border: none;
            border-radius: 4px;
            padding: 11px 20px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 0;
        }
        .btn-submit-rating:hover { background: #f0ff66; }

        .empty-message { text-align: center; color: #888; padding: 40px 20px; font-style: italic; }

        @media (max-width: 768px) {
            .orders-table th:nth-child(4),
            .orders-table td:nth-child(4) { display: none; }
            .status-select-form { flex-direction: column; }
        }
    </style>
</head>
<body class="view_orders">

<div class="container">
    <h2>📦 View Orders</h2>

    <?php if ($successMessage): ?>
        <div class="success-message">✓ <?php echo $successMessage; ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="error-message">✗ <?php echo $errorMessage; ?></div>
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
            <button type="submit" class="btn-search-icon">🔍</button>
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
                        <th>Rating</th>
                        <th>Update Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <!-- Order ID -->
                        <td><span class="order-id">#<?php echo intval($order['id']); ?></span></td>

                        <!-- Customer -->
                        <td>
                            <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div class="customer-email"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                        </td>

                        <!-- Total -->
                        <td class="order-total">₱<?php echo number_format(floatval($order['total_amount']), 2); ?></td>

                        <!-- Date -->
                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>

                        <!-- Status Badge -->
                        <td><?php echo statusBadge($order['status']); ?></td>

                        <!-- Rating -->
                        <td>
                            <?php if ($order['rating'] > 0): ?>
                                <div><?php echo starRating($order['rating']); ?></div>
                                <?php if (!empty($order['review'])): ?>
                                    <div class="review-text" title="<?php echo htmlspecialchars($order['review']); ?>">
                                        "<?php echo htmlspecialchars($order['review']); ?>"
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#555; font-size:12px;">No rating yet</span>
                            <?php endif; ?>
                        </td>

                        <!-- Update Status (inline dropdown) -->
                        <td>
                            <form method="POST" class="status-select-form">
                                <input type="hidden" name="action"   value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo intval($order['id']); ?>">
                                <select name="status">
                                    <?php foreach (['pending','preparing','shipped','completed','refunded','cancelled'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $order['status'] === $s ? 'selected' : ''; ?>>
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
                                <?php if (!in_array($order['status'], ['refunded','cancelled'])): ?>
                                    <form method="POST" onsubmit="return confirm('Mark Order #<?php echo intval($order['id']); ?> as Refunded?')">
                                        <input type="hidden" name="action"   value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo intval($order['id']); ?>">
                                        <input type="hidden" name="status"   value="refunded">
                                        <button type="submit" class="btn-quick btn-refund">↩ Refund</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!in_array($order['status'], ['completed','cancelled','refunded'])): ?>
                                    <form method="POST" onsubmit="return confirm('Mark Order #<?php echo intval($order['id']); ?> as Refunded?')">
                                        <input type="hidden" name="action"   value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo intval($order['id']); ?>">
                                        <input type="hidden" name="status"   value="completed">
                                        <button type="submit" class="btn-quick btn-complete">✓ Complete</button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="btn-rate"
                                        onclick="openRatingModal(
                                            <?php echo intval($order['id']); ?>,
                                            <?php echo intval($order['rating']); ?>,
                                            <?php echo json_encode($order['review']); ?>
                                        )">
                                    ★ <?php echo $order['rating'] > 0 ? 'Edit Rating' : 'Rate'; ?>
                                </button>
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

<!-- ── Rating Modal ── -->
<div class="modal-overlay" id="ratingModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeRatingModal()">✕</button>
        <h3>⭐ Rate Order <span id="modalOrderLabel"></span></h3>
        <form method="POST" id="ratingForm">
            <input type="hidden" name="action"   value="submit_rating">
            <input type="hidden" name="order_id" id="modalOrderId">

            <label>Your Rating:</label>
            <div class="star-picker">
                <!-- reversed for CSS sibling trick -->
                <input type="radio" name="rating" id="star5" value="5"><label for="star5">★</label>
                <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
                <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
                <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
                <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
            </div>

            <label for="reviewText">Review (optional):</label>
            <textarea id="reviewText" name="review" placeholder="Share your thoughts about this order..."></textarea>

            <button type="submit" class="btn-submit-rating">Submit Rating</button>
        </form>
    </div>
</div>

<script>
    function openRatingModal(orderId, currentRating, currentReview) {
        document.getElementById('modalOrderId').value          = orderId;
        document.getElementById('modalOrderLabel').textContent = '#' + orderId;
        document.getElementById('reviewText').value            = currentReview || '';

        // Clear all, then pre-check if rated
        document.querySelectorAll('.star-picker input').forEach(r => r.checked = false);
        if (currentRating > 0) {
            const radio = document.getElementById('star' + currentRating);
            if (radio) radio.checked = true;
        }

        document.getElementById('ratingModal').classList.add('active');
    }

    function closeRatingModal() {
        document.getElementById('ratingModal').classList.remove('active');
    }

    // Close on backdrop click
    document.getElementById('ratingModal').addEventListener('click', function(e) {
        if (e.target === this) closeRatingModal();
    });
</script>
</body>
</html>