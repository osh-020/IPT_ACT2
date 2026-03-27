<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'])) {
    $filename = $_POST['filename'];
    $uploadDir = __DIR__ . '/uploads/';
    $filePath = $uploadDir . basename($filename);

    // Validate filename to prevent directory traversal
    if (basename($filePath) === basename($filename) && file_exists($filePath)) {
        if (unlink($filePath)) {
            // Remove from metadata file
            $metadataFile = $uploadDir . 'metadata.json';
            if (file_exists($metadataFile)) {
                $metadata = json_decode(file_get_contents($metadataFile), true);
                if (is_array($metadata) && isset($metadata[$filename])) {
                    unset($metadata[$filename]);
                    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
                }
            }
            $_SESSION['message'] = 'Image deleted successfully!';
        }
    }
}

// Redirect back to display_pic.php
header('Location: display_pic.php');
exit;
?>
