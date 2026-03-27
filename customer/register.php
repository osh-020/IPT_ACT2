<?php

include("../includes/db_connect.php");



if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    $fname = $_POST['full_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $civil_status = $_POST['civil_status'];
    $mobile_number = $_POST['mobile_number'];
    $address = $_POST['address'];
    $zip_code = $_POST['zip_code'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO `users`(`full_name`, `age`, `gender`, `civil_status`, `mobile_number`, `address`, `zip_code`, `email`, `username`, `password`)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sissssssss', $fname, $age, $gender, $civil_status, $mobile_number, $address, $zip_code, $email, $username, $hashed_password);

    try{
        mysqli_stmt_execute($stmt);
        echo"<p style = color:green;>Registered successfully</p>";    
    } catch (mysqli_sql_exception){
        echo"<p style = color:red;>Username already exists*</p>";
    }

}
?>