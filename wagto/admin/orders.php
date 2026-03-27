<?php
$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../includes/header.php';

// Sample orders data (in real app, this would come from database)
$orders = [
    [
        'id' => 'ORD-001',
        'customer' => 'John Doe',
        'email' => 'john@example.com',
        'items' => 2,
        'total' => 1299.99,
        'status' => 'Completed',
        'date' => '2026-03-25 14:30:00',
        'products' => 'Ryzen 9 7950X, RTX 4090'
    ],
    [
        'id' => 'ORD-002',
        'customer' => 'Jane Smith',
        'email' => 'jane@example.com',
        'items' => 1,
        'total' => 599.99,
        'status' => 'Pending',
        'date' => '2026-03-26 10:15:00',
        'products' => 'RTX 4070 Super'
    ],
    [
        'id' => 'ORD-003',
        'customer' => 'Mike Johnson',
        'email' => 'mike@example.com',
        'items' => 3,
        'total' => 2199.98,
        'status' => 'Processing',
        'date' => '2026-03-27 09:00:00',
        'products' => 'Core i9-14900K, Vengeance DDR5 32GB, 990 Pro 2TB'
    ],
];

$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'] ?? '';
    $newStatus = $_POST['order_status'] ?? '';
    
    if ($orderId && $newStatus) {
        $message = "✓ Order status updated to '{$newStatus}'!";
    }
}
?>

<div class="header">
    <h1>Manage Orders</h1>
</div>

<?php if ($message): ?>
    <div class="toast success" style="display: block; position: static; margin-bottom: 1rem; animation: none;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- ORDERS TABLE -->
<?php if (count($orders) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Products</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($order['id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($order['customer']); ?></td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                    <td><small><?php echo htmlspecialchars($order['products']); ?></small></td>
                    <td><?php echo $order['items']; ?></td>
                    <td><strong style="color: var(--accent);">$<?php echo number_format($order['total'], 2); ?></strong></td>
                    <td>
                        <span style="
                            padding: 0.3rem 0.8rem;
                            border-radius: 20px;
                            font-size: 0.75rem;
                            font-weight: 600;
                            background: <?php 
                                switch($order['status']) {
                                    case 'Completed': echo 'rgba(76, 175, 80, 0.2); color: #4caf50;'; break;
                                    case 'Processing': echo 'rgba(232, 255, 71, 0.2); color: var(--accent);'; break;
                                    case 'Pending': echo 'rgba(71, 212, 255, 0.2); color: var(--accent2);'; break;
                                    case 'Cancelled': echo 'rgba(255, 71, 71, 0.2); color: var(--danger);'; break;
                                    default: echo 'rgba(136, 136, 136, 0.2); color: var(--muted);';
                                }
                            ?>
                        ">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($order['date']); ?></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn-view" onclick="viewOrder('<?php echo htmlspecialchars($order['id']); ?>')">View</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="order_status" style="padding: 0.4rem; font-size: 0.8rem; border-radius: 6px; background: var(--surface2); border: 1px solid var(--border); color: var(--text);" onchange="this.form.submit();">
                                    <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <h3>No orders yet</h3>
        <p>Orders will appear here once customers start placing them.</p>
    </div>
<?php endif; ?>

<!-- ORDER DETAILS MODAL (Simple) -->
<div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--surface); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; border: 1px solid var(--border);">
        <h2 id="modalOrderId" style="font-family: 'Space Mono', monospace; margin-bottom: 1rem;"></h2>
        <div id="modalContent" style="margin-bottom: 1.5rem;"></div>
        <button onclick="document.getElementById('orderModal').style.display = 'none';" class="btn-cancel">Close</button>
    </div>
</div>

<script>
function viewOrder(orderId) {
    const orders = <?php echo json_encode($orders); ?>;
    const order = orders.find(o => o.id === orderId);
    
    if (order) {
        document.getElementById('modalOrderId').textContent = 'Order ' + order.id;
        document.getElementById('modalContent').innerHTML = `
            <p><strong>Customer:</strong> ${order.customer}</p>
            <p><strong>Email:</strong> ${order.email}</p>
            <p><strong>Products:</strong> ${order.products}</p>
            <p><strong>Items:</strong> ${order.items}</p>
            <p><strong>Total:</strong> $${order.total.toFixed(2)}</p>
            <p><strong>Status:</strong> ${order.status}</p>
            <p><strong>Date:</strong> ${order.date}</p>
        `;
        document.getElementById('orderModal').style.display = 'flex';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
