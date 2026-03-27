<?php
$successMessage = '';
$errorMessage = '';
$previewImage = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    $fileSize = $file['size'];

    // Validate file
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileError !== UPLOAD_ERR_OK) {
        $errorMessage = "File upload error. Please try again.";
    } elseif (!in_array($fileExtension, $allowedExtensions)) {
        $errorMessage = "Only JPG, JPEG, PNG, and GIF images are allowed.";
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
            $successMessage = "Image uploaded successfully!";
        } else {
            $errorMessage = "Failed to upload image. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Picture</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #0d0d0f;
            color: #f0f0f0;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #141417;
            padding: 30px;
            border-radius: 8px;
            border: 2px solid #e8ff47;
        }

        h1 {
            color: #e8ff47;
            margin-bottom: 30px;
            text-align: center;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #1c2a1c;
            color: #4caf50;
            border: 1px solid #4caf50;
        }

        .error {
            background-color: #2a1c1c;
            color: #ff4747;
            border: 1px solid #ff4747;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #e8ff47;
            font-weight: bold;
        }

        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #1c1c21;
            color: #f0f0f0;
            border: 2px solid #2a2a32;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="file"]:focus {
            outline: none;
            border-color: #e8ff47;
            box-shadow: 0 0 10px rgba(232, 255, 71, 0.3);
        }

        .preview-container {
            margin: 20px 0;
            text-align: center;
            display: none;
        }

        .preview-container.show {
            display: block;
        }

        .preview-container img {
            max-width: 100%;
            max-height: 300px;
            border: 2px solid #47d4ff;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .preview-label {
            color: #47d4ff;
            margin-bottom: 10px;
            font-weight: bold;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #e8ff47;
            color: #0d0d0f;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #f0ff66;
        }

        button:disabled {
            background-color: #888;
            cursor: not-allowed;
        }

        .info-text {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
            text-align: center;
        }

        .link-section {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #2a2a32;
        }

        .link-section a {
            color: #47d4ff;
            text-decoration: none;
            padding: 10px 15px;
            border: 1px solid #47d4ff;
            border-radius: 5px;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s ease;
        }

        .link-section a:hover {
            background-color: #47d4ff;
            color: #0d0d0f;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>📸 Upload Picture</h1>

        <?php if ($successMessage): ?>
            <div class="message success">
                ✓ <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="message error">
                ✗ <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="image">Select Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
                <p class="info-text">Allowed: JPG, JPEG, PNG, GIF (Max 5MB)</p>
            </div>

            <div class="preview-container" id="previewContainer">
                <p class="preview-label">Preview:</p>
                <img id="previewImage" alt="Preview">
            </div>

            <button type="submit">Upload Image</button>
        </form>

        <div class="link-section">
            <a href="display_pic.php">View All Uploaded Pictures</a>
        </div>
    </div>

    <script>
        const imageInput = document.getElementById('image');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF)');
                    imageInput.value = '';
                    previewContainer.classList.remove('show');
                    return;
                }

                // Check file size
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
