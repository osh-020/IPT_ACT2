<?php
include ("../includes/db_connect.php");

$successMessage = '';
$errorMessage = '';

// Handle delete product
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // Get product info to delete image
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if ($product) {
        // Delete image file
        $imagePath = __DIR__ . '/uploads/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Delete product from database
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $successMessage = "Product deleted successfully!";
        } else {
            $errorMessage = "Failed to delete product. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch all products
$query = "SELECT * FROM products ORDER BY created_at DESC";
$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body class="manage_products">

    <div class="container">
        <h2> Manage Products</h2>

        <?php if ($successMessage): ?>
            <div class="success-message">
                 <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="error-message">
                 <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="upload_product.php" class="btn-upload">+ Add New Product</a>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="./uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image" 
                             onerror="this.classList.add('no-image'); this.alt='No Image Available'">
                        
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                            <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                            
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                            
                            <div class="product-stock">
                                Stock: <strong><?php echo intval($product['stock']); ?></strong> units
                            </div>

                            <div class="product-actions">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-edit"> Edit</a>
                                <form method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn-delete" style="width: 100%;">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <p>No products found. <a href="upload_product.php" style="color: #e8ff47; text-decoration: underline;">Add one now</a></p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
