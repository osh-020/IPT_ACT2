<?php
session_start();
include ("../includes/db_connect.php");

$successMessage = '';
$errorMessage = '';

// Check for session success message from edit_product.php
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']); // Clear it after retrieving
}

// Get search and filter parameters
$searchQuery = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
$filterCategory = isset($_GET['category']) ? htmlspecialchars(trim($_GET['category'])) : '';

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
        $imagePath = dirname(__DIR__) . '/includes/product_pic/' . $product['image'];
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

// Fetch all products with search and filter
$query = "SELECT * FROM products WHERE 1=1";

// Add search filter
if (!empty($searchQuery)) {
    $escapedSearch = $conn->real_escape_string("%$searchQuery%");
    $query .= " AND (name LIKE '$escapedSearch' OR description LIKE '$escapedSearch' OR brand LIKE '$escapedSearch')";
}

// Add category filter
if (!empty($filterCategory)) {
    $escapedCategory = $conn->real_escape_string($filterCategory);
    $query .= " AND category = '$escapedCategory'";
}

$query .= " ORDER BY created_at DESC";

$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch all unique categories for the filter dropdown
$categoryQuery = "SELECT DISTINCT category FROM products ORDER BY category";
$categoryResult = $conn->query($categoryQuery);
$categories = [];
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="icon" type="image/png" href="../includes/website_pic/logo.png">
    <link rel="stylesheet" href="../includes/admin_style.css">
</head>
<body class="manage_products">

    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <!-- Logo Section -->
            <div class="admin-logo">
                <a href="index.php" class="admin-logo-link">
                    <img src="../includes/website_pic/logo.png" alt="COMPUTRONIUM Logo" class="admin-logo-img">
                    <h1>COMPUTRONIUM Admin</h1>
                </a>
            </div>

            <!-- Admin Navigation -->
            <nav class="admin-nav">
                <ul class="admin-nav-menu">
                    <li><a href="index.php" class="admin-nav-link">Dashboard</a></li>
                    <li><a href="manage_product.php" class="admin-nav-link active">Products</a></li>
                    <li><a href="view_order.php" class="admin-nav-link">Orders</a></li>
                </ul>
            </nav>

            <!-- Admin Actions -->
            <div class="admin-actions">
                <a href="upload_product.php" class="admin-btn-primary">New Product</a>
            </div>
        </div>
    </header>

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

        <div class="search-filter-section">
            <form method="GET" class="search-form">
                <div class="search-group">
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="Search by name, description, or brand..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>" class="search-input">
                        <button type="submit" class="btn-search-icon">Search</button>
                    </div>
                </div>

                <div class="filter-group">
                    <select name="category" class="category-filter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo ($filterCategory === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!empty($searchQuery) || !empty($filterCategory)): ?>
                    <a href="manage_product.php" class="btn-clear">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="../includes/product_pic/<?php echo htmlspecialchars($product['image']); ?>" 
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
