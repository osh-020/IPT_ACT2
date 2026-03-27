# Image Upload System - Code Tutorial

## Table of Contents
1. [Project Overview](#project-overview)
2. [File Structure](#file-structure)
3. [upload.php - Complete Breakdown](#uploadphp---complete-breakdown)
4. [display_pic.php - Complete Breakdown](#display_picphp---complete-breakdown)
5. [delete_pic.php - Complete Breakdown](#delete_picphp---complete-breakdown)
6. [How Everything Works Together](#how-everything-works-together)
7. [Security Practices](#security-practices)
8. [Database Setup (Optional)](#database-setup-optional)

---

## Project Overview

This image upload system consists of three main PHP files that work together to:
- Allow users to upload images with preview
- Display uploaded images in a gallery
- Delete images from the server

**Technologies Used:**
- PHP 7.0+
- HTML5
- CSS3
- JavaScript (ES6)
- File System Operations

---

## File Structure

```
practice/
├── upload.php           # Image upload form with preview
├── display_pic.php      # Gallery display page
├── delete_pic.php       # Delete handler
├── tutorial.php         # User tutorial (HTML)
├── tutorial.md          # This file - code documentation
└── uploads/             # Folder where images are stored
```

---

## upload.php - Complete Breakdown

### Purpose
Handles file upload form, validates files, stores them on the server, and displays real-time preview.

### Full Code Walkthrough

#### 1. PHP Backend (Server-side)

```php
<?php
$successMessage = '';
$errorMessage = '';
$previewImage = '';
```

**Explanation:**
- Initialize empty variables to store feedback messages
- These will be displayed to the user after form submission

#### 2. Handle File Upload

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    $fileSize = $file['size'];
```

**Explanation:**
- `$_SERVER['REQUEST_METHOD'] === 'POST'` - Check if form was submitted
- `isset($_FILES['image'])` - Check if a file was uploaded
- Extract file information from the `$_FILES` superglobal:
  - `$file['name']` - Original filename from user's computer
  - `$file['tmp_name']` - Temporary location on server
  - `$file['error']` - Error code (0 = no error)
  - `$file['size']` - File size in bytes

#### 3. Validate File Type

```php
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileError !== UPLOAD_ERR_OK) {
    $errorMessage = "File upload error. Please try again.";
} elseif (!in_array($fileExtension, $allowedExtensions)) {
    $errorMessage = "Only JPG, JPEG, PNG, and GIF images are allowed.";
```

**Explanation:**
- `pathinfo($fileName, PATHINFO_EXTENSION)` - Extract file extension (e.g., "jpg")
- `strtolower()` - Convert to lowercase (so "JPG" becomes "jpg")
- `$fileError !== UPLOAD_ERR_OK` - Check for upload errors (error code 0 means OK)
- `in_array($fileExtension, $allowedExtensions)` - Check if extension is in allowed list

**PHP Constants for Upload Errors:**
```
UPLOAD_ERR_OK = 0           // No error
UPLOAD_ERR_INI_SIZE = 1     // File too large (php.ini limit)
UPLOAD_ERR_FORM_SIZE = 2    // File too large (form limit)
UPLOAD_ERR_PARTIAL = 3      // Partial upload
UPLOAD_ERR_NO_FILE = 4      // No file uploaded
UPLOAD_ERR_NO_TMP_DIR = 6   // No temp directory
UPLOAD_ERR_CANT_WRITE = 7   // Can't write to disk
UPLOAD_ERR_EXTENSION = 8    // Upload stopped by extension
```

#### 4. Validate File Size

```php
elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
    $errorMessage = "File size must not exceed 5MB.";
```

**Explanation:**
- `5 * 1024 * 1024` = 5,242,880 bytes = 5 megabytes
- `$file['size']` returns file size in bytes
- If file is larger than 5MB, show error

#### 5. Create Unique Filename

```php
} else {
    // Create unique filename
    $newFileName = time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
    $uploadDir = __DIR__ . '/uploads/';
```

**Explanation:**
- `time()` - Returns current Unix timestamp (seconds since Jan 1, 1970)
- `rand(1000, 9999)` - Random 4-digit number
- `__DIR__` - Full path to current directory
- Result: `1711500000_4567.jpg` (prevents filename conflicts)

**Why unique filenames?**
- Two users uploading "photo.jpg" won't overwrite each other
- Timestamps ensure files are unique
- Random numbers add extra uniqueness

#### 6. Create Upload Directory

```php
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
```

**Explanation:**
- `is_dir()` - Check if directory exists
- `mkdir()` - Create directory if it doesn't exist
- `0777` - File permissions (read/write/execute for all)
- `true` - Create parent directories if needed (recursive)

#### 7. Move Uploaded File

```php
$uploadPath = $uploadDir . $newFileName;

if (move_uploaded_file($fileTmpName, $uploadPath)) {
    $successMessage = "Image uploaded successfully!";
} else {
    $errorMessage = "Failed to upload image. Please try again.";
}
```

**Explanation:**
- `move_uploaded_file()` - Move file from temp location to permanent location
- Parameters: source (temp file), destination (uploads folder)
- Returns `true` on success, `false` on failure
- Show appropriate message

### HTML Form Structure

```html
<form method="POST" enctype="multipart/form-data" id="uploadForm">
    <div class="form-group">
        <label for="image">Select Image:</label>
        <input type="file" id="image" name="image" accept="image/*" required>
    </div>

    <div class="preview-container" id="previewContainer">
        <p class="preview-label">Preview:</p>
        <img id="previewImage" alt="Preview">
    </div>

    <button type="submit">Upload Image</button>
</form>
```

**Key Attributes:**
- `method="POST"` - Send form data via POST (required for file uploads)
- `enctype="multipart/form-data"` - **CRITICAL** - Allows file upload in form
- `accept="image/*"` - Restrict file picker to images only
- `required` - Field must be filled before submission

**Without `enctype="multipart/form-data"`, file uploads won't work!**

### JavaScript - Real-time Preview

```javascript
const imageInput = document.getElementById('image');
const previewContainer = document.getElementById('previewContainer');
const previewImage = document.getElementById('previewImage');

imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];

    if (file) {
        // Client-side validation
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF)');
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
    }
});
```

**Explanation:**

1. **Event Listener:** `addEventListener('change')` - Triggers when user selects a file

2. **Get File Object:** `e.target.files[0]` - Get first selected file

3. **Client-side Validation:**
   - Check MIME type (e.g., "image/jpeg")
   - Check file size before uploading
   - Show alerts if validation fails

4. **FileReader API:**
   - `new FileReader()` - Create file reader instance
   - `reader.readAsDataURL(file)` - Read file as data URL
   - `reader.onload` - Callback when reading is complete
   - `e.target.result` - Returns base64 encoded image data

5. **Display Preview:**
   - Set image src to base64 data
   - Show preview container using CSS class

**Why Preview is Important:**
- Users can see image before uploading
- Catches mistakes early
- Improves user experience

---

## display_pic.php - Complete Breakdown

### Purpose
Reads uploaded images from the uploads folder and displays them in a responsive gallery with modal viewer and delete functionality.

### Step 1: Read Files from Uploads Folder

```php
<?php
$uploadDir = __DIR__ . '/uploads/';
$images = [];

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($fileExtension, $allowedExtensions)) {
                $images[] = $file;
            }
        }
    }
    
    rsort($images);
}
```

**Explanation:**

- `scandir($uploadDir)` - Get list of all files and folders
  - Returns array including `.` (current dir) and `..` (parent dir)
  - Must filter these out: `if ($file !== '.' && $file !== '..')`

- Loop through files:
  - Extract extension
  - Check if it's an image file
  - Add to `$images` array if valid

- `rsort($images)` - Sort in reverse order (newest first)

### Step 2: Display Gallery Grid

```html
<div class="gallery">
    <?php foreach ($images as $image): ?>
        <div class="gallery-item">
            <img src="uploads/<?php echo urlencode($image); ?>" 
                 alt="<?php echo htmlspecialchars($image); ?>" 
                 onclick="openModal('uploads/<?php echo urlencode($image); ?>')">
            
            <div class="gallery-item-info">
                <div class="gallery-item-name">
                    <?php echo htmlspecialchars($image); ?>
                </div>
                
                <form method="POST" action="delete_pic.php">
                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($image); ?>">
                    <button type="submit" class="gallery-item-delete">🗑️ Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

