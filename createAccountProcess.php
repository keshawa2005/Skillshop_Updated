<?php

// Include database connection
require "../db/connection.php";

$fname = $_POST['fname'];
$lname = $_POST['lname'];
$email = $_POST['email'];
$password = $_POST['password'];
$cpassword = $_POST['cpassword'];
$accountType = $_POST['accountType'];
$terms = $_POST['terms'];

// Server-side validation
if (empty($fname)) {
    echo "Please enter your first name.";
} else if (empty($lname)) {
    echo "Please enter your last name.";
} else if (empty($email)) {
    echo "Please enter your email.";
} else if (strlen($email) >= 150) {
    echo "Email must be less than 150 characters.";
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Please enter a valid email address.";
} else if (empty($password)) {
    echo "Please enter your password.";
} else if ($password != $cpassword) {
    echo "Passwords do not match.";
} else if (strlen($password) < 8 || strlen($cpassword) < 8) {
    echo "Password length should be between 8 and 20 characters.";
} else if (empty($accountType)) {
    echo "Please select an account type.";
} else if (empty($terms)) {
    echo "Please agree to the terms and conditions.";
} else {

    $passwordHash = password_hash($password, PASSWORD_DEFAULT); // Hash the password before storing

    // Get account type ID from the database
    $result = Database::search("SELECT `id` FROM `account_type` WHERE `name` = ?", "s", [$accountType]);
    if ($result && $row = $result->fetch_assoc()) {
        $accountTypeId = $row['id'];
    } else {
        echo "Invalid account type selected.";
        exit;
    }

    // Check if email already exists in the database
    $check = Database::search("SELECT `id` FROM `user` WHERE `email` = ?", "s", [$email]);
    if ($check && $check->num_rows > 0) {
        echo "An account with this email already exists.";
    } else {

        // Insert new user into the database
        $insertUser = Database::iud("INSERT INTO `user` 
        (`fname`, `lname`, `email`, `password_hash`, `active_account_type_id`) 
        VALUES (?, ?, ?, ?, ?)", "ssssi", [$fname, $lname, $email, $passwordHash, $accountTypeId]);

        if ($insertUser) {

            $user_id = Database::getConnection()->insert_id; // Get the ID of the newly inserted user

            $insertRole = Database::iud("INSERT INTO `user_has_account_type` (`user_id`, `account_type_id`) 
        VALUES (?, ?)", "ii", [$user_id, $accountTypeId]); 

            echo $insertRole ? "success" : "Failed to assign account type.";
        } else {
            echo "Failed to create account. Please try again.";
        }
    }
}
