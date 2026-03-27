<?php
$uploadDir = __DIR__ . '/uploads/';
$images = [];
$metadata = array();

// Load metadata
$metadataFile = $uploadDir . 'metadata.json';
if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);
    if (!is_array($metadata)) {
        $metadata = array();
    }
}

// Get all images from uploads folder with metadata
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($fileExtension, $allowedExtensions)) {
                // Get product name from metadata or use default
                $productName = isset($metadata[$file]) ? $metadata[$file] : 'Image';
                
                $images[] = array(
                    'filename' => $file,
                    'productName' => $productName
                );
            }
        }
    }
    
    // Sort images by name (most recent first)
    usort($images, function($a, $b) {
        return strcmp($b['filename'], $a['filename']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Pictures</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #e8ff47;
            padding-bottom: 20px;
        }

        h1 {
            color: #e8ff47;
            margin-bottom: 10px;
        }

        .info-text {
            color: #888;
            font-size: 14px;
        }

        .upload-button-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .upload-button-section a {
            display: inline-block;
            background-color: #e8ff47;
            color: #0d0d0f;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .upload-button-section a:hover {
            background-color: #f0ff66;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .gallery-item {
            background-color: #141417;
            border: 2px solid #2a2a32;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .gallery-item:hover {
            border-color: #e8ff47;
            box-shadow: 0 0 15px rgba(232, 255, 71, 0.3);
        }

        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        .gallery-item-info {
            padding: 12px;
            background-color: #0d0d0f;
            border-top: 1px solid #2a2a32;
        }

        .gallery-item-name {
            color: #e8ff47;
            font-size: 14px;
            word-break: break-word;
            margin-bottom: 5px;
            max-height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .gallery-item-filename {
            color: #888;
            font-size: 11px;
            word-break: break-all;
            margin-bottom: 8px;
            max-height: 30px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .gallery-item-delete {
            display: block;
            width: 100%;
            padding: 8px;
            background-color: #ff4747;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s ease;
        }

        .gallery-item-delete:hover {
            background-color: #ff6666;
        }

        .no-images {
            text-align: center;
            padding: 60px 20px;
            background-color: #141417;
            border: 2px dashed #2a2a32;
            border-radius: 8px;
        }

        .no-images h2 {
            color: #888;
            margin-bottom: 15px;
        }

        .no-images p {
            color: #666;
            margin-bottom: 20px;
        }

        .no-images a {
            display: inline-block;
            background-color: #e8ff47;
            color: #0d0d0f;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .no-images a:hover {
            background-color: #f0ff66;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            position: relative;
        }

        .modal-content img {
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #e8ff47;
            background-color: rgba(0, 0, 0, 0.7);
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }

        .modal-close:hover {
            background-color: rgba(0, 0, 0, 0.9);
        }

        .image-count {
            color: #47d4ff;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>🖼️ Uploaded Pictures</h1>
            <p class="info-text">Gallery of all uploaded images</p>
            <?php if (count($images) > 0): ?>
                <p class="image-count">Total Images: <?php echo count($images); ?></p>
            <?php endif; ?>
        </div>

        <div class="upload-button-section">
            <a href="upload.php">⬆️ Upload New Picture</a>
        </div>

        <?php if (count($images) > 0): ?>
            <div class="gallery">
                <?php foreach ($images as $imageData): ?>
                    <div class="gallery-item">
                        <img src="uploads/<?php echo urlencode($imageData['filename']); ?>" alt="<?php echo htmlspecialchars($imageData['productName']); ?>" onclick="openModal('uploads/<?php echo urlencode($imageData['filename']); ?>')">
                        <div class="gallery-item-info">
                            <div class="gallery-item-name" title="<?php echo htmlspecialchars($imageData['filename']); ?>">
                                <strong><?php echo htmlspecialchars($imageData['productName']); ?></strong>
                            </div>
                            <div class="gallery-item-filename">
                                <?php echo htmlspecialchars($imageData['filename']); ?>
                            </div>
                            <form method="POST" action="delete_pic.php" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($imageData['filename']); ?>">
                                <button type="submit" class="gallery-item-delete">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-images">
                <h2>No Pictures Yet</h2>
                <p>You haven't uploaded any pictures yet.</p>
                <a href="upload.php">Upload Your First Picture</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for viewing full size images -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">✕</button>
            <img id="modalImage" src="" alt="Full size image">
        </div>
    </div>

    <script>
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            modal.classList.add('show');
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
        }

        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>

</body>
</html>
