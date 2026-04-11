<?php
// Rating Helper Functions
// Uses the existing rating table for product reviews in orders

/**
 * Get average rating for a product
 */
function getProductRating($product_id, $conn) {
    // Will use product_id after migration - for now returns 0
    $stmt = $conn->prepare("
        SELECT AVG(rating) as average, COUNT(*) as total 
        FROM order_ratings 
        WHERE product_id = ? OR product_id IS NULL
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return [
        'average' => $data['average'] ? round($data['average'], 1) : 0,
        'total' => $data['total'] ?? 0
    ];
}

/**
 * Get reviews for a product with optional rating filter
 */
function getProductReviewsList($product_id, $conn, $rating_filter = null) {
    // Will use product_id after migration - for now returns empty
    $query = "
        SELECT r.rating_id, r.rating, r.review, r.created_at, u.full_name
        FROM order_ratings r
        JOIN orders o ON o.order_id = r.order_id
        JOIN users u ON u.user_id = o.user_id
        WHERE (r.product_id = ? OR r.product_id IS NULL)
    ";
    
    if ($rating_filter) {
        $query .= " AND r.rating = ?";
    }
    
    $query .= " ORDER BY r.created_at DESC LIMIT 10";
    
    $stmt = $conn->prepare($query);
    if ($rating_filter) {
        $stmt->bind_param("ii", $product_id, $rating_filter);
    } else {
        $stmt->bind_param("i", $product_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $reviews;
}

/**
 * Check if user has purchased a product
 */
function userHasPurchasedProduct($user_id, $product_id, $conn) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Completed'
    ");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data['count'] > 0;
}

/**
 * Check if user has already reviewed a product in a specific order
 */
function userHasReviewedProduct($user_id, $product_id, $conn, $order_id = null) {
    if ($order_id) {
        // Check for this specific order (per-order basis like real e-commerce)
        $stmt = $conn->prepare("
            SELECT r.rating_id FROM order_ratings r
            WHERE r.order_id = ? AND r.product_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $order_id, $product_id);
    } else {
        // Fallback: check if user has reviewed this product from ANY order
        $stmt = $conn->prepare("
            SELECT r.rating_id FROM order_ratings r
            JOIN orders o ON o.order_id = r.order_id
            WHERE o.user_id = ? AND r.product_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $user_id, $product_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Get user's existing review for a product
 */
function getUserProductReview($user_id, $product_id, $conn) {
    $stmt = $conn->prepare("
        SELECT r.* FROM order_ratings r
        JOIN orders o ON o.order_id = r.order_id
        WHERE o.user_id = ? AND r.product_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $review = $result->fetch_assoc();
    $stmt->close();
    
    return $review;
}

/**
 * Add or update product review
 */
function addProductReview($user_id, $product_id, $order_id, $rating, $review_text, $conn) {
    $stmt = $conn->prepare("
        INSERT INTO order_ratings (order_id, product_id, rating, review, created_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            rating = VALUES(rating), 
            review = VALUES(review), 
            created_at = NOW()
    ");
    $stmt->bind_param("iiis", $order_id, $product_id, $rating, $review_text);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get rating distribution for a product
 */
function getProductRatingDistribution($product_id, $conn) {
    $stmt = $conn->prepare("
        SELECT rating, COUNT(*) as count FROM order_ratings 
        WHERE product_id = ? 
        GROUP BY rating
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    while ($row = $result->fetch_assoc()) {
        $distribution[$row['rating']] = $row['count'];
    }
    $stmt->close();
    
    return $distribution;
}

/**
 * Format review timestamp
 */
function formatReviewTime($created_at) {
    $created = strtotime($created_at);
    $now = time();
    $diff = $now - $created;
    
    $seconds = $diff;
    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $days = floor($hours / 24);
    
    if ($seconds < 60) return "just now";
    if ($minutes < 60) return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    if ($hours < 24) return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    if ($days < 7) return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    
    return date('M d, Y', $created);
}
