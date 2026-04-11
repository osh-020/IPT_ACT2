<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch current user data
$fetch_stmt = $conn->prepare("SELECT full_name, email, mobile_number, address, zip_code FROM users WHERE user_id = ?");
$fetch_stmt->bind_param("i", $user_id);
$fetch_stmt->execute();
$result = $fetch_stmt->get_result();
$user = $result->fetch_assoc();
$fetch_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || empty($mobile_number) || empty($address) || empty($zip_code)) {
        $error_message = "All fields are required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else if (!preg_match('/^[0-9]{10,15}$/', $mobile_number)) {
        $error_message = "Mobile number must be 10-15 digits.";
    } else {
        // Check if email is already taken by another user
        $email_check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $email_check->bind_param("si", $email, $user_id);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $error_message = "Email is already in use.";
        } else {
            // Update profile
            if (!empty($new_password)) {
                // Update with password change
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, mobile_number = ?, address = ?, zip_code = ?, password = ? WHERE user_id = ?");
                $update_stmt->bind_param("ssssssi", $full_name, $email, $mobile_number, $address, $zip_code, $hashed_password, $user_id);
            } else {
                // Update without password change
                $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, mobile_number = ?, address = ?, zip_code = ? WHERE user_id = ?");
                $update_stmt->bind_param("sssssi", $full_name, $email, $mobile_number, $address, $zip_code, $user_id);
            }

            if ($update_stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $success_message = "Profile updated successfully!";
                $user = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'mobile_number' => $mobile_number,
                    'address' => $address,
                    'zip_code' => $zip_code
                ];
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - COMPUTRONIUM</title>
    <link rel="stylesheet" href="../includes/customer_style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="edit-profile-container">
        <h1>Edit Profile</h1>

        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_profile.php">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="mobile_number">Mobile Number</label>
                <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($user['mobile_number'] ?? ''); ?>" placeholder="10-15 digits" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="zip_code">Zip Code</label>
                <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password (Optional)</label>
                <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                <p class="info-text">Only fill this if you want to change your password</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="dashboard.php" class="btn btn-secondary" style="text-align: center; text-decoration: none; line-height: 1.5;">Cancel</a>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>

