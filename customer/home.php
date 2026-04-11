<?php
session_start();
include 'header.php';
include '../includes/db_connect.php';

// Get featured products for carousel (newest 10 products)
$query = "SELECT id, name, price, image, category, description, brand, stock FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 10";
$result = $conn->query($query);
$carouselProducts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $carouselProducts[] = $row;
    }
}

// Get featured products (newest 6 products)
$query2 = "SELECT id, name, price, image, category, description, brand, stock FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 6";
$result2 = $conn->query($query2);
$featuredProducts = [];
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
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
    <!-- Hero Carousel Banner -->
    <section class="hero-carousel" style="position: relative; overflow: hidden; height: 500px; border: 2px solid #e8ff47;">
        <!-- Carousel Slides -->
        <div class="carousel-slides" style="position: relative; width: 100%; height: 100%;">
            <!-- Main Hero Image (First Slide) -->
            <div class="carousel-slide active" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 1; transition: opacity 0.5s ease-in-out;">
                <img src="../includes/website_pic/hero_bg.jpg" alt="Welcome" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            
            <!-- Product Carousel Slides -->
            <?php
            $slideIndex = 1;
            foreach ($carouselProducts as $product) {
                $image = !empty($product['image']) ? htmlspecialchars($product['image']) : 'placeholder.jpg';
                echo "
                <div class='carousel-slide' style='position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; transition: opacity 0.5s ease-in-out;'>
                    <img src='../includes/product_pic/$image' alt='" . htmlspecialchars($product['name']) . "' style='width: 100%; height: 100%; object-fit: cover;' onerror=\"this.src='../includes/product_pic/cpu_intel_i5.jpg'\">
                </div>
                ";
                $slideIndex++;
            }
            ?>
        </div>
        
        <!-- Text Overlay -->
        <div class="hero-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 10; width: 100%;">
            <h1 style="color: #e8ff47; font-size: 48px; margin: 0 0 20px 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">Welcome to COMPUTRONIUM</h1>
            <p style="color: #f0f0f0; font-size: 20px; margin: 0 0 30px 0; text-shadow: 1px 1px 3px rgba(0,0,0,0.8);"><i>Find the best computer components and peripherals</i></p>
            <a href="products.php" class="btn btn-primary" style="padding: 12px 30px; background-color: #e8ff47; color: #000; font-weight: 600; border: none; cursor: pointer; border-radius: 0; font-size: 16px;">Shop Now</a>
        </div>
        
        <!-- Carousel Navigation Dots -->
        <div style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 10; display: flex; gap: 10px;">
            <?php
            $totalSlides = 1 + count($carouselProducts); // Hero bg + product images
            for ($i = 0; $i < $totalSlides; $i++) {
                $activeClass = $i === 0 ? 'style="background-color: #e8ff47; cursor: pointer; width: 12px; height: 12px; border-radius: 50%; border: none;"' : 'style="background-color: rgba(232, 255, 71, 0.5); cursor: pointer; width: 12px; height: 12px; border-radius: 50%; border: none;"';
                echo "<button onclick=\"goToSlide($i)\" $activeClass></button>";
            }
            ?>
        </div>
        
        <!-- Previous and Next Buttons -->
        <button onclick="changeSlide(-1)" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background-color: rgba(232, 255, 71, 0.8); color: #000; border: none; padding: 15px 20px; font-size: 20px; font-weight: bold; cursor: pointer; z-index: 10; border-radius: 0;">❮</button>
        <button onclick="changeSlide(1)" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background-color: rgba(232, 255, 71, 0.8); color: #000; border: none; padding: 15px 20px; font-size: 20px; font-weight: bold; cursor: pointer; z-index: 10; border-radius: 0;">❯</button>
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
                $image = !empty($product['image']) ? htmlspecialchars($product['image']) : 'placeholder.jpg';
                $productId = $product['id'];
                $description = addslashes(htmlspecialchars($product['description']));
                $brand = addslashes(htmlspecialchars($product['brand']));
                $category = addslashes(htmlspecialchars($product['category']));
                $imageName = addslashes($image);
                
                echo "
                <div class='product-card' onclick=\"openProductModal($productId, '" . addslashes($name) . "', '$price', '$imageName', '$description', " . $product['stock'] . ", '$brand', '$category')\" style='cursor: pointer;'>
                    <div class='product-image'>
                        <img src='../includes/product_pic/$image' alt='$name' onerror=\"this.src='../includes/product_pic/cpu_intel_i5.jpg'\">
                    </div>
                    <div class='product-info'>
                        <h3>$name</h3>
                        <p class='category'>" . htmlspecialchars($product['category']) . "</p>
                        <p class='price'>₱$price</p>
                        <div class='btn btn-small' style='text-align: center; background-color: #e8ff47; color: #000; cursor: pointer;'>View Details</div>
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

<!-- Carousel JavaScript -->
<script>
let currentSlide = 0;
let autoRotateInterval;

function showSlide(n) {
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('button[onclick*="goToSlide"]');
    
    if (n >= slides.length) {
        currentSlide = 0;
    }
    if (n < 0) {
        currentSlide = slides.length - 1;
    }
    
    slides.forEach(slide => {
        slide.style.opacity = '0';
    });
    
    dots.forEach(dot => {
        dot.style.backgroundColor = 'rgba(232, 255, 71, 0.5)';
    });
    
    slides[currentSlide].style.opacity = '1';
    dots[currentSlide].style.backgroundColor = '#e8ff47';
}

