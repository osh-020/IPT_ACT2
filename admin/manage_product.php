<?php
session_start();
include ("../includes/db_connect.php");
require_once ("../includes/admin_notifications.php");

$successMessage = '';
$errorMessage = '';

// Get admin notification count
$adminUnreadCount = getAdminUnreadNotificationsCount($conn);

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
<?php include("header.php"); ?>

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
                                <form method="POST" style="flex: 1; display:flex; flex-direction:column; gap:6px;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <button type="button" class="btn-reviews" style="width:100%;" onclick="viewReviews(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">⭐ Reviews</button>
                                    <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn-delete" style="width:100%;">Delete</button>
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

            <!-- Reviews Modal -->
<div id="reviewsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; overflow-y:auto;">
    <div style="background:#1a1a2e; margin:50px auto; padding:30px; max-width:650px; border-radius:12px; border:1px solid #333;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 id="modalTitle" style="color:#e8ff47; margin:0;">Product Reviews</h3>
            <button onclick="closeReviews()" style="background:none; border:none; color:#fff; font-size:24px; cursor:pointer;">✕</button>
        </div>
        <div id="reviewsContent" style="color:#ccc;">Loading...</div>
    </div>
</div>

<!-- Reviews fetch script -->
<script>
function viewReviews(productId, productName) {
    document.getElementById('modalTitle').textContent = '⭐ Reviews: ' + productName;
    document.getElementById('reviewsContent').innerHTML = 'Loading...';
    document.getElementById('reviewsModal').style.display = 'block';

    fetch('get_product_reviews.php?product_id=' + productId)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                document.getElementById('reviewsContent').innerHTML = '<p style="text-align:center; color:#888;">No reviews yet for this product.</p>';
                return;
            }
            let avg = (data.reduce((s, r) => s + r.rating, 0) / data.length).toFixed(1);
            let html = `<div style="margin-bottom:15px; padding:10px; background:#0d0d1a; border-radius:8px; text-align:center;">
                            <span style="font-size:28px; color:#e8ff47;">${avg} ⭐</span>
                            <span style="color:#888; margin-left:8px;">(${data.length} review${data.length > 1 ? 's' : ''})</span>
                        </div>`;
            data.forEach(r => {
                let stars = '⭐'.repeat(r.rating);
                let date = new Date(r.created_at).toLocaleDateString('en-PH', {year:'numeric', month:'short', day:'numeric'});
                html += `<div style="border-bottom:1px solid #333; padding:12px 0;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                <strong style="color:#fff;">${r.full_name}</strong>
                                <span style="color:#888; font-size:13px;">${date}</span>
                            </div>
                            <div style="margin-bottom:6px;">${stars}</div>
                            <div style="color:#ccc;">${r.review ? r.review : '<em style="color:#555;">No comment</em>'}</div>
                        </div>`;
            });
            document.getElementById('reviewsContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('reviewsContent').innerHTML = '<p style="color:red;">Failed to load reviews.</p>';
        });
}

function closeReviews() {
    document.getElementById('reviewsModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('reviewsModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviews();
});
</script>

</body>
</html>
