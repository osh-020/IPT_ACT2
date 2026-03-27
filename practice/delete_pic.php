<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'])) {
    $filename = $_POST['filename'];
    $uploadDir = __DIR__ . '/uploads/';
    $filePath = $uploadDir . basename($filename);

    // Validate filename to prevent directory traversal
    if (basename($filePath) === basename($filename) && file_exists($filePath)) {
        if (unlink($filePath)) {
            $_SESSION['message'] = 'Image deleted successfully!';
        }
    }
}

// Redirect back to display_pic.php
header('Location: display_pic.php');
exit;
?>