**Security Functions Used:**

- `urlencode($image)` - Encode URL special characters
  - Converts spaces to `%20`
  - Safe for use in URLs
  
- `htmlspecialchars($image)` - Escape HTML special characters
  - Converts `<` to `&lt;`
  - Converts `>` to `&gt;`
  - Prevents XSS (cross-site scripting) attacks

**Why Security Matters:**
If filename is `<script>alert('hacked')</script>.jpg`:
- Without escaping: Script runs! 🚨 DANGER
- With escaping: Shows as text only ✅ SAFE

### Step 3: Modal for Full-Size Images

```html
<div id="imageModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <img id="modalImage" src="" alt="Full size image">
    </div>
</div>
```

**Modal CSS (Hidden by default):**
```css
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
```

### Step 4: Modal JavaScript Functions

```javascript
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
```

**Explanation:**

1. `openModal()` - Show modal with image
   - Add `show` class to make visible
   
2. `closeModal()` - Hide modal
   - Remove `show` class to make invisible
   
3. Click outside modal - Close
   - `if (e.target === this)` - Was the click on background?
   
4. Escape key - Close
   - `e.key === 'Escape'` - Was Escape pressed?

---

## delete_pic.php - Complete Breakdown

### Purpose
Safely delete uploaded images from the server.

