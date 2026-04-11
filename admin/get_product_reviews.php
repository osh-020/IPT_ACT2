<?php
session_start();
include("../includes/db_connect.php");

header('Content-Type: application/json');

$product_id = intval($_GET['product_id'] ?? 0);
if (!$product_id) { echo json_encode([]); exit; }

$stmt = $conn->prepare("
    SELECT r.rating, r.review, r.created_at, u.full_name
    FROM order_ratings r
    JOIN orders o ON o.order_id = r.order_id
    JOIN users u ON u.user_id = o.user_id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($reviews);