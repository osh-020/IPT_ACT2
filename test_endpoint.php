<?php
include 'includes/db_connect.php';

echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
echo "<h2>Testing ?get_reviews endpoint for Product 28</h2>";

// Simulate the get_reviews request
$product_id = 28;
$rating_filter = null;

$query = "
    SELECT r.rating_id, r.rating, r.review, r.created_at, COALESCE(u.full_name, 'Anonymous') as full_name
    FROM order_ratings r
    LEFT JOIN orders o ON o.order_id = r.order_id
    LEFT JOIN users u ON u.user_id = o.user_id
    WHERE r.product_id = ?
";

if ($rating_filter) {
    $query .= " AND r.rating = ?";
}

$query .= " ORDER BY r.created_at DESC LIMIT 50";

echo "<p><strong>SQL Query:</strong></p>";
echo "<pre>" . htmlspecialchars($query) . "</pre>";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "<p style='color: red;'><strong>ERROR:</strong> Prepare failed: " . $conn->error . "</p>";
} else {
    $stmt->bind_param("i", $product_id);
    
    if (!$stmt->execute()) {
        echo "<p style='color: red;'><strong>ERROR:</strong> Execute failed: " . $stmt->error . "</p>";
    } else {
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC) ?: [];
        $stmt->close();
        
        echo "<p><strong>Reviews found:</strong> " . count($reviews) . "</p>";
        
        if (count($reviews) > 0) {
            echo "<h3>Review Data:</h3>";
            echo "<pre>" . print_r($reviews, true) . "</pre>";
            
            // Test JSON output
            echo "<h3>JSON Output (what the AJAX will receive):</h3>";
            echo "<pre>" . json_encode(['reviews' => $reviews], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color: orange;'><strong>WARNING:</strong> No reviews returned from query!</p>";
            
            // Debug: check if order_ratings table has data
            echo "<h3>Debug Info:</h3>";
            $debug = $conn->query("SELECT * FROM order_ratings WHERE product_id = 28");
            echo "<p><strong>Row count from order_ratings for product 28:</strong> " . $debug->num_rows . "</p>";
            
            if ($debug->num_rows > 0) {
                echo "<p><strong>Raw order_ratings data:</strong></p>";
                while ($row = $debug->fetch_assoc()) {
                    echo "<pre>" . print_r($row, true) . "</pre>";
                }
            }
        }
    }
}

echo "</div>";
?>
