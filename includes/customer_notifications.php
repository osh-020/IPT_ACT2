<?php
/**
 * Customer Notifications Helper Functions
 * Handles all customer-related notifications (orders, shipping, etc.)
 * Include guard to prevent redeclaration
 */
if (defined('CUSTOMER_NOTIFICATIONS_INCLUDED')) {
    return;
}
define('CUSTOMER_NOTIFICATIONS_INCLUDED', true);

/**
 * Get unread notifications count for a customer
 * @param int $user_id - Customer ID
 * @param mysqli $conn - Database connection
 * @return int - Count of unread notifications
 */
function getCustomerUnreadNotificationsCount($user_id, $conn) {
    $query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['unread_count'];
}

/**
 * Get all notifications for a customer
 * @param int $user_id - Customer ID
 * @param mysqli $conn - Database connection
 * @param int $limit - Results per page
 * @param int $offset - Pagination offset
 * @return array - Array of notifications
 */
function getCustomerNotifications($user_id, $conn, $limit = 50, $offset = 0) {
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

/**
 * Create a notification for a customer
 * @param int $user_id - Customer ID
 * @param string $type - Notification type (order, shipped, delivered, cancelled, system, message)
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param mysqli $conn - Database connection
 * @param int|null $order_id - Related order ID (optional)
 * @return bool - True if successful
 */
function createCustomerNotification($user_id, $type, $title, $message, $conn, $order_id = null) {
    $query = "INSERT INTO notifications (user_id, type, title, message, order_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("isssi", $user_id, $type, $title, $message, $order_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Mark a single notification as read
 * @param int $notification_id - Notification ID
 * @param mysqli $conn - Database connection
 * @return bool - True if successful
 */
function markCustomerNotificationAsRead($notification_id, $conn) {
    $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Mark all notifications as read for a customer
 * @param int $user_id - Customer ID
 * @param mysqli $conn - Database connection
 * @return bool - True if successful
 */
function markAllCustomerNotificationsAsRead($user_id, $conn) {
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Delete a notification
 * @param int $notification_id - Notification ID
 * @param mysqli $conn - Database connection
 * @return bool - True if successful
 */
function deleteCustomerNotification($notification_id, $conn) {
    $query = "DELETE FROM notifications WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get total count of notifications for a customer
 * @param int $user_id - Customer ID
 * @param mysqli $conn - Database connection
 * @return int - Total notification count
 */
function getCustomerNotificationsCount($user_id, $conn) {
    $query = "SELECT COUNT(*) as total_count FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total_count'];
}

/**
 * Format notification time (relative time format)
 * @param string $timestamp - Timestamp string
 * @return string - Formatted time (e.g., "5 minutes ago")
 */
function formatCustomerNotificationTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . " minute" . ($mins != 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . " hour" . ($hours != 1 ? "s" : "") . " ago";
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . " day" . ($days != 1 ? "s" : "") . " ago";
    } else {
        return date("M d, Y", $time);
    }
}

/**
 * Get notification styling (icon and color) based on type
 * @param string $type - Notification type
 * @return array - Array with icon and color
 */
function getCustomerNotificationStyle($type) {
    $styles = [
        'order' => [
            'icon' => '📦',
            'color' => '#007bff',
            'label' => 'Order',
            'bg_color' => '#e7f3ff'
        ],
        'shipped' => [
            'icon' => '🚚',
            'color' => '#28a745',
            'label' => 'Shipped',
            'bg_color' => '#e8f5e9'
        ],
        'delivered' => [
            'icon' => '✅',
            'color' => '#20c997',
            'label' => 'Delivered',
            'bg_color' => '#e0f2f1'
        ],
        'cancelled' => [
            'icon' => '❌',
            'color' => '#dc3545',
            'label' => 'Cancelled',
            'bg_color' => '#ffebee'
        ],
        'refunded' => [
            'icon' => '💰',
            'color' => '#ff9800',
            'label' => 'Refunded',
            'bg_color' => '#fff3e0'
        ],
        'system' => [
            'icon' => '⚙️',
            'color' => '#6c757d',
            'label' => 'System Alert',
            'bg_color' => '#f5f5f5'
        ],
        'message' => [
            'icon' => '💬',
            'color' => '#17a2b8',
            'label' => 'Message',
            'bg_color' => '#e0f7fa'
        ]
    ];
    return $styles[$type] ?? $styles['system'];
}

/**
 * Delete all old notifications for a customer (older than specified days)
 * @param int $user_id - Customer ID
 * @param int $days - Delete notifications older than this many days
 * @param mysqli $conn - Database connection
 * @return bool - True if successful
 */
function deleteOldCustomerNotifications($user_id, $days = 30, $conn) {
    $query = "DELETE FROM notifications WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ii", $user_id, $days);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get notifications by type for a customer
 * @param int $user_id - Customer ID
 * @param string $type - Notification type to filter by
 * @param mysqli $conn - Database connection
 * @return array - Array of filtered notifications
 */
function getCustomerNotificationsByType($user_id, $type, $conn) {
    $query = "SELECT * FROM notifications WHERE user_id = ? AND type = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

/**
 * Get notifications for a specific order
 * @param int $user_id - Customer ID
 * @param int $order_id - Order ID
 * @param mysqli $conn - Database connection
 * @return array - Array of order-related notifications
 */
function getCustomerOrderNotifications($user_id, $order_id, $conn) {
    $query = "SELECT * FROM notifications WHERE user_id = ? AND order_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("ii", $user_id, $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

/**
 * BACKWARD COMPATIBILITY ALIASES
 * These functions maintain compatibility with old code
 * New code should use the Customer-prefixed versions above
 */

// Alias for getCustomerUnreadNotificationsCount
function getUnreadNotificationsCount($user_id, $conn) {
    return getCustomerUnreadNotificationsCount($user_id, $conn);
}

// Alias for getCustomerNotifications
function getUserNotifications($user_id, $conn, $limit = 50, $offset = 0) {
    return getCustomerNotifications($user_id, $conn, $limit, $offset);
}

// Alias for createCustomerNotification
function createNotification($user_id, $type, $title, $message, $conn, $order_id = null) {
    return createCustomerNotification($user_id, $type, $title, $message, $conn, $order_id);
}

// Alias for markCustomerNotificationAsRead
function markNotificationAsRead($notification_id, $conn) {
    return markCustomerNotificationAsRead($notification_id, $conn);
}

// Alias for markAllCustomerNotificationsAsRead
function markAllNotificationsAsRead($user_id, $conn) {
    return markAllCustomerNotificationsAsRead($user_id, $conn);
}

// Alias for deleteCustomerNotification
function deleteNotification($notification_id, $conn) {
    return deleteCustomerNotification($notification_id, $conn);
}

// Alias for formatCustomerNotificationTime
function formatNotificationTime($timestamp) {
    return formatCustomerNotificationTime($timestamp);
}

// Alias for getCustomerNotificationStyle
function getNotificationStyle($type) {
    return getCustomerNotificationStyle($type);
}

?>
