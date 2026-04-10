<?php
/**
 * Admin Notifications Setup Page
 * Run once to create the admin_notifications table
 */
session_start();
require_once 'includes/db_connect.php';

$setup_complete = false;
$error_message = '';
$success_message = '';

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
$table_exists = $table_check && $table_check->num_rows > 0;

if ($table_exists) {
    $setup_complete = true;
    $success_message = "Admin notifications table already exists and is ready to use!";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS `admin_notifications` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql) === TRUE) {
        $setup_complete = true;
        $success_message = "Admin notifications table created successfully!";
    } else {
        $error_message = "Error creating table: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications Setup</title>
    <link rel="icon" type="image/png" href="includes/website_pic/logo.png">
    <link rel="stylesheet" href="includes/admin_style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #0d0d0f;
        }
        .setup-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .setup-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .setup-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .setup-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #2e7d32;
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #c62828;
        }
        .setup-btn {
            display: inline-block;
            background-color: #0066cc;
            color: white;
            padding: 14px 40px;
            border-radius: 5px;
            border: none;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .setup-btn:hover {
            background-color: #0052a3;
        }
        .setup-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .completed-btn {
            background-color: #4caf50;
        }
        .completed-btn:hover {
            background-color: #45a049;
        }
        .go-admin {
            margin-top: 20px;
        }
        .go-admin a {
            color: #0066cc;
            text-decoration: none;
            font-weight: bold;
        }
        .go-admin a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-icon">⚙️</div>
        <h1 class="setup-title">Admin Notifications Setup</h1>

        <?php if ($success_message): ?>
            <div class="success-box"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-box"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($setup_complete): ?>
            <p class="setup-message">
                The admin notifications table is ready! Admin users will now receive notifications when:
            </p>
            <ul style="text-align: left; display: inline-block; color: #666;">
                <li>New orders are placed</li>
                <li>Orders are refunded</li>
                <li>Orders are cancelled</li>
            </ul>
            <div class="go-admin">
                <a href="admin/index.php" class="setup-btn completed-btn">Go to Admin Dashboard</a>
            </div>
        <?php else: ?>
            <p class="setup-message">
                Click the button below to create the admin notifications table and enable admin notifications for orders.
            </p>
            <form method="POST">
                <button type="submit" name="setup" class="setup-btn">Initialize Admin Notifications</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
