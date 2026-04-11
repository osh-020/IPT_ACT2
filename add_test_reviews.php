<?php
session_start();
include 'includes/db_connect.php';

// Check if request is POST to add test data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_test_data'])) {
    // Add a test user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $email = "testcustomer@test.com";
    $password = password_hash("test123", PASSWORD_DEFAULT);
    $stmt->bind_param("sssss", $name, $email, $password, $phone, $address);
    $name = "Test Customer";
    $phone = "1234567890";
    $address = "123 Test St";
    $stmt->execute();
    $user_id = $conn->insert_id;
    $stmt->close();

    // Add a test order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_date, status, total_amount) VALUES (?, NOW(), 'Delivered', 99.99)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // Add order items for products 1, 2, 3
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, 1, 99.99)");
    $stmt->bind_param("ii", $order_id, $product_id);
    for ($product_id = 1; $product_id <= 3; $product_id++) {
        $stmt->execute();
    }
    $stmt->close();

    // Add sample reviews
    $reviews = [
        [1, 5, "Excellent CPU! Very fast and reliable."],
        [2, 4, "Great RAM, good speed for gaming."],
        [3, 5, "Perfect SSD, very fast boot times."],
    ];

    $stmt = $conn->prepare("INSERT INTO order_ratings (order_id, product_id, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iis", $order_id, $product_id, $rating, $review);

    foreach ($reviews as [$product_id, $rating, $review]) {
        $stmt->execute();
    }
    $stmt->close();

    echo "<div style='padding: 20px; background: #d4edda; color: #155724; border-radius: 5px;'>";
    echo "<h3>✓ Test data added successfully!</h3>";
    echo "<p>User: $name (ID: $user_id)</p>";
    echo "<p>Order ID: $order_id</p>";
    echo "<p>Added 3 sample reviews for products 1, 2, and 3</p>";
    echo "<p><a href='products.php' style='color: #155724; font-weight: bold;'>Go to Products Page →</a></p>";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background: #fff3cd; color: #856404; border-radius: 5px;'>";
    echo "<h3>⚠️ Add Test Reviews</h3>";
    echo "<p>This will create:</p>";
    echo "<ul>";
    echo "<li>1 test customer (testcustomer@test.com)</li>";
    echo "<li>1 test order with 3 products</li>";
    echo "<li>3 sample reviews with ratings and comments</li>";
    echo "</ul>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='add_test_data' value='1' style='padding: 10px 20px; background: #ffc107; border: none; border-radius: 3px; cursor: pointer; font-weight: bold;'>Add Test Data Now</button>";
    echo "</form>";
    echo "</div>";
}
?>
