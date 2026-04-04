# Notification System Guide

## Overview
The notification system has been added to the customer_only folder. It allows customers to receive notifications about their orders and system messages.

## Database
A new `notifications` table has been created with the following structure:
- `notification_id` - Primary key
- `user_id` - Customer ID (foreign key to users table)
- `order_id` - Related order ID (optional, foreign key to orders table)
- `type` - Notification type (order, shipped, delivered, cancelled, system, message)
- `title` - Notification title
- `message` - Notification message
- `is_read` - Whether the notification has been read (0 or 1)
- `created_at` - Timestamp of creation
- `updated_at` - Timestamp of last update

## Components

### 1. Notifications Helper File
Location: `customer_only/includes/notifications.php`

**Available Functions:**

```php
// Get unread notifications count
getUnreadNotificationsCount($user_id, $conn)

// Get all notifications for a user
getUserNotifications($user_id, $conn, $limit = 50, $offset = 0)

// Create a new notification
createNotification($user_id, $type, $title, $message, $conn, $order_id = null)

// Mark single notification as read
markNotificationAsRead($notification_id, $conn)

// Mark all notifications as read
markAllNotificationsAsRead($user_id, $conn)

// Delete a notification
deleteNotification($notification_id, $conn)

// Format notification timestamp
formatNotificationTime($timestamp)

// Get notification icon and color by type
getNotificationStyle($type)
```

### 2. Notifications Page
Location: `customer_only/pages/notifications.php`

Features:
- View all notifications with pagination
- Mark notifications as read individually
- Mark all notifications as read at once
- Delete notifications
- Visual indicators for unread notifications
- Different icons and colors for different notification types

### 3. Header Update
The header now includes:
- Notification bell icon (🔔)
- Unread notification badge/counter
- Link to the notifications page

## How to Create Notifications

### Example 1: When a customer places an order
Add this in your checkout or order placement code:

```php
// After order is successfully created
$order_id = $new_order_id; // newly created order ID
$user_id = $_SESSION['user_id'];

createNotification(
    $user_id, 
    'order', 
    'Order Placed', 
    'Your order has been successfully placed and is being processed.', 
    $conn, 
    $order_id
);
```

### Example 2: When order status changes to "Shipped"
Add this in your admin panel when updating order status:

```php
// In admin order update code
$order_id = $_POST['order_id'];
$user_id = getUserIdFromOrderId($order_id, $conn); // Get user ID

createNotification(
    $user_id,
    'shipped',
    'Order Shipped',
    'Your order #' . $order_id . ' has been shipped! Track your package soon.',
    $conn,
    $order_id
);
```

### Example 3: When order is delivered
```php
createNotification(
    $user_id,
    'delivered',
    'Order Delivered',
    'Your order #' . $order_id . ' has been delivered successfully!',
    $conn,
    $order_id
);
```

### Example 4: System/Admin message
```php
createNotification(
    $user_id,
    'system',
    'Special Promotion',
    'Check out our new summer sale - up to 50% off on selected items!',
    $conn
);
```

## Notification Types
The system supports the following notification types:

| Type | Icon | Color | Use Case |
|------|------|-------|----------|
| order | 📦 | Blue | Order placed or order updates |
| shipped | 🚚 | Green | Order has been shipped |
| delivered | ✅ | Teal | Order delivered |
| cancelled | ❌ | Red | Order cancelled |
| system | 🔔 | Gray | General system messages |
| message | 💬 | Cyan | Messages from support |

## Integration Steps

### Step 1: Update Database
Run the SQL from `complete_database.sql` to create the notifications table:

```sql
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Step 2: Files Modified/Created
- ✅ `customer_only/includes/notifications.php` - Helper functions
- ✅ `customer_only/includes/header.php` - Updated with notification badge
- ✅ `customer_only/pages/notifications.php` - Notifications page
- ✅ `complete_database.sql` - Updated with notifications table

### Step 3: Link Notifications in Order Processing
To fully integrate, update your order placement code (likely in `customer_only/pages/checkout.php`):

```php
// After creating order
include '../includes/notifications.php';
createNotification($user_id, 'order', 'Order Placed', 'Your order has been created', $conn, $order_id);
```

## Features

✅ **Unread Badge** - Shows count of unread notifications in header
✅ **Pagination** - Handles multiple notifications with pagination
✅ **Mark as Read** - Mark individual or all notifications as read
✅ **Delete** - Remove notifications
✅ **Time Formatting** - Smart relative timestamps (e.g., "5 minutes ago")
✅ **Visual Indicators** - Different colors and icons for different types
✅ **Responsive** - Works on mobile and desktop

## Usage in View Orders Page

You can enhance the view_orders.php page to show order notifications:

```php
// Add at top after getting order
$notifications = getUserNotifications($_SESSION['user_id'], $conn, 5);

// Display notification indicators next to orders
```

## Next Steps (Optional)

1. **Email Notifications** - Send emails when important status updates occur
2. **Push Notifications** - Add browser push notifications
3. **Notification Preferences** - Let users choose which notifications to receive
4. **Notification Templates** - Create reusable notification templates
5. **Admin Panel** - Add ability for admins to send custom notifications to users

---

**Need Help?**
- Check the helper functions in `customer_only/includes/notifications.php`
- View the notifications page implementation at `customer_only/pages/notifications.php`
- All functions are documented with comments
