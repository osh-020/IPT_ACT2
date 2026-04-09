<?php
session_start();
include ("../includes/db_connect.php");

$successMessage = '';
$errorMessage = '';
$product = null;

// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    $errorMessage = "Invalid product ID.";
} else {
    // Fetch product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $errorMessage = "Product not found.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == "POST" && $product) {
    // Get form data
    $productName = htmlspecialchars(trim($_POST['product_name'] ?? ''));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $price = floatval($_POST['price'] ?? 0);
    $category = htmlspecialchars(trim($_POST['category'] ?? ''));
    $brand = htmlspecialchars(trim($_POST['brand'] ?? ''));
    $stock = intval($_POST['stock'] ?? 0);
    $imageName = $product['image']; // Default to existing image

    // Validate form fields
    if (empty($productName)) {
        $errorMessage = "Product name is required.";
    } elseif (empty($description)) {
        $errorMessage = "Description is required.";
    } elseif ($price <= 0) {
        $errorMessage = "Price must be greater than 0.";
    } elseif (empty($category)) {
        $errorMessage = "Category is required.";
    } elseif (empty($brand)) {
        $errorMessage = "Brand is required.";
    } elseif ($stock < 0) {
        $errorMessage = "Stock quantity cannot be negative.";
    } else {
        // Check if new image is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $file = $_FILES['image'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileError = $file['error'];
            $fileSize = $file['size'];

            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileError !== UPLOAD_ERR_OK) {
                $errorMessage = "File upload error. Please try again.";
            } elseif (!in_array($fileExtension, $allowedExtensions)) {
                $errorMessage = "Only JPG, JPEG, and PNG images are allowed.";
            } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                $errorMessage = "File size must not exceed 5MB.";
            } else {
                // Create unique filename
                $newFileName = time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                $uploadDir = dirname(__DIR__) . '/includes/product_pic/';
                
                // Create uploads directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $uploadPath = $uploadDir . $newFileName;

                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    // Delete old image if it exists
                    $oldImagePath = $uploadDir . $product['image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                    $imageName = $newFileName;
                } else {
                    $errorMessage = "Failed to upload image. Please try again.";
                }
            }
        }

        // Update product if no error occurred
        if (empty($errorMessage)) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, brand = ?, stock = ?, image = ? WHERE id = ?");
            
            if ($stmt) {
                $stmt->bind_param("ssdsissi", $productName, $description, $price, $category, $brand, $stock, $imageName, $productId);
                
                if ($stmt->execute()) {
                    // Set session message and redirect to manage products
                    $_SESSION['successMessage'] = "Product updated successfully!";
                    header("Location: manage_product.php");
                    exit();
                } else {
                    $errorMessage = "Failed to update product. Please try again.";
                }
                $stmt->close();
            } else {
                $errorMessage = "Database error: " . $conn->error;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="icon" type="image/png" href="../includes/website_pic/logo.png">
    <link rel="stylesheet" href="../includes/admin_style.css">
</head>
<body class="edit_product">

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
        <h2>Edit Product</h2>

        <?php if ($successMessage): ?>
            <div class="success-message">
                ✓ <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <form action="edit_product.php?id=<?php echo $product['id']; ?>" method="POST" enctype="multipart/form-data" id="productForm">
                <div class="form-group">
                    <label for="image">Product Image (Optional - leave blank to keep current):</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <div class="preview-container show" id="previewContainer">
                    <p class="preview-label">Current Image:</p>
                    <img id="previewImage" src="../includes/product_pic/<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.classList.add('no-image'); this.alt='No Image Available'">
                </div>

                <div class="form-group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="product_name" placeholder="e.g., Gaming PC Core i9" 
                           value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" placeholder="Detailed product description..." required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price:</label>
                        <input type="number" id="price" name="price" step="0.01" 
                               value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Quantity:</label>
                        <input type="number" id="stock" name="stock" step="1" 
                               value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <input type="text" id="category" name="category" placeholder="e.g., Processors, Graphics Cards" 
                               value="<?php echo htmlspecialchars($product['category']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="brand">Brand:</label>
                        <input type="text" id="brand" name="brand" placeholder="e.g., Intel, AMD, NVIDIA" 
                               value="<?php echo htmlspecialchars($product['brand']); ?>" required>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-submit">Update Product</button>
                    <a href="manage_product.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p class="error-message"><?php echo $errorMessage ?: "No product data available."; ?></p>
            <a href="manage_product.php" class="btn-cancel">Back to Products</a>
        <?php endif; ?>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('previewImage').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
