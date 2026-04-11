<?php
include 'includes/db_connect.php';

echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
echo "<h2>✓ Database Verification</h2>";

// Check total reviews
$result = $conn->query("SELECT COUNT(*) as count FROM order_ratings");
$row = $result->fetch_assoc();
echo "<p><strong>Total reviews in database:</strong> " . $row['count'] . "</p>";

// Check reviews with user info
echo "<h3>Reviews with Customer Info:</h3>";
$query = "
    SELECT r.rating_id, r.rating, r.review, r.created_at, 
           COALESCE(u.full_name, 'Anonymous') as full_name, r.product_id
    FROM order_ratings r
    LEFT JOIN orders o ON o.order_id = r.order_id
    LEFT JOIN users u ON u.user_id = o.user_id
    ORDER BY r.created_at DESC
";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>Product ID</th>";
    echo "<th style='padding: 10px;'>Customer</th>";
    echo "<th style='padding: 10px;'>Rating</th>";
    echo "<th style='padding: 10px;'>Review</th>";
    echo "<th style='padding: 10px;'>Date</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($row['product_id']) . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='padding: 10px;'>⭐ " . $row['rating'] . "/5</td>";
        echo "<td style='padding: 10px;'>" . (!empty($row['review']) ? htmlspecialchars($row['review']) : "<em>(No comment)</em>") . "</td>";
        echo "<td style='padding: 10px;'>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No reviews found!</p>";
}

// Test the product 28 review fetch
echo "<h3>Test: Fetching Reviews for Product 28 (Crucial P3 SSD):</h3>";
$product_id = 28;
$query = "
    SELECT r.rating_id, r.rating, r.review, r.created_at, COALESCE(u.full_name, 'Anonymous') as full_name
    FROM order_ratings r
    LEFT JOIN orders o ON o.order_id = r.order_id
    LEFT JOIN users u ON u.user_id = o.user_id
    WHERE r.product_id = $product_id
    ORDER BY r.created_at DESC
";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> Found " . $result->num_rows . " review(s) for product 28</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<p><strong>" . htmlspecialchars($row['full_name']) . "</strong></p>";
        echo "<p>⭐ " . str_repeat("⭐", $row['rating']) . " (" . $row['rating'] . "/5)</p>";
        echo "<p>" . (!empty($row['review']) ? htmlspecialchars($row['review']) : "<em>No comment provided</em>") . "</p>";
        echo "<p style='font-size: 12px; color: #666;'>" . $row['created_at'] . "</p>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'><strong>✗ ERROR:</strong> No reviews found for product 28</p>";
}

echo "</div>";
?>
