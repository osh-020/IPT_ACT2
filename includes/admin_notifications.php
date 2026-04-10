<?php
// Admin Notification Helper Functions
// Include guard to prevent redeclaration
if (defined('ADMIN_NOTIFICATIONS_INCLUDED')) {
    return;
}
define('ADMIN_NOTIFICATIONS_INCLUDED', true);

/**
 * Check if admin_notifications table exists
 */
function adminNotificationsTableExists($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
    return $result && $result->num_rows > 0;
}

/**
 * Get unread admin notifications count
 */
function getAdminUnreadNotificationsCount($conn) {
    // Return 0 if table doesn't exist yet
    if (!adminNotificationsTableExists($conn)) {
        return 0;
    }
    
    $query = "SELECT COUNT(*) as unread_count FROM admin_notifications WHERE is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['unread_count'];
}

/**
 * Get all admin notifications
 */
function getAdminNotifications($conn, $limit = 50, $offset = 0) {
    if (!adminNotificationsTableExists($conn)) {
        return [];
    }
    
    $query = "SELECT an.*, o.order_id, o.full_name, o.user_id, o.total 
              FROM admin_notifications an
              LEFT JOIN orders o ON an.order_id = o.order_id
              ORDER BY an.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create an admin notification
 */
function createAdminNotification($order_id, $type, $title, $message, $conn) {
    if (!adminNotificationsTableExists($conn)) {
        return false;
    }
    
    $query = "INSERT INTO admin_notifications (order_id, type, title, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $order_id, $type, $title, $message);
    return $stmt->execute();
}

/**
 * Mark admin notification as read
 */
function markAdminNotificationAsRead($notification_id, $conn) {
    if (!adminNotificationsTableExists($conn)) {
        return false;
    }
    
    $query = "UPDATE admin_notifications SET is_read = 1 WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

/**
 * Mark all admin notifications as read
 */
function markAllAdminNotificationsAsRead($conn) {
    if (!adminNotificationsTableExists($conn)) {
        return false;
    }
    
    $query = "UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0";
    $stmt = $conn->prepare($query);
    return $stmt->execute();
}

/**
 * Delete an admin notification
 */
function deleteAdminNotification($notification_id, $conn) {
    if (!adminNotificationsTableExists($conn)) {
        return false;
    }
    
    $query = "DELETE FROM admin_notifications WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

/**
 * Format notification time
 */
function formatAdminNotificationTime($timestamp) {
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
 * Get notification icon and color by type
 */
function getAdminNotificationStyle($type) {
    $styles = [
        'order' => ['icon' => '📦', 'color' => '#0066cc', 'label' => 'New Order'],
        'refund' => ['icon' => '💰', 'color' => '#cc6600', 'label' => 'Refund'],
        'cancel' => ['icon' => '❌', 'color' => '#cc0000', 'label' => 'Cancelled'],
        'system' => ['icon' => '⚙️', 'color' => '#666666', 'label' => 'System'],
    ];
    return $styles[$type] ?? $styles['system'];
}

/**
 * Get total count of admin notifications
 */
function getAdminNotificationsCount($conn) {
    if (!adminNotificationsTableExists($conn)) {
        return 0;
    }
    
    $query = "SELECT COUNT(*) as total_count FROM admin_notifications";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_count'];
}

?>

