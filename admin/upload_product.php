<?php
include ("../includes/db_connect.php");

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_FILES['image'])) {
    // Get form data
    $productName = htmlspecialchars(trim($_POST['product_name'] ?? ''));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $price = floatval($_POST['price'] ?? 0);
    $category = htmlspecialchars(trim($_POST['category'] ?? ''));
    $brand = htmlspecialchars(trim($_POST['brand'] ?? ''));
    $stock = intval($_POST['stock'] ?? 0);

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
        // Validate image file
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
            $uploadDir = __DIR__ . '/uploads/';
            
            // Create uploads directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $uploadPath = $uploadDir . $newFileName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Insert product into database (matching actual table columns)
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, brand, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    $stmt->bind_param("ssddsss", $productName, $description, $price, $category, $brand, $stock, $newFileName);
                    
                    if ($stmt->execute()) {
                        $successMessage = "Product uploaded successfully!";
                    } else {
                        // Delete uploaded image if database insert fails
                        unlink($uploadPath);
                        $errorMessage = "Failed to save product to database. Please try again.";
                    }
                    $stmt->close();
                } else {
                    // Delete uploaded image if statement fails
                    unlink($uploadPath);
                    $errorMessage = "Database error: " . $conn->error;
                }
            } else {
                $errorMessage = "Failed to upload image. Please try again.";
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
    <title>Upload Product</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body class="upload_product">

    <div class="container">
        <h2>Upload Product</h2>

        <?php if ($successMessage): ?>
            <div class="success-message">
                ✓ <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="error-message">
                ✗ <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <form action="upload_product.php" method="POST" enctype="multipart/form-data" id="productForm">
             <div class="form-group">
                <label for="image">Product Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>

            <div class="preview-container" id="previewContainer">
                <p class="preview-label">Image Preview:</p>
                <img id="previewImage" alt="Image Preview">
            </div>
            <div class="form-group">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" placeholder="e.g., Gaming PC Core i9" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" placeholder="Detailed product description..." required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label for="stock">Quantity:</label>
                    <input type="number" id="stock" name="stock" step="1" placeholder="0" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category">Category:</label>
                    <input type="text" id="category" name="category" placeholder="e.g., Processors, Graphics Cards" required>
                </div>
                <div class="form-group">
                    <label for="brand">Brand:</label>
                    <input type="text" id="brand" name="brand" placeholder="e.g., Intel, NVIDIA" required>
                </div>
            </div>

           

            <button type="submit">Upload Product</button>
        </form>
    </div>

    <script>
        const imageInput = document.getElementById('image');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                // Client-side validation
                const allowedTypes = ['image/jpeg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG)');
                    imageInput.value = '';
                    previewContainer.classList.remove('show');
                    return;
                }

                // Check file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must not exceed 5MB');
                    imageInput.value = '';
                    previewContainer.classList.remove('show');
                    return;
                }

                // Create preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.remove('show');
            }
        });
    </script>

</body>
</html>