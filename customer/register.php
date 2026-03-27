<?php

include("../includes/db_connect.php");

$errors = [];
$success = false;
$fname = '';
$age = '';
$gender = '';
$civil_status = '';
$mobile_number = '';
$address = '';
$zip_code = '';
$email = '';
$username = '';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    $fname = $_POST['full_name'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $mobile_number = $_POST['mobile_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Full Name validation
    if (empty($fname)) {
        $errors[] = "Full Name is required.";
    } elseif (!preg_match('/^[A-Za-z ]+$/', $fname)) {
        $errors[] = "Full Name can only contain letters and spaces.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[^;:\'\"\/\s]{8,20}$/', $password)) {
        $errors[] = "Password must be 8-20 characters, include uppercase, lowercase, numbers, no spaces or ;:'\"/";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Mobile number validation (Philippine format)
    if (empty($mobile_number)) {
        $errors[] = "Mobile number is required.";
    } elseif (!preg_match('/^09\d{9}$/', $mobile_number)) {
        $errors[] = "Mobile number must start with 09 and be 11 digits long.";
    }

    // ZIP code validation
    if (empty($zip_code)) {
        $errors[] = "ZIP Code is required.";
    } elseif (!preg_match('/^\d{4}$/', $zip_code)) {
        $errors[] = "ZIP Code must be 4 digits.";
    }

    // If no validation errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO `users`(`full_name`,
                                        `age`,
                                        `gender`,
                                        `civil_status`,
                                        `mobile_number`,
                                        `address`,
                                        `zip_code`,
                                        `email`,
                                        `username`,
                                        `password`)
            VALUES (?,?,?,?,?,?,?,?,?,?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sissssssss', 
                                            $fname,
                                            $age,
                                            $gender,
                                            $civil_status,
                                            $mobile_number,
                                            $address,
                                            $zip_code,
                                            $email,
                                            $username,
                                            $hashed_password
                                        );

        try{
            mysqli_stmt_execute($stmt);
            $success = true;
            $fname = $age = $gender = $civil_status = $mobile_number = $address = $zip_code = $email = $username = '';
        } catch (mysqli_sql_exception $e){
            $errors[] = "Username already exists.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="register-page">

    <div class="container">
    <h1><span class="lSide">COMPU</span><span class="rSide">TRONIUM</span></h1>
    <p>Welcome to Computronium! Please fill out the form below to create your account.</p>
        <?php if ($success): ?>
            <div class="success-message">
                <strong>Registered successfully!</strong> You can now <a href="login.php">log in</a>.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <strong> Registration failed:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="registrationForm">
            <h2>Register</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fname); ?>" required>
                </div>
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($age); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>     
                        <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>    
                    </select>
                </div>
                <div class="form-group">
                    <label for="civil_status">Civil Status:</label>
                    <select id="civil_status" name="civil_status" required>
                        <option value="">Select Civil Status</option>
                        <option value="Single" <?php echo $civil_status === 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo $civil_status === 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Divorced" <?php echo $civil_status === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                        <option value="Widowed" <?php echo $civil_status === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="mobile_number">Mobile Number:</label>
                    <input type="text" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($mobile_number); ?>" placeholder="09XXXXXXXXX" required>
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="zip_code">Zip Code:</label>
                    <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>" placeholder="0000" required>
                </div>
                <div class="form-group">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
            </div>

            <button type="submit">Register</button>
        </form>
    </div>

</body>
</html>