<?php
session_start();
include '../includes/header.php';
include '../includes/db_connect.php';

// Get search and filter parameters
$searchQuery = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
$filterCategory = isset($_GET['category']) ? htmlspecialchars(trim($_GET['category'])) : '';
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 12;

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_to_cart'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=products.php");
        exit;
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($quantity > 0) {
        // Validate product exists
        $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        if ($product && $product['stock'] > 0 && $quantity <= $product['stock']) {
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
        }
    }
}

// Build search query
$query = "SELECT id, name, description, brand, category, price, stock, image FROM products WHERE stock > 0";

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

// Add sorting
$query .= " ORDER BY id DESC";

// Get total count
$countResult = $conn->query($query);
$totalProducts = $countResult->num_rows;
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
?>

<main class="main-content">
    <div class="products-container">
        <!-- Sidebar Filters -->
        <aside class="sidebar">
            <h3>Filters</h3>
            
            <!-- Category Filter -->
            <div class="filter-group">
                <h4>Category</h4>
                <form method="GET" action="products.php">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php
                        foreach ($categories as $cat) {
                            $selected = ($filterCategory == $cat) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($cat) . "' $selected>" . htmlspecialchars($cat) . "</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>

            <!-- Clear Filters -->
            <?php
            if (!empty($searchQuery) || !empty($filterCategory)) {
                echo "<a href='products.php' class='btn btn-small'>Clear Filters</a>";
            }
            ?>
        </aside>

        <!-- Products Section -->
        <section class="products-section">
            <h2>Products</h2>
            
            <!-- Results Info -->
            <div class="results-info">
                <p>Showing <?php echo $totalProducts; ?> products</p>
                <?php
                if (!empty($searchQuery)) {
                    echo "<p>Search: <strong>" . htmlspecialchars($searchQuery) . "</strong></p>";
                }
                ?>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $name = htmlspecialchars($product['name']);
                        $price = number_format($product['price'], 2);
                        $shortDescription = htmlspecialchars(substr($product['description'], 0, 100)) . '...';
                        $fullDescription = htmlspecialchars($product['description']);
                        $stock = $product['stock'];
                        $image = !empty($product['image']) ? htmlspecialchars($product['image']) : 'placeholder.jpg';
                        $productId = $product['id'];
                        $brand = htmlspecialchars($product['brand'] ?? 'N/A');
                        $category = htmlspecialchars($product['category'] ?? 'N/A');
                        
                        echo "
                        <div class='product-card'>
                            <div class='product-image' onclick=\"openProductModal($productId, '$name', '$price', '$fullDescription', '$stock', '$image', '$brand', '$category')\" style='cursor: pointer;'>
                                <img src='../admin/uploads/$image' alt='$name' onerror=\"this.src='https://via.placeholder.com/200x200?text=No+Image'\">
                            </div>
                            <div class='product-info'>
                                <h3 onclick=\"openProductModal($productId, '$name', '$price', '$fullDescription', '$stock', '$image', '$brand', '$category')\" style='cursor: pointer;'>$name</h3>
                                <p class='brand'>Brand: $brand</p>
                                <p class='description'>$shortDescription</p>
                                <p class='price'>₱$price</p>
                                <p class='stock'>Stock: $stock</p>
                                <form method='POST' action='products.php'>
                                    <input type='hidden' name='product_id' value='$productId'>
                                    <div class='product-actions'>
                                        <input type='number' name='quantity' value='1' min='1' max='$stock' class='qty-input'>
                                        <button type='submit' name='add_to_cart' value='1' class='btn btn-add'>Add to Cart</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        ";
                    }
                } else {
                    echo "<p class='no-products'>No products found</p>";
                }
                ?>
            </div>

            <!-- Pagination -->
            <?php
            if ($totalPages > 1) {
                echo "<div class='pagination'>";
                for ($i = 1; $i <= $totalPages; $i++) {
                    $active = ($i == $currentPage) ? 'active' : '';
                    echo "<a href='products.php?search=" . urlencode($searchQuery) . "&category=" . urlencode($filterCategory) . "&page=$i' class='page-link $active'>$i</a>";
                }
                echo "</div>";
            }
            ?>
        </section>
    </div>

    <!-- Product Details Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProductModal()">&times;</span>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="modalImage" src="" alt="Product Image" onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
                </div>
                <div class="modal-details">
                    <h2 id="modalName"></h2>
                    <p class="modal-brand"><strong>Brand:</strong> <span id="modalBrand"></span></p>
                    <p class="modal-category"><strong>Category:</strong> <span id="modalCategory"></span></p>
                    <p class="modal-price" id="modalPrice"></p>
                    <p class="modal-stock" id="modalStock"></p>
                    
                    <div class="modal-description">
                        <h3>Full Description</h3>
                        <p id="modalDescription"></p>
                    </div>
                    
                    <form id="modalAddForm" method="POST" action="products.php">
                        <input type="hidden" name="product_id" id="modalProductId">
                        <div class="modal-actions">
                            <input type="number" name="quantity" id="modalQuantity" value="1" min="1" class="qty-input">
                            <button type="submit" name="add_to_cart" value="1" class="btn btn-add">Add to Cart</button>
                            <button type="button" onclick="closeProductModal()" class="btn btn-cancel">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openProductModal(productId, name, price, description, stock, image, brand, category) {
            document.getElementById('modalProductId').value = productId;
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalPrice').innerHTML = '<strong>Price:</strong> ₱' + price;
            document.getElementById('modalStock').innerHTML = '<strong>Stock:</strong> <span style="color: #4caf50;">' + stock + ' available</span>';
            document.getElementById('modalDescription').textContent = description;
            document.getElementById('modalImage').src = '../admin/uploads/' + image;
            document.getElementById('modalBrand').textContent = brand;
            document.getElementById('modalCategory').textContent = category;
            document.getElementById('modalQuantity').max = stock;
            document.getElementById('productModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeProductModal();
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProductModal();
            }
        });
    </script>
</main>

<?php include '../includes/footer.php'; ?>