### Complete Code

```php
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
```

### Security Analysis

#### 1. Check POST Request
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename']))
```
- Only process POST requests
- Verify filename was submitted

#### 2. Directory Traversal Attack Prevention
```php
$filePath = $uploadDir . basename($filename);

if (basename($filePath) === basename($filename))
```

**What is directory traversal?**
User tries to delete files outside uploads folder using:
```
filename = "../../../etc/passwd"
```

**How basename() protects:**
- `basename("../../../etc/passwd")` returns `"passwd"`
- `basename($filePath)` = `basename("/var/www/uploads/passwd")`= `"passwd"`
- They match! ✅ Safe

Without this check, attacker could delete system files! 🚨

#### 3. Delete File
```php
if (unlink($filePath)) {
    $_SESSION['message'] = 'Image deleted successfully!';
}
```

- `unlink($filePath)` - Delete file from server
- Returns `true` if successful, `false` if failed
- Only sets message if deletion succeeds

#### 4. Redirect
```php
header('Location: display_pic.php');
exit;
```

- `header()` - Send HTTP header
- Redirects user back to gallery
- `exit` - Stop script execution after redirect

---

## How Everything Works Together

### Complete Flow Diagram

```
1. User visits upload.php
   ↓
2. Selects image file
   ↓ (JavaScript)
3. Shows real-time preview
   ↓
4. User clicks "Upload Image"
   ↓ (Browser sends POST request)
5. upload.php processes:
   - Validates file type ✓
   - Validates file size ✓
   - Creates unique filename
   - Moves file to uploads/ ✓
   ↓
6. Shows success message
   ↓
7. User clicks "View Gallery"
   ↓
8. display_pic.php:
   - Reads uploads/ folder
   - Lists all images
   - Shows gallery grid
   ↓
9. User can:
   a) Click image → view full size in modal
   b) Click delete → delete_pic.php
      → removes file from server
      → redirects back to gallery

```

### Data Flow Example

**User uploads "vacation.jpg" (3MB):**

```
browser (upload.php)
    ↓ (POST request)
server (upload.php backend)
    ↓
1. Check: is it an image? ✓ (MIME type)
2. Check: is it under 5MB? ✓ (3MB < 5MB)
3. Check: correct extension? ✓ (.jpg allowed)
4. Create filename: 1711500000_4567.jpg
5. Move file to uploads/1711500000_4567.jpg
    ↓
server stores: /var/www/uploads/1711500000_4567.jpg
    ↓
