<?php
session_start();
require_once './header.php';
require_once '../includes/db_connect.php';
require_once '../includes/customer_notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle mark as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    markNotificationAsRead($notification_id, $conn);
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    markAllNotificationsAsRead($user_id, $conn);
}

// Handle delete all notifications
if (isset($_POST['delete_all_notifications'])) {
    $delete_query = "DELETE FROM notifications WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: notifications.php");
    exit();
}

// Handle delete notification
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    deleteNotification($notification_id, $conn);
}

// Get pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$filter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';

// Build query with filter
$count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$query_params = [$user_id];

if ($filter !== 'all') {
    $status_map = [
        'to_pay' => 'pending',
        'to_ship' => 'processing',
        'to_receive' => 'shipped',
        'completed' => 'delivered',
        'return_refund' => 'refunded',
        'cancelled' => 'cancelled'
    ];
    $status = $status_map[$filter] ?? '';
    if ($status) {
        $count_query .= " AND type = ?";
        $query_params[] = $filter;
    }
}

// Get total notifications count
$count_stmt = $conn->prepare($count_query);
if (!empty($query_params)) {
    $types = str_repeat('s', count($query_params));
    $count_stmt->bind_param($types, ...$query_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_notifications = $count_row['total'];
$total_pages = ceil($total_notifications / $limit);

// Get notifications with filter
if ($filter === 'all') {
    $notifications = getUserNotifications($user_id, $conn, $limit, $offset);
} else {
    $notif_query = "SELECT * FROM notifications WHERE user_id = ? AND type = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $notif_stmt = $conn->prepare($notif_query);
    $notif_stmt->bind_param("isii", $user_id, $filter, $limit, $offset);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    $notifications = [];
    while ($row = $notif_result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $notif_stmt->close();
}

// Get unread count
$unread_count = getUnreadNotificationsCount($user_id, $conn);
?>

<main class="main-content">
    <div class="notifications-container" style="max-width: 900px; margin: 40px auto; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>Notifications</h1>
            <div style="display: flex; gap: 10px;">
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-primary" style="padding: 10px 20px;">
                            Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($total_notifications > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="delete_all_notifications" class="btn btn-danger" onclick="return confirm('Delete all notifications? This cannot be undone.')" style="padding: 10px 20px; background-color: #dc3545; color: white; border: none; border-radius: 0; cursor: pointer;">
                            Delete All
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">
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
                $filter_url = 'notifications.php?filter=' . $filter_key;
                $style = $isActive ? 'background-color: #e8ff47; color: #000; font-weight: bold;' : 'background-color: #2a2a32; color: #fff;';
            ?>
                <a href="<?php echo $filter_url; ?>" style="<?php echo $style; ?> padding: 10px 15px; border-radius: 0; text-decoration: none; border: 1px solid #e8ff47; cursor: pointer; transition: all 0.3s;">
                    <?php echo $filter_label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 50px 20px; border: 1px solid #ddd; border-radius: 0;">
                <p style="font-size: 18px; color: #666;">No notifications yet</p>
                <p style="color: #999;">Your notifications will appear here</p>
            </div>
        <?php else: ?>
            <div style="border: 1px solid #ddd; border-radius: 0; overflow: hidden;">
                <?php foreach ($notifications as $notif): 
                    $style = getNotificationStyle($notif['type']);
                    $read_class = $notif['is_read'] ? 'read' : 'unread';
                    $time_text = formatNotificationTime($notif['created_at']);
                    $notification_link = $notif['order_id'] ? 'view_orders.php?order_id=' . $notif['order_id'] : '#';
                ?>
                    <a href="<?php echo $notification_link; ?>" style="text-decoration: none; color: inherit; display: block;">
                        <div style="padding: 20px; border-bottom: 1px solid #ddd; background-color: #2a2a32; display: flex; justify-content: space-between; align-items: flex-start; cursor: <?php echo $notif['order_id'] ? 'pointer' : 'default'; ?>; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#333';" onmouseout="this.style.backgroundColor='#2a2a32';">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <h3 style="margin: 0; color: <?php echo !$notif['is_read'] ? '#fff' : '#eee'; ?>;">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                        <?php if (!$notif['is_read']): ?>
                                            <span style="display: inline-block; width: 8px; height: 8px; background-color: #ffff00; border-radius: 50%; margin-left: 10px;"></span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                                <p style="margin: 0 0 8px 0; color: <?php echo !$notif['is_read'] ? '#f0f0f0' : '#ccc'; ?>;">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                                <p style="margin: 0 0 0 0; color: <?php echo !$notif['is_read'] ? '#e0e0e0' : '#bbb'; ?>; font-size: 14px;">
                                    <?php echo $time_text; ?>
                                    <?php if ($notif['order_id']): ?>
                                        | Order #<?php echo $notif['order_id']; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div style="margin-left: 20px; display: flex; gap: 10px;" onclick="event.preventDefault(); event.stopPropagation();">
                                <?php if (!$notif['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo $notif['notification_id']; ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm" style="padding: 8px 12px; background-color: #4CAF50; color: white; border: none; border-radius: 0; cursor: pointer;">
                                            Mark Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['notification_id']; ?>">
                                    <button type="submit" name="delete_notification" class="btn btn-sm" onclick="return confirm('Delete this notification?')" style="padding: 8px 12px; background-color: #f44336; color: white; border: none; border-radius: 0; cursor: pointer;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 30px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="notifications.php?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" class="btn btn-secondary" style="margin: 0 5px; padding: 10px 15px;">← Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span style="margin: 0 5px; padding: 10px 15px; background-color: #007bff; color: white; border-radius: 0;">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="notifications.php?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" class="btn btn-secondary" style="margin: 0 5px; padding: 10px 15px;">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="notifications.php?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" class="btn btn-secondary" style="margin: 0 5px; padding: 10px 15px;">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

