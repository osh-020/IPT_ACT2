<?php
session_start();
require_once dirname(__FILE__) . '/../includes/db_connect.php';
require_once dirname(__FILE__) . '/../includes/admin_notifications.php';

// Check if table exists
if (!adminNotificationsTableExists($conn)) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Required - Admin Notifications</title>
    <link rel="icon" type="image/png" href="../includes/website_pic/logo.png">
    <link rel="stylesheet" href="../includes/admin_style.css">

</head>
<body>
    <div class="setup-container">
        <div class="setup-icon">⚙️</div>
        <h1 class="setup-title">Setup Required</h1>
        <p class="setup-message">
            The admin notifications system needs to be initialized. 
            This is a one-time setup that creates the necessary database table.
        </p>
        <p>
            <a href="../admin_setup.php" class="setup-btn">Initialize Admin Notifications</a>
        </p>
        <p style="color: #999; margin-top: 20px; font-size: 14px;">
            Or go back to <a href="dashboard.php" class="setup-link">Admin Dashboard</a>
        </p>
    </div>
</body>
</html>
    <?php
    exit();
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'mark_read') {
        $notification_id = intval($_POST['notification_id'] ?? 0);
        if ($notification_id > 0) {
            markAdminNotificationAsRead($notification_id, $conn);
        }
        header("Location: notifications.php?page=$page");
        exit();
    }
    
    if ($_POST['action'] === 'mark_all_read') {
        markAllAdminNotificationsAsRead($conn);
        header("Location: notifications.php");
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $notification_id = intval($_POST['notification_id'] ?? 0);
        if ($notification_id > 0) {
            deleteAdminNotification($notification_id, $conn);
        }
        header("Location: notifications.php?page=$page");
        exit();
    }
}

// Get notifications
$notifications = getAdminNotifications($conn, $limit, $offset);
$totalNotifications = getAdminNotificationsCount($conn);
$totalPages = ceil($totalNotifications / $limit);
$unreadCount = getAdminUnreadNotificationsCount($conn);
?>
<?php include("header.php"); ?>
    <!-- Main Content -->
    <main class="admin-main">
        <div class="notifications-container">
            <!-- Notifications Header -->
            <div class="notifications-header">
                <div>
                    <h2>Notifications
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </h2>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="mark-all-btn">Mark All as Read</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Notifications List -->
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    
                    <p class="empty-state-text">No notifications yet</p>
                </div>
            <?php else: ?>
                <ul class="notifications-list">
                    <?php foreach ($notifications as $notification): 
                        $style = getAdminNotificationStyle($notification['type']);
                        $isUnread = $notification['is_read'] == 0;
                    ?>
                        <li class="notification-item <?php echo $isUnread ? 'unread' : ''; ?>">
                            <div class="notification-content">
                                <div class="notification-icon"><?php echo $style['icon']; ?></div>
                                <div class="notification-text">
                                    <p class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></p>
                                    <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <div class="notification-meta">
                                        <span class="notification-type"><?php echo $style['label']; ?></span>
                                        <span class="notification-time"><?php echo formatAdminNotificationTime($notification['created_at']); ?></span>
                                        <?php if ($notification['full_name']): ?>
                                            <span class="notification-type">Customer: <?php echo htmlspecialchars($notification['full_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <?php if ($notification['order_id']): ?>
                                    <a href="view_order.php?search=<?php echo $notification['order_id']; ?>" class="action-btn btn-view-order">View Order</a>
                                <?php endif; ?>
                                <?php if ($isUnread): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                        <button type="submit" class="action-btn btn-mark-read">Mark as Read</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                    <button type="submit" class="action-btn btn-delete" onclick="return confirm('Are you sure?');">Delete</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="notifications.php?page=1">&laquo; First</a>
                            <a href="notifications.php?page=<?php echo $page - 1; ?>">&lt; Previous</a>
                        <?php endif; ?>

                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1): ?>
                            <span>...</span>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="notifications.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <span>...</span>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="notifications.php?page=<?php echo $page + 1; ?>">Next &gt;</a>
                            <a href="notifications.php?page=<?php echo $totalPages; ?>">Last &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