browser displays: ✓ Image uploaded successfully!
```

---

## Security Practices

### 1. File Type Validation (Dual Layer)

**Client-side (upload.php - JavaScript):**
```javascript
const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!allowedTypes.includes(file.type)) { reject }
```
- Fast feedback for users
- CANNOT be trusted (can be bypassed)

**Server-side (upload.php - PHP):**
```php
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($fileExtension, $allowedExtensions)) { reject }
```
- Mandatory security check
- User cannot bypass this

**Why both?**
- Client-side: Better UX (instant feedback)
- Server-side: Real security (cannot be bypassed)

### 2. Filename Sanitization

**Problem:** Attackers upload files with dangerous names
```
../../etc/passwd.jpg
<script>alert('hack')</script>.jpg
union select * from users;.jpg
```

**Solution: Unique filenames**
```php
$newFileName = time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
```

Result: `1711500000_4567.jpg` - Safe filename!

### 3. Directory Traversal Prevention

**In display_pic.php** (when deleting):
```php
$filePath = $uploadDir . basename($filename);
if (basename($filePath) === basename($filename)) {
    // Safe to delete
}
```

**Prevents:**
- `filename = "../admin/secret.txt"` ← blocked
- `filename = "/etc/passwd"` ← blocked
- `filename = "normal_image.jpg"` ← allowed

### 4. XSS (Cross-Site Scripting) Prevention

**Problem:** Filenames displayed to users
```html
<img alt="<?php echo $filename; ?>">
```

If filename = `" onerror="alert('hacked")`
Result: `<img alt="" onerror="alert('hacked')">`
Script runs! 🚨

**Solution: escape output**
```html
<img alt="<?php echo htmlspecialchars($filename); ?>">
```

Result: `<img alt="&quot; onerror=&quot;alert('hacked')">`
Shows as text only ✅

### 5. File Size Limits

```php
if ($fileSize > 5 * 1024 * 1024) { // 5MB
    reject
}
```

**Prevents:**
- Server disk space exhaustion
- Slow uploads
- Denial of service attacks

---

## Database Setup (Optional)

If you want to store image metadata in a database:

### Create Table

```sql
CREATE TABLE uploaded_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_size INT NOT NULL,
    uploaded_by INT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### Insert Record on Upload

```php
// After move_uploaded_file() succeeds
$stmt = $conn->prepare("INSERT INTO uploaded_images (filename, original_name, file_size) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $newFileName, $fileName, $fileSize);
$stmt->execute();
$stmt->close();
```

### Fetch Images from Database

```php
// Instead of scandir()
$result = $conn->query("SELECT filename FROM uploaded_images ORDER BY upload_date DESC");
$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row['filename'];
}
```

### Delete from Database

```php
// In delete_pic.php
$stmt = $conn->prepare("DELETE FROM uploaded_images WHERE filename = ?");
$stmt->bind_param("s", $filename);
if ($stmt->execute()) {
    unlink($uploadDir . $filename); // Also delete the file
}
$stmt->close();
```

---

## Summary

| Concept | Purpose | Security |
|---------|---------|----------|
| **Dual Validation** | Check file type twice | Prevents malicious uploads |
| **Unique Filenames** | Avoid conflicts | Prevents overwrites & attacks |
| **basename()** | Extract only filename | Prevents directory traversal |
| **htmlspecialchars()** | Escape HTML | Prevents XSS attacks |
| **File Size Check** | Limit uploads | Prevents DoS attacks |
| **FileReader API** | Show preview | Improves UX |
| **Modal Viewer** | View full size | Better UX |

---

## Quick Reference

### Key PHP Functions Used
- `$_FILES` - Access uploaded files
- `move_uploaded_file()` - Move file to permanent location
- `scandir()` - List directory contents
- `unlink()` - Delete file
- `basename()` - Get filename without path
- `pathinfo()` - Extract file path components
- `strtolower()` - Convert to lowercase
- `in_array()` - Check if value exists in array
- `urlencode()` - Encode URL special characters
- `htmlspecialchars()` - Escape HTML characters

### Key JavaScript Functions Used
- `addEventListener()` - Attach event handler
- `FileReader()` - Read file as data
- `readAsDataURL()` - Convert file to base64
- `classList.add/remove()` - Modify CSS classes
- `document.getElementById()` - Get element

### Allowed MIME Types
- `image/jpeg` (.jpg, .jpeg)
- `image/png` (.png)
- `image/gif` (.gif)

---

**Created:** March 27, 2026  
**System:** VOLTCORE Image Upload System  
**Version:** 1.0
