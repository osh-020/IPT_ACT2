<?php
session_start();
include '../includes/db_connect.php';

$errors = [];

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = isset($_POST['full_name']) ? htmlspecialchars(trim($_POST['full_name'])) : '';
    $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $age = isset($_POST['age']) ? intval($_POST['age']) : '';
    $gender = isset($_POST['gender']) ? htmlspecialchars(trim($_POST['gender'])) : '';
    $civil_status = isset($_POST['civil_status']) ? htmlspecialchars(trim($_POST['civil_status'])) : '';
    $mobile_number = isset($_POST['mobile_number']) ? htmlspecialchars(trim($_POST['mobile_number'])) : '';
    $address = isset($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : '';
    $zip_code = isset($_POST['zip_code']) ? htmlspecialchars(trim($_POST['zip_code'])) : '';

    // Validation
    // Full Name: Required, letters & spaces only
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $errors[] = "Full name must contain only letters and spaces";
    }

    // Username: 5-15 characters, alphanumeric + underscore, starts with letter, no spaces
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 5 || strlen($username) > 15) {
        $errors[] = "Username must be between 5 and 15 characters";
    } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $username)) {
        $errors[] = "Username must start with a letter and contain only letters, numbers, and underscores (no spaces or special characters)";
    }

    // Email: Required, valid format
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // Password: 8-20 characters, uppercase, lowercase, number, avoid special chars :;,'""/|
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8 || strlen($password) > 20) {
        $errors[] = "Password must be between 8 and 20 characters";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter (A-Z)";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter (a-z)";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number (0-9)";
    } elseif (preg_match('/[:;\',"\"|]/', $password)) {
        $errors[] = "Password cannot contain :;,'\"/| characters";
    } elseif (strpos($password, ' ') !== false) {
        $errors[] = "Password cannot contain spaces";
    }

    // Confirm Password: Must match password
    if ($password != $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Age: 18-60 numeric
    if (empty($age)) {
        $errors[] = "Age is required";
    } elseif (!is_numeric($age) || $age < 18 || $age > 60) {
        $errors[] = "Age must be a number between 18 and 60";
    }

    // Gender: Required
    if (empty($gender)) {
        $errors[] = "Gender is required";
    }

    // Civil Status: Required
    if (empty($civil_status)) {
        $errors[] = "Civil status is required";
    }

    // Mobile Number: Philippine format (09XXXXXXXXX - 11 digits)
    if (empty($mobile_number)) {
        $errors[] = "Mobile number is required";
    } elseif (!preg_match('/^09\d{9}$/', $mobile_number)) {
        $errors[] = "Mobile number must be in Philippine format (e.g., 09XXXXXXXXX - 11 digits starting with 09)";
    }

    // Address: Required
    if (empty($address)) {
        $errors[] = "Address is required";
    }

    // ZIP Code: Numeric, 4 digits
    if (empty($zip_code)) {
        $errors[] = "ZIP code is required";
    } elseif (!preg_match('/^\d{4}$/', $zip_code)) {
        $errors[] = "ZIP code must be exactly 4 digits";
    }

    // Terms: Required
    if (!isset($_POST['terms'])) {
        $errors[] = "You must agree to the Terms and Conditions";
    }

    if (empty($errors)) {
        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();

        if ($check_result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, age, gender, civil_status, mobile_number, address, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssisssss", $full_name, $username, $email, $hashed_password, $age, $gender, $civil_status, $mobile_number, $address, $zip_code);

            if ($insert_stmt->execute()) {
                // Get the new user ID
                $new_user_id = $insert_stmt->insert_id;
                $insert_stmt->close();

                // Automatically log in the new user
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;

                header("Location: home.php");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
    }
}
?>

<?php include 'header.php'; ?>

<main class="main-content">
    <div class="auth-container">
        <div class="auth-box register-box">
            <h2>Create Your Account</h2>

            <?php
            if (!empty($errors)) {
                echo "<div class='error-message'><ul>";
                foreach ($errors as $error) {
                    echo "<li>$error</li>";
                }
                echo "</ul></div>";
            }
            ?>

            <form method="POST" action="register.php" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name * <span style="font-size: 12px; color: #999;">(Letters and spaces only)</span></label>
                        <input type="text" id="full_name" name="full_name" pattern="[a-zA-Z\s]+" maxlength="100" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" title="Full name must contain only letters and spaces" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username * <span style="font-size: 12px; color: #999;">(5-15 chars, letters/numbers/_)</span></label>
                        <input type="text" id="username" name="username" pattern="[a-zA-Z][a-zA-Z0-9_]{4,14}" minlength="5" maxlength="15" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" title="Must start with a letter, 5-15 characters, only letters, numbers, and underscores" placeholder="e.g., user_123" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" title="Please enter a valid email address" required>
                    </div>

                    <div class="form-group">
                        <label for="age">Age * <span style="font-size: 12px; color: #999;">(18-60)</span></label>
                        <input type="number" id="age" name="age" min="18" max="60" value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>" title="Age must be between 18 and 60" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="civil_status">Civil Status *</label>
                        <select id="civil_status" name="civil_status" required>
                            <option value="">Select Status</option>
                            <option value="Single" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                            <option value="Divorced" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                            <option value="Widowed" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number * <span style="font-size: 12px; color: #999;">(09XXXXXXXXX)</span></label>
                        <input type="text" id="mobile_number" name="mobile_number" pattern="09\d{9}" maxlength="11" minlength="11" value="<?php echo isset($_POST['mobile_number']) ? htmlspecialchars($_POST['mobile_number']) : ''; ?>" placeholder="09123456789" title="Philippine format: 09XXXXXXXXX (11 digits)" required>
                    </div>

                    <div class="form-group">
                        <label for="zip_code">ZIP Code * <span style="font-size: 12px; color: #999;">(4 digits)</span></label>
                        <input type="text" id="zip_code" name="zip_code" pattern="\d{4}" maxlength="4" minlength="4" value="<?php echo isset($_POST['zip_code']) ? htmlspecialchars($_POST['zip_code']) : ''; ?>" placeholder="1234" title="Must be exactly 4 digits" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" rows="3" maxlength="500" placeholder="Enter your complete address" title="Please enter your address" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password * <span style="font-size: 12px; color: #999;">(8-20 chars)</span></label>
                        <input type="password" id="password" name="password" minlength="8" maxlength="20" placeholder="Uppercase, lowercase, number (no spaces, no :;,'\"/|)" title="8-20 characters with uppercase, lowercase, number" required>
                        <small style="color: #999; display: block; margin-top: 5px;">
                            Requirements: 8-20 chars • At least 1 uppercase (A-Z) • At least 1 lowercase (a-z) • At least 1 number (0-9) • No spaces or special chars :;,'""/|
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="20" placeholder="Re-enter your password" title="Must match the password above" required>
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                    <input type="checkbox" id="terms" name="terms" required style="width: 18px; height: 18px; cursor: pointer; margin-top: 2px;">
                    <label for="terms" style="margin: 0; cursor: pointer; font-size: 14px;">I agree to the <a href="#" style="color: #007bff; text-decoration: none;">Terms and Conditions</a></label>
                </div>

                <button type="submit" class="btn btn-primary">Register</button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="home.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
