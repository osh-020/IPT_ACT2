<?php
session_start();
include ("../includes/db_connect.php");

// Get search and filter parameters
$searchQuery = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
$filterCategory = isset($_GET['category']) ? htmlspecialchars(trim($_GET['category'])) : '';
$sortBy = isset($_GET['sort']) ? htmlspecialchars(trim($_GET['sort'])) : 'newest';
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 12;

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Validate product exists
    $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if ($product && $product['stock'] > 0 && $quantity > 0 && $quantity <= $product['stock']) {
        // Add or update cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
        
        // Redirect to prevent form resubmission
        header("Location: products.php?search=" . urlencode($searchQuery) . "&category=" . urlencode($filterCategory) . "&added=1");
        exit;
    }
}

// Build search query
$query = "SELECT id, name, description, brand, category, price, stock, image FROM products WHERE stock > 0";

// Add search filter
if (!empty($searchQuery)) {
    $escapedSearch = $conn->real_escape_string("%$searchQuery%");
    $query .= " AND (name LIKE '$escapedSearch' OR description LIKE '$escapedSearch' OR brand LIKE '$escapedSearch' OR category LIKE '$escapedSearch')";
}

// Add category filter
if (!empty($filterCategory)) {
    $escapedCategory = $conn->real_escape_string($filterCategory);
    $query .= " AND category = '$escapedCategory'";
}

// Add sorting
switch ($sortBy) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'popular':
        $query .= " ORDER BY stock DESC";
        break;
    default: // newest
        $query .= " ORDER BY id DESC";
        break;
}

// Get total count
$countResult = $conn->query("SELECT COUNT(*) as total FROM ($query) as filtered_products");
$countRow = $countResult->fetch_assoc();
$totalProducts = $countRow['total'];
$totalPages = ceil($totalProducts / $itemsPerPage);

// Add pagination
$startIndex = ($currentPage - 1) * $itemsPerPage;
$query .= " LIMIT $startIndex, $itemsPerPage";

// Execute query
$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch categories for filter
$categoryQuery = "SELECT DISTINCT category FROM products WHERE stock > 0 ORDER BY category";
$categoryResult = $conn->query($categoryQuery);
$categories = [];
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Calculate cart totals
$cartCount = 0;
$cartTotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += $item['quantity'];
    $cartTotal += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Products</title>
    <link rel="stylesheet" href="./css/shopping.css">
</head>
<body>
    <div class="shopping-container">
        <!-- Header -->
        <div class="shopping-header">
            <h1>🛍️ Shop Products</h1>
            <p>Find and purchase the products you love</p>
        </div>

        <!-- Cart Summary -->
        <?php if ($cartCount > 0): ?>
            <div class="cart-summary">
                <div class="cart-info">
                    <span class="cart-count">🛒 Items in Cart: <strong><?php echo $cartCount; ?></strong></span>
                    <span class="cart-total">Total: <strong>₱<?php echo number_format($cartTotal, 2); ?></strong></span>
                </div>
                <a href="./cart.php" class="btn-view-cart">View Cart & Checkout</a>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Section -->
        <div class="search-filter-wrapper">
            <form method="GET" class="search-filter-content">
                <div class="search-group">
                    <input type="text" name="search" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>

                <div class="filter-group">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo ($filterCategory === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="sort">
                        <option value="newest" <?php echo ($sortBy === 'newest') ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_low" <?php echo ($sortBy === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo ($sortBy === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="popular" <?php echo ($sortBy === 'popular') ? 'selected' : ''; ?>>Most Available</option>
                    </select>
                </div>

                <button type="submit" class="btn-search">Search</button>

                <?php if (!empty($searchQuery) || !empty($filterCategory)): ?>
                    <a href="products.php" class="btn-clear">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products Section -->
        <div class="products-section">
            <?php if (!empty($searchQuery) || !empty($filterCategory)): ?>
                <div class="products-header">
                    <div class="results-info">
                        Found <strong><?php echo $totalProducts; ?></strong> product(s)
                        <?php if (!empty($searchQuery)): ?>
                            for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (count($products) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="../admin/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2214%22 fill=%22%23999%22%3ENo Image%3C/text%3E%3C/svg%3E'">

                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                                
                                <div class="product-category">
                                    <?php echo htmlspecialchars($product['category']); ?>
                                </div>

                                <div class="product-description">
                                    <?php 
                                    $desc = htmlspecialchars(substr($product['description'], 0, 100));
                                    echo $desc . (strlen($product['description']) > 100 ? '...' : '');
                                    ?>
                                </div>

                                <div class="product-price">
                                    ₱<?php echo number_format($product['price'], 2); ?>
                                </div>

                                <div class="product-stock">
                                    <?php 
                                    $stock = intval($product['stock']);
                                    if ($stock > 20) {
                                        $badge = '<span class="stock-badge in-stock">✓ In Stock (' . $stock . ')</span>';
                                    } elseif ($stock > 0) {
                                        $badge = '<span class="stock-badge low-stock">⚠ Only ' . $stock . ' left</span>';
                                    } else {
                                        $badge = '<span class="stock-badge out-of-stock">✗ Out of Stock</span>';
                                    }
                                    echo $badge;
                                    ?>
                                </div>

                                <div class="product-actions">
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" name="add_to_cart" class="btn-add-cart" 
                                                <?php echo ($product['stock'] == 0) ? 'disabled' : ''; ?>>
                                            🛒 Add to Cart
                                        </button>
                                    </form>
                                    <button class="btn-wishlist" title="Add to Wishlist">❤</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="text-align: center; margin-top: 40px; padding: 20px;">
                        <?php if ($currentPage > 1): ?>
                            <a href="?search=<?php echo urlencode($searchQuery); ?>&category=<?php echo urlencode($filterCategory); ?>&page=1" 
                               style="padding: 10px 15px; margin: 5px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">
                                « First
                            </a>
                            <a href="?search=<?php echo urlencode($searchQuery); ?>&category=<?php echo urlencode($filterCategory); ?>&page=<?php echo $currentPage - 1; ?>" 
                               style="padding: 10px 15px; margin: 5px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">
                                ‹ Previous
                            </a>
                        <?php endif; ?>

                        <span style="padding: 10px 15px;">Page <strong><?php echo $currentPage; ?></strong> of <strong><?php echo $totalPages; ?></strong></span>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?search=<?php echo urlencode($searchQuery); ?>&category=<?php echo urlencode($filterCategory); ?>&page=<?php echo $currentPage + 1; ?>" 
                               style="padding: 10px 15px; margin: 5px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">
                                Next ›
                            </a>
                            <a href="?search=<?php echo urlencode($searchQuery); ?>&category=<?php echo urlencode($filterCategory); ?>&page=<?php echo $totalPages; ?>" 
                               style="padding: 10px 15px; margin: 5px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">
                                Last »
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-products">
                    <h3>No Products Found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                    <a href="products.php" style="padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; display: inline-block;">
                        Browse All Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
