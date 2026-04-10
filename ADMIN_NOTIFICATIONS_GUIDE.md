# Admin Notifications System Guide

## Overview
The admin notifications system has been added to notify administrators about important order events including:
- **New Orders** - When a new order is placed
- **Refunds** - When an order is refunded
- **Cancellations** - When an order is cancelled

## Database Setup

### New Table: `admin_notifications`
A new table has been created with the following structure:
- `notification_id` - Primary key (auto-increment)
- `order_id` - Related order ID (foreign key to orders table)
- `type` - Notification type ('order', 'refund', 'cancel', 'system')
- `title` - Notification title
- `message` - Notification message
- `is_read` - Whether the notification has been read (0 or 1)
- `created_at` - Timestamp of creation (auto-set)
- `updated_at` - Timestamp of last update (auto-set)

**To create this table in your database:**
- Run the updated `ipt_act2.sql` file which includes the admin_notifications table definition
- Or execute the following SQL directly:

```sql
CREATE TABLE `admin_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'order',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `order_id` (`order_id`),
  KEY `created_at` (`created_at`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Components

### 1. Admin Notifications Helper File
**Location:** `includes/admin_notifications.php`

**Available Functions:**

```php
// Get unread notifications count
getAdminUnreadNotificationsCount($conn)

// Get all admin notifications with pagination
getAdminNotifications($conn, $limit = 50, $offset = 0)

// Create a new admin notification
createAdminNotification($order_id, $type, $title, $message, $conn)

// Mark single notification as read
markAdminNotificationAsRead($notification_id, $conn)

// Mark all notifications as read
markAllAdminNotificationsAsRead($conn)

// Delete an admin notification
deleteAdminNotification($notification_id, $conn)

// Format notification timestamp
formatAdminNotificationTime($timestamp)

// Get notification icon and color by type
getAdminNotificationStyle($type)

// Get total count of admin notifications
getAdminNotificationsCount($conn)
```

### 2. Admin Notifications Page
**Location:** `admin/notifications.php`

Features:
- View all admin notifications with pagination (20 per page)
- Mark notifications as read individually
- Mark all unread notifications as read at once
- Delete notifications
- Link to view related orders
- Real-time notification count badge
- Different icons and colors for different notification types:
  - Blue - New Orders
  - Orange - Refunds
  - Red - Cancellations
  - ⚙️ Gray - System

### 3. Admin Header Updates
All admin pages now include:
- Navigation link to the Notifications page
- Real-time unread notification badge/counter
- Updated pages:
  - `admin/index.php` (Dashboard)
  - `admin/view_order.php` (Orders)
  - `admin/manage_product.php` (Products)
  - `admin/edit_product.php` (Edit Product)
  - `admin/upload_product.php` (Upload Product)

### 4. Order Status Updates
The order management system (`admin/view_order.php`) now automatically creates admin notifications when:
- Order status changes to **"Pending"** → "New Order" notification
- Order status changes to **"Refunded"** → "Refund" notification
- Order status changes to **"Cancelled"** → "Cancellation" notification

## How to Use

### For Admins:
1. **View Notifications:**
   - Click on "Notifications" in the admin navigation menu
   - See all notifications with newest first
   - Unread notifications appear with a blue background

2. **Mark as Read:**
   - Click the "Mark as Read" button on individual notifications
   - Click "Mark All as Read" at the top to read all at once

3. **Delete Notifications:**
   - Click "Delete" button on any notification
   - Confirm the deletion

4. **View Related Order:**
   - Click "View Order" button to navigate to the order details

### For Developers:
To manually create admin notifications in your code:

```php
// Include the notifications helper
require_once 'includes/admin_notifications.php';

// Create a notification when an order is placed
createAdminNotification(
    $order_id,
    'order',  // type: 'order', 'refund', 'cancel', or 'system'
    'New Order #' . $order_id,  // title
    'A new order has been placed.',  // message
    $conn
);

// Create a notification for refund
createAdminNotification(
    $order_id,
    'refund',
    'Order Refunded',
    'Order #' . $order_id . ' has been refunded.',
    $conn
);

// Create a notification for cancellation
createAdminNotification(
    $order_id,
    'cancel',
    'Order Cancelled',
    'Order #' . $order_id . ' has been cancelled.',
    $conn
);
```

## Notification Types

| Type | Icon | Color | Label |
|------|------|-------|-------|
| order | | Blue (#0066cc) | New Order |
| refund | | Orange (#cc6600) | Refund |
| cancel | | Red (#cc0000) | Cancelled |
| system | ⚙️ | Gray (#666666) | System |

## Features

[✓] Real-time unread notice count on admin pages
[✓] Automatic notifications for order status changes
[✓] Mark notifications as read
[✓] Delete notifications
[✓] Pagination support (20 notifications per page)
[✓] Quick link to view related orders
[✓] Timestamp formatting (e.g., "5 minutes ago", "2 days ago")
[✓] Event-based icons and colors
[✓] Clean, modern UI
[✓] Mobile responsive design

## Database Queries

To manually check notifications:

```sql
-- Get all unread admin notifications
SELECT * FROM admin_notifications 
WHERE is_read = 0 
ORDER BY created_at DESC;

-- Get admin notifications for a specific order
SELECT * FROM admin_notifications 
WHERE order_id = ? 
ORDER BY created_at DESC;

-- Get count of unread notifications
SELECT COUNT(*) as unread_count 
FROM admin_notifications 
WHERE is_read = 0;

-- Get notifications by type
SELECT * FROM admin_notifications 
WHERE type = 'refund' 
AND is_read = 0 
ORDER BY created_at DESC;

-- Get notifications grouped by type
SELECT type, COUNT(*) as count 
FROM admin_notifications 
GROUP BY type;

-- Mark all notifications as read
UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0;

-- Delete old notifications (older than 30 days)
DELETE FROM admin_notifications 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Customization

### Changing Notification Icons and Colors
Edit `includes/admin_notifications.php` and modify the `getAdminNotificationStyle()` function to customize icons and colors for each notification type.

### Changing Notification Limit
In `admin/notifications.php`, modify the `$limit` variable (default is 20):
```php
$limit = 50;  // Show 50 notifications per page instead of 20
```

### Adding New Notification Types
1. Add the new type to the `getAdminNotificationStyle()` function in `admin_notifications.php`
2. Update the SQL queries to handle the new type
3. Call `createAdminNotification()` with the new type when appropriate

## Troubleshooting

**Notifications not appearing?**
- Ensure the `admin_notifications` table has been created in your database
- Check that the database connection (`db_connect.php`) is working properly
- Verify that the `admin_notifications.php` include file is properly included

**Notification count not updating?**
- Make sure the `getAdminUnreadNotificationsCount()` function is called on each page
- Check browser cache and clear it if needed

**Notifications not being created automatically?**
- Verify that `admin_notifications.php` is included in `view_order.php`
- Check that `createAdminNotification()` is being called when order status changes
- Review the debug logs if available

## Future Enhancements

Potential features to add:
- Email notifications to admin for urgent alerts
- Notification preferences/settings
- Bulk actions (mark multiple as read, delete multiple)
- Notification search/filter by type or date range
- Summary dashboard showing notification statistics
- Push notifications in real-time using WebSockets
- Notification archiving instead of deletion

---

**Last Updated:** April 10, 2026
**Version:** 1.0
