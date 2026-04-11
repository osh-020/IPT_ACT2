<?php
session_start();
include 'header.php';
include '../includes/db_connect.php';
require_once '../includes/product_rating.php';

// Get search and filter parameters (from GET or POST)
$searchQuery = '';
$filterCategory = '';
$currentPage = 1;

// Check GET first
if (isset($_GET['search'])) {
    $searchQuery = htmlspecialchars(trim($_GET['search']));
}
if (isset($_GET['category'])) {
    $filterCategory = htmlspecialchars(trim($_GET['category']));
}
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $currentPage = (int)$_GET['page'];
}

// Check POST for same parameters (preserved from form)
if (isset($_POST['search'])) {
    $searchQuery = htmlspecialchars(trim($_POST['search']));
}
if (isset($_POST['category'])) {
    $filterCategory = htmlspecialchars(trim($_POST['category']));
}
if (isset($_POST['page']) && is_numeric($_POST['page'])) {
    $currentPage = (int)$_POST['page'];
}

$itemsPerPage = 12;

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle AJAX Review Requests
if (isset($_GET['get_reviews'])) {
    header('Content-Type: application/json');
    $product_id = intval($_GET['get_reviews']);
    $rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : null;
    
    // Query reviews for this product
    $query = "
        SELECT r.rating_id, r.rating, r.review, r.created_at, u.full_name
        FROM order_ratings r
        JOIN orders o ON o.order_id = r.order_id
        JOIN users u ON u.user_id = o.user_id
        WHERE r.product_id = ?
    ";
    
    if ($rating_filter) {
        $query .= " AND r.rating = ?";
    }
    
    $query .= " ORDER BY r.created_at DESC LIMIT 50";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error, 'reviews' => [], 'stats' => ['average' => 0, 'total' => 0], 'distribution' => [1=>0,2=>0,3=>0,4=>0,5=>0]]);
        exit;
    }
    
    if ($rating_filter) {
        $stmt->bind_param("ii", $product_id, $rating_filter);
    } else {
        $stmt->bind_param("i", $product_id);
    }
    
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Execute failed: ' . $stmt->error, 'reviews' => [], 'stats' => ['average' => 0, 'total' => 0], 'distribution' => [1=>0,2=>0,3=>0,4=>0,5=>0]]);
        exit;
    }
    
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();
    
    // Get stats for this product
    $stats_stmt = $conn->prepare("
        SELECT AVG(rating) as average, COUNT(*) as total 
        FROM order_ratings
        WHERE product_id = ?
    ");
    $stats_stmt->bind_param("i", $product_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc() ?: ['average' => 0, 'total' => 0];
    $stats_stmt->close();
    
    // Get distribution for this product
    $dist_stmt = $conn->prepare("
        SELECT rating, COUNT(*) as count FROM order_ratings 
        WHERE product_id = ?
        GROUP BY rating
    ");
    $dist_stmt->bind_param("i", $product_id);
    $dist_stmt->execute();
    $dist_result = $dist_stmt->get_result();
    
    $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    while ($row = $dist_result->fetch_assoc()) {
        if ($row['rating'] >= 1 && $row['rating'] <= 5) {
            $distribution[$row['rating']] = $row['count'];
        }
    }
    $dist_stmt->close();
    
    echo json_encode([
        'reviews' => $reviews,
        'stats' => [
            'average' => $stats['average'] ? round($stats['average'], 1) : 0,
            'total' => (int)($stats['total'] ?? 0)
        ],
        'distribution' => $distribution
    ]);
    exit;
}

// Handle AJAX Check if User Can Review
if (isset($_GET['check_can_review'])) {
    header('Content-Type: application/json');
    $product_id = intval($_GET['check_can_review']);
    $can_review = false;
    
    if (isset($_SESSION['user_id'])) {
        // Check if user has purchased this product (completed order)
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM order_items oi
            JOIN orders o ON o.order_id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Completed'
        ");
        $check_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_data = $check_result->fetch_assoc();
        $check_stmt->close();
        
        $can_review = $check_data['count'] > 0;
    }
    
    echo json_encode(['can_review' => $can_review]);
    exit;
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_review'])) {
    if (isset($_SESSION['user_id'])) {
        $product_id = intval($_POST['product_id']);
        $rating = intval($_POST['rating']);
        $review_text = htmlspecialchars(trim($_POST['review_text'] ?? ''));
        
        // Check if user has purchased this product
        if (userHasPurchasedProduct($_SESSION['user_id'], $product_id, $conn)) {
            // Get the first completed order for this product
            $order_stmt = $conn->prepare("
                SELECT o.order_id FROM orders o
                JOIN order_items oi ON oi.order_id = o.order_id
                WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Completed'
                LIMIT 1
            ");
            $order_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();
            $order_data = $order_result->fetch_assoc();
            $order_stmt->close();
            
            if ($order_data && $rating >= 1 && $rating <= 5) {
                addProductReview($_SESSION['user_id'], $product_id, $order_data['order_id'], $rating, $review_text, $conn);
            }
        }
    }
    
    // Redirect to same page to refresh reviews
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
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
    
    // Preserve filters when redirecting back
    $redirect_params = [];
    if (!empty($searchQuery)) {
        $redirect_params[] = "search=" . urlencode($searchQuery);
    }
    if (!empty($filterCategory)) {
        $redirect_params[] = "category=" . urlencode($filterCategory);
    }
    if (!empty($currentPage) && $currentPage > 1) {
        $redirect_params[] = "page=" . $currentPage;
    }
    
    $redirect_url = "products.php";
    if (!empty($redirect_params)) {
        $redirect_url .= "?" . implode("&", $redirect_params);
    }
    
    // Redirect to maintain category/search filter
    header("Location: " . $redirect_url);
    exit;
}

// Handle Checkout (Buy Now)
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['checkout'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=checkout.php");
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
            // Add to cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                ];
            }
            // Mark as selected for checkout
            if (!isset($_SESSION['cart_selected'])) {
                $_SESSION['cart_selected'] = [];
            }
            $_SESSION['cart_selected'][$product_id] = true;
        }
    }
    
    // Redirect to checkout
    header("Location: checkout.php");
    exit;
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
                        <div class='product-card' onclick=\"openProductModal($productId, '" . addslashes($name) . "', '$price', '" . addslashes($fullDescription) . "', '$stock', '" . addslashes($image) . "', '" . addslashes($brand) . "', '" . addslashes($category) . "')\" style='cursor: pointer;'>
                            <div class='product-image'>
                                <img src='../includes/product_pic/$image' alt='$name' onerror=\"this.src='../includes/product_pic/cpu_intel_i5.jpg'\">
                            </div>
                            <div class='product-info'>
                                <h3>$name</h3>
                                <p class='brand'>Brand: $brand</p>
                                <p class='description'>$shortDescription</p>
                                <p class='price'>₱$price</p>
                                <p class='stock'>Stock: $stock</p>
                                <div class='btn btn-add' style='text-align: center; background-color: #e8ff47; color: #000; padding: 8px; font-weight: 600; border-radius: 0;'>View Details</div>
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
                        <input type="hidden" name="category" id="modalCategory">
                        <input type="hidden" name="page" id="modalPage">
                        
                        <!-- Quantity Controls -->
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                            <label style="color: #e8ff47; font-weight: 600;">Quantity:</label>
                            <button type="button" onclick="decreaseQuantity()" style="background-color: #e8ff47; color: #000; border: none; padding: 10px 15px; font-weight: bold; cursor: pointer; border-radius: 0; font-size: 18px;">-</button>
                            <input type="number" id="modalQuantity" name="quantity" value="1" min="1" style="width: 60px; padding: 8px; text-align: center; background-color: #2a2a32; color: #f0f0f0; border: 1px solid #e8ff47; border-radius: 0;" onchange="validateQuantity()">
                            <button type="button" onclick="increaseQuantity()" style="background-color: #e8ff47; color: #000; border: none; padding: 10px 15px; font-weight: bold; cursor: pointer; border-radius: 0; font-size: 18px;">+</button>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="submit" name="add_to_cart" value="1" class="btn btn-add" style="padding: 15px 30px; background-color: #e8ff47; color: #000; font-weight: 600; border: none; cursor: pointer; border-radius: 0; flex: 1; font-size: 16px;">Add to Cart</button>
                            <button type="submit" name="checkout" value="1" class="btn btn-checkout" style="padding: 15px 30px; background-color: #28a745; color: white; font-weight: 600; border: none; cursor: pointer; border-radius: 0; flex: 1; font-size: 16px;">Buy Now</button>
                            <button type="button" onclick="closeProductModal()" class="btn btn-cancel" style="padding: 15px 30px; background-color: #dc3545; color: white; font-weight: 600; border: none; cursor: pointer; border-radius: 0; font-size: 16px;">Close</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Reviews Section (Outside modal-body for full width) -->
            <div id="modalReviews" style="padding: 30px; border-top: 2px solid #e8ff47; background-color: #141417;">
                <h3 style="color: #e8ff47; margin-bottom: 15px; font-size: 20px;">Customer Reviews</h3>
                
                <!-- Review Stats -->
                <div id="reviewStats" style="display: flex; gap: 30px; margin-bottom: 25px; padding: 15px; background-color: #1a1a1f; border: 1px solid #e8ff47; border-radius: 0;">
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #e8ff47;" id="avgRating">0</div>
                        <div style="color: #999; font-size: 12px;">out of 5</div>
                    </div>
                    <div style="flex: 1;">
                        <div id="ratingBars"></div>
                    </div>
                </div>
                
                <!-- Rating Filter -->
                <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" onclick="filterReviewsByRating(null, this)" class="rating-filter active" style="padding: 8px 15px; background-color: #e8ff47; color: #000; border: 1px solid #e8ff47; border-radius: 0; cursor: pointer; font-weight: 600;">All</button>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <button type="button" onclick="filterReviewsByRating(<?php echo $i; ?>, this)" class="rating-filter" style="padding: 8px 15px; background-color: #2a2a32; color: #e8ff47; border: 1px solid #e8ff47; border-radius: 0; cursor: pointer;"><?php echo $i; ?> ★</button>
                    <?php endfor; ?>
                </div>
                
                <!-- Reviews List -->
                <div id="reviewsList" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px; min-height: 50px; padding: 10px; background-color: #0d0d0f; border: 1px solid #2a2a32; border-radius: 0;">
                    <p style="color: #999; text-align: center; padding: 20px;">Loading reviews...</p>
                </div>
                
                <!-- Review Submission Form (only for buyers) -->
                <div id="reviewSubmitSection" style="border-top: 1px solid #e8ff47; padding-top: 20px; display: none;">
                    <h4 style="color: #e8ff47; margin-bottom: 15px;">Share Your Review</h4>
                    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="product_id" id="reviewProductId" value="">
                        <input type="hidden" name="submit_review" value="1">
                        
                        <div>
                            <label style="color: #e8ff47; font-weight: 600; display: block; margin-bottom: 8px;">Rating:</label>
                            <div class="star-rating" style="display: flex; gap: 10px; font-size: 24px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star" onclick="setRating(<?php echo $i; ?>)" style="cursor: pointer; color: #555;" data-value="<?php echo $i; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" value="5">
                        </div>
                        
                        <div>
                            <label style="color: #e8ff47; font-weight: 600; display: block; margin-bottom: 8px;">Your Review:</label>
                            <textarea name="review_text" placeholder="Share your experience with this product..." style="width: 100%; height: 100px; padding: 10px; background-color: #1a1a1f; color: #f0f0f0; border: 1px solid #e8ff47; border-radius: 0; font-family: Arial;"></textarea>
                        </div>
                        
                        <button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 0; cursor: pointer; font-weight: 600;">Submit Review</button>
                    </form>
                </div>
                
                <!-- Not Eligible Message -->
                <div id="notEligibleMessage" style="background-color: #2a2a32; border-left: 4px solid #ff9800; padding: 15px; color: #999; display: none;">
                    <p>You can only review products you've purchased. <a href="checkout.php" style="color: #e8ff47; text-decoration: underline;">Buy this product</a> to leave a review.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openProductModal(productId, name, price, description, stock, image, brand, category) {
            document.getElementById('modalProductId').value = productId;
            document.getElementById('reviewProductId').value = productId;
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalPrice').innerHTML = '<strong>Price:</strong> ₱' + price;
            document.getElementById('modalStock').innerHTML = '<strong>Stock:</strong> <span style="color: #4caf50;">' + stock + ' available</span>';
            document.getElementById('modalDescription').textContent = description;
            document.getElementById('modalImage').src = '../includes/product_pic/' + image;
            document.getElementById('modalBrand').textContent = brand;
            document.getElementById('modalCategory').textContent = category;
            document.getElementById('modalQuantity').max = stock;
            document.getElementById('modalQuantity').value = 1;
            document.getElementById('modalPage').value = '<?php echo $currentPage; ?>';
            document.getElementById('productModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Reset review form
            document.getElementById('ratingValue').value = 5;
            setRating(5); // Highlight 5 stars by default
            document.querySelectorAll('.rating-filter').forEach((btn, idx) => {
                if (idx === 0) {
                    btn.style.backgroundColor = '#e8ff47';
                    btn.style.color = '#000';
                } else {
                    btn.style.backgroundColor = '#2a2a32';
                    btn.style.color = '#e8ff47';
                }
            });
            
            // Load reviews for this product
            loadProductReviews(productId);
            checkUserCanReview(productId);
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function increaseQuantity() {
            const quantityInput = document.getElementById('modalQuantity');
            const maxStock = parseInt(quantityInput.max) || 999;
            const currentQuantity = parseInt(quantityInput.value) || 1;
            if (currentQuantity < maxStock) {
                quantityInput.value = currentQuantity + 1;
            }
        }

        function decreaseQuantity() {
            const quantityInput = document.getElementById('modalQuantity');
            const currentQuantity = parseInt(quantityInput.value) || 1;
            if (currentQuantity > 1) {
                quantityInput.value = currentQuantity - 1;
            }
        }

        function validateQuantity() {
            const quantityInput = document.getElementById('modalQuantity');
            const maxStock = parseInt(quantityInput.max) || 999;
            let value = parseInt(quantityInput.value) || 1;
            if (value < 1) value = 1;
            if (value > maxStock) value = maxStock;
            quantityInput.value = value;
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
        
        // Review Functions
        let currentRatingFilter = null;
        
        function loadProductReviews(productId) {
            fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?get_reviews=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Backend error:', data.error);
                        displayReviews([], {average: 0, total: 0});
                        displayRatingDistribution({1: 0, 2: 0, 3: 0, 4: 0, 5: 0});
                    } else {
                        displayReviews(data.reviews || [], data.stats || {average: 0, total: 0});
                        displayRatingDistribution(data.distribution || {1: 0, 2: 0, 3: 0, 4: 0, 5: 0});
                    }
                })
                .catch(error => {
                    console.error('Error loading reviews:', error);
                    displayReviews([], {average: 0, total: 0});
                    displayRatingDistribution({1: 0, 2: 0, 3: 0, 4: 0, 5: 0});
                });
        }
        
        function displayReviews(reviews, stats) {
            const avg = stats.average || 0;
            const total = stats.total || 0;
            
            console.log('Displaying reviews:', reviews, 'Stats:', stats); // Debug logging
            
            document.getElementById('avgRating').textContent = avg.toFixed(1);
            
            const reviewsList = document.getElementById('reviewsList');
            if (!reviews || reviews.length === 0) {
                reviewsList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No reviews yet. Be the first to review!</p>';
            } else {
                reviewsList.innerHTML = reviews.map(review => `
                    <div style="padding: 15px; border-bottom: 1px solid #e8ff47; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div>
                                <strong style="color: #e8ff47;">${escapeHtml(review.full_name)}</strong>
                                <div style="color: #999; font-size: 12px;">${review.created_at}</div>
                            </div>
                            <div style="color: #ffb800; font-size: 16px;">${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</div>
                        </div>
                        <p style="color: #f0f0f0; margin: 0;">${escapeHtml(review.review || '')}</p>
                    </div>
                `).join('');
            }
        }
        
        function displayRatingDistribution(distribution) {
            const bars = document.getElementById('ratingBars');
            let total = Object.values(distribution).reduce((a, b) => a + b, 0);
            
            bars.innerHTML = Object.keys(distribution).reverse().map(rating => {
                const count = distribution[rating];
                const percentage = total > 0 ? (count / total * 100) : 0;
                return `
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="color: #999; font-size: 12px; width: 30px;">${rating}★</span>
                        <div style="flex: 1; height: 6px; background-color: #1a1a1f; border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; background-color: #ffb800; width: ${percentage}%;"></div>
                        </div>
                        <span style="color: #999; font-size: 12px; width: 30px; text-align: right;">${count}</span>
                    </div>
                `;
            }).join('');
        }
        
        function filterReviewsByRating(rating, button) {
            currentRatingFilter = rating;
            
            // Update button styles
            document.querySelectorAll('.rating-filter').forEach(btn => {
                btn.style.backgroundColor = '#2a2a32';
                btn.style.color = '#e8ff47';
            });
            button.style.backgroundColor = '#e8ff47';
            button.style.color = '#000';
            
            // Reload reviews with filter
            const productId = document.getElementById('reviewProductId').value;
            fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?get_reviews=' + productId + (rating ? '&rating=' + rating : ''))
                .then(response => response.json())
                .then(data => {
                    displayReviews(data.reviews, data.stats);
                })
                .catch(error => console.error('Error loading reviews:', error));
        }
        
        function checkUserCanReview(productId) {
            const userLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
            if (!userLoggedIn) {
                document.getElementById('notEligibleMessage').style.display = 'block';
                document.getElementById('reviewSubmitSection').style.display = 'none';
                return;
            }
            
            fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?check_can_review=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.can_review) {
                        document.getElementById('reviewSubmitSection').style.display = 'block';
                        document.getElementById('notEligibleMessage').style.display = 'none';
                    } else {
                        document.getElementById('reviewSubmitSection').style.display = 'none';
                        document.getElementById('notEligibleMessage').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error checking review eligibility:', error));
        }
        
        function setRating(rating) {
            document.getElementById('ratingValue').value = rating;
            document.querySelectorAll('.star').forEach((star, index) => {
                if (index < rating) {
                    star.style.color = '#ffb800';
                } else {
                    star.style.color = '#555';
                }
            });
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    </script>
</main>

<?php include 'footer.php'; ?>

