<?php
session_start();
include '../includes/header.php';
include '../includes/db_connect.php';

// Get featured products (newest 6 products)
$query = "SELECT id, name, price, image, category FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 6";
$result = $conn->query($query);
$featuredProducts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featuredProducts[] = $row;
    }
}

// Get product categories count
$categoriesQuery = "SELECT DISTINCT category, COUNT(*) as count FROM products WHERE stock > 0 GROUP BY category";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<main class="main-content">
    <!-- Hero Banner -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to COMPUTRONIUM</h1>
            <p>Find the best computer components and peripherals</p>
            <a href="products.php" class="btn btn-primary">Shop Now</a>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <h2>Shop by Category</h2>
        <div class="categories-grid">
            <?php
            foreach ($categories as $cat) {
                $catName = htmlspecialchars($cat['category']);
                $catCount = $cat['count'];
                echo "
                <a href='products.php?category=" . urlencode($catName) . "' class='category-card'>
                    <div class='category-icon'></div>
                    <h3>$catName</h3>
                    <p>$catCount products</p>
                </a>
                ";
            }
            ?>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-section">
        <h2>Featured Products</h2>
        <div class="products-grid">
            <?php
            foreach ($featuredProducts as $product) {
                $name = htmlspecialchars($product['name']);
                $price = number_format($product['price'], 2);
                $image = !empty($product['image']) ? htmlspecialchars($product['image']) : 'https://via.placeholder.com/200x200?text=No+Image';
                $productId = $product['id'];
                
                echo "
                <div class='product-card'>
                    <div class='product-image'>
                        <img src='../admin/uploads/$image' alt='$name' onerror=\"this.src='https://via.placeholder.com/200x200?text=No+Image'\">
                    </div>
                    <div class='product-info'>
                        <h3>$name</h3>
                        <p class='category'>" . htmlspecialchars($product['category']) . "</p>
                        <p class='price'>₱$price</p>
                        <form method='POST' action='products.php'>
                            <input type='hidden' name='product_id' value='$productId'>
                            <input type='hidden' name='add_to_cart' value='1'>
                            <button type='submit' class='btn btn-small'>Add to Cart</button>
                        </form>
                    </div>
                </div>
                ";
            }
            ?>
        </div>
    </section>

    <!-- Info Section -->
    <section class="info-section">
        <div class="info-card">
            <h3>Quality Products</h3>
            <p>Genuine computer components from trusted brands</p>
        </div>
        <div class="info-card">
            <h3>Fast Shipping</h3>
            <p>Quick delivery to your doorstep</p>
        </div>
        <div class="info-card">
            <h3>Best Prices</h3>
            <p>Competitive pricing with regular discounts</p>
        </div>
        <div class="info-card">
            <h3>Warranty</h3>
            <p>All products come with manufacturer warranty</p>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