function changeSlide(n) {
    currentSlide += n;
    showSlide(currentSlide);
    resetAutoRotate();
}

function goToSlide(n) {
    currentSlide = n;
    showSlide(currentSlide);
    resetAutoRotate();
}

function autoRotate() {
    currentSlide++;
    showSlide(currentSlide);
}

function resetAutoRotate() {
    clearInterval(autoRotateInterval);
    autoRotateInterval = setInterval(autoRotate, 5000); // Change slide every 5 seconds
}

// Initialize carousel
showSlide(currentSlide);
resetAutoRotate();
</script>

<!-- Product Modal -->
<div id="productModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7);">
    <div style="background-color: #1c1c21; margin: 10% auto; padding: 30px; border: 2px solid #e8ff47; border-radius: 0; width: 80%; max-width: 900px; max-height: 80vh; overflow-y: auto;">
        <span onclick="closeProductModal()" style="color: #e8ff47; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Product Image -->
            <div style="display: flex; align-items: center; justify-content: center;">
                <img id="modalProductImage" src="" alt="Product" style="max-width: 100%; max-height: 400px; object-fit: contain;">
            </div>
            
            <!-- Product Details -->
            <div>
                <h2 id="modalProductName" style="color: #e8ff47; margin-bottom: 10px;"></h2>
                <p id="modalProductBrand" style="color: #b0b0b0; margin-bottom: 15px;"></p>
                <p id="modalProductCategory" style="color: #b0b0b0; margin-bottom: 15px;"></p>
                
                <p id="modalProductPrice" style="font-size: 28px; color: #e8ff47; font-weight: bold; margin-bottom: 15px;"></p>
                
                <p id="modalProductStock" style="color: #4caf50; margin-bottom: 20px;"></p>
                
                <p id="modalProductDescription" style="color: #f0f0f0; line-height: 1.6; margin-bottom: 30px;"></p>
                
                <!-- Quantity Controls -->
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <label style="color: #e8ff47; font-weight: 600;">Quantity:</label>
                    <button onclick="decreaseQuantity()" style="background-color: #e8ff47; color: #000; border: none; padding: 10px 15px; font-weight: bold; cursor: pointer; border-radius: 0; font-size: 18px;">-</button>
                    <input type="number" id="modalQuantity" value="1" min="1" style="width: 60px; padding: 8px; text-align: center; background-color: #2a2a32; color: #f0f0f0; border: 1px solid #e8ff47; border-radius: 0;" onchange="validateQuantity()">
                    <button onclick="increaseQuantity()" style="background-color: #e8ff47; color: #000; border: none; padding: 10px 15px; font-weight: bold; cursor: pointer; border-radius: 0; font-size: 18px;">+</button>
                </div>
                
                <!-- Add to Cart Form -->
                <form id="addToCartForm" method="POST" action="products.php" style="display: flex; gap: 10px;">
                    <input type="hidden" id="modalProductId" name="product_id" value="">
                    <input type="hidden" name="add_to_cart" value="1">
                    <input type="hidden" id="modalQuantityInput" name="quantity" value="1">
                    <button type="submit" style="padding: 15px 30px; background-color: #e8ff47; color: #000; font-weight: 600; border: none; cursor: pointer; border-radius: 0; flex: 1; font-size: 16px;">Add to Cart</button>
                    <button type="button" onclick="closeProductModal()" style="padding: 15px 30px; background-color: #dc3545; color: white; font-weight: 600; border: none; cursor: pointer; border-radius: 0; font-size: 16px;">Close</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openProductModal(productId, name, price, image, description, stock, brand, category) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalProductName').textContent = name;
    document.getElementById('modalProductBrand').textContent = 'Brand: ' + brand;
    document.getElementById('modalProductCategory').textContent = 'Category: ' + category;
    document.getElementById('modalProductPrice').textContent = '₱' + price;
    document.getElementById('modalProductStock').textContent = stock > 0 ? 'Stock: ' + stock + ' available' : 'Out of Stock';
    document.getElementById('modalProductDescription').textContent = description || 'No description available';
    document.getElementById('modalProductImage').src = '../includes/product_pic/' + image;
    document.getElementById('modalQuantity').value = '1';
    document.getElementById('modalQuantityInput').value = '1';
    document.getElementById('productModal').style.display = 'block';
}

function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

function increaseQuantity() {
    const quantityInput = document.getElementById('modalQuantity');
    const maxStock = parseInt(quantityInput.max) || 999;
    const currentQuantity = parseInt(quantityInput.value) || 1;
    if (currentQuantity < maxStock) {
        quantityInput.value = currentQuantity + 1;
        updateQuantityInput();
    }
}

function decreaseQuantity() {
    const quantityInput = document.getElementById('modalQuantity');
    const currentQuantity = parseInt(quantityInput.value) || 1;
    if (currentQuantity > 1) {
        quantityInput.value = currentQuantity - 1;
        updateQuantityInput();
    }
}

function validateQuantity() {
    const quantityInput = document.getElementById('modalQuantity');
    let value = parseInt(quantityInput.value) || 1;
    if (value < 1) value = 1;
    quantityInput.value = value;
    updateQuantityInput();
}

function updateQuantityInput() {
    const quantity = document.getElementById('modalQuantity').value;
    document.getElementById('modalQuantityInput').value = quantity;
}

window.onclick = function(event) {
    const modal = document.getElementById('productModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>

