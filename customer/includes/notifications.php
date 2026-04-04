<?php
// Notification Helper Functions
// Include guard to prevent redeclaration
if (defined('NOTIFICATIONS_INCLUDED')) {
    return;
}
define('NOTIFICATIONS_INCLUDED', true);

/**
 * Get unread notifications count for a user
 */
function getUnreadNotificationsCount($user_id, $conn) {
    $query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['unread_count'];
}

/**
 * Get all notifications for a user
 */
function getUserNotifications($user_id, $conn, $limit = 50, $offset = 0) {
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create a notification
 */
function createNotification($user_id, $type, $title, $message, $conn, $order_id = null) {
    $query = "INSERT INTO notifications (user_id, type, title, message, order_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssi", $user_id, $type, $title, $message, $order_id);
    return $stmt->execute();
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id, $conn) {
    $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead($user_id, $conn) {
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

/**
 * Delete a notification
 */
function deleteNotification($notification_id, $conn) {
    $query = "DELETE FROM notifications WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

/**
 * Format notification time
 */
function formatNotificationTime($timestamp) {
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
 * Get notification icon/color based on type
 */
function getNotificationStyle($type) {
    $styles = [
        'order' => ['icon' => '[Order]', 'color' => '#007bff'],
        'shipped' => ['icon' => '[Shipped]', 'color' => '#28a745'],
        'delivered' => ['icon' => '[Delivered]', 'color' => '#20c997'],
        'cancelled' => ['icon' => '[Cancelled]', 'color' => '#dc3545'],
        'system' => ['icon' => '[Alert]', 'color' => '#6c757d'],
        'message' => ['icon' => '[Message]', 'color' => '#17a2b8']
    ];
    return $styles[$type] ?? $styles['system'];
}
?>
