<?php
include 'includes/db_connect.php';

// Check reviews count
$reviews = $conn->query("SELECT COUNT(*) as count FROM order_ratings");
$review_count = $reviews->fetch_assoc()['count'];

// Check users count
$users = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $users->fetch_assoc()['count'];

// Check orders count
$orders = $conn->query("SELECT COUNT(*) as count FROM orders");
$order_count = $orders->fetch_assoc()['count'];

// Get a sample review
$sample = $conn->query("SELECT * FROM order_ratings LIMIT 1");
$sample_data = $sample->fetch_assoc();

?>
<h2>Database Status</h2>
<table border="1" cellpadding="10">
<tr><td><strong>Reviews</strong></td><td><?php echo $review_count; ?></td></tr>
<tr><td><strong>Users</strong></td><td><?php echo $user_count; ?></td></tr>
<tr><td><strong>Orders</strong></td><td><?php echo $order_count; ?></td></tr>
</table>

<h3>Sample Review (if exists):</h3>
<pre><?php print_r($sample_data); ?></pre>

<h3>Test Review Query:</h3>
<pre><?php
$test = $conn->query("
    SELECT r.rating_id, r.rating, r.review, r.created_at, u.full_name
    FROM order_ratings r
    LEFT JOIN orders o ON o.order_id = r.order_id
    LEFT JOIN users u ON u.user_id = o.user_id
    LIMIT 5
");
while($row = $test->fetch_assoc()) {
    print_r($row);
}
?></pre>
?>
