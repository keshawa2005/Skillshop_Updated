<?php

header("Content-Type: application/json");

if (!isset($_SESSION)) {
    session_start();
}

require "../db/connection.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access!"
    ]);
    exit;
}

$userId = $_SESSION["user_id"];

// Get form data
$fname = isset($_POST['fname']) ? $_POST['fname'] : '';
$lname = isset($_POST['lname']) ? $_POST['lname'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$bio = isset($_POST['bio']) ? $_POST['bio'] : '';
$genderId = isset($_POST['genderId']) ? $_POST['genderId'] : '';
$mobile = isset($_POST['mobile']) ? $_POST['mobile'] : '';
$line1 = isset($_POST['line1']) ? $_POST['line1'] : '';
$line2 = isset($_POST['line2']) ? $_POST['line2'] : '';
$cityId = isset($_POST['cityId']) ? $_POST['cityId'] : '';
$avatarUrl = isset($_POST['avatarUrl']) ? $_POST['avatarUrl'] : '';


// Validation
if (empty($fname)) {
    echo json_encode([
        "success" => false,
        "message" => "First name is required!"
    ]);
} else if (empty($lname)) {
    echo json_encode([
        "success" => false,
        "message" => "Last name is required!"
    ]);
} else if (empty($email)) {
    echo json_encode([
        "success" => false,
        "message" => "Email is required!"
    ]);
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email format!"
    ]);
} else if (strlen($email) >= 150) {
    echo json_encode([
        "success" => false,
        "message" => "Email must be less than 150 characters!"
    ]);
} else if (!empty($bio) && strlen($bio) >= 500) {
    echo json_encode([
        "success" => false,
        "message" => "Bio must be less than 500 characters!"
    ]);
} else if (!empty($mobile) && !preg_match("/^\d{10}$/", $mobile)) {
    echo json_encode([
        "success" => false,
        "message" => "Mobile number must be 10 digits!"
    ]);
} else if (empty($line1)) {
    echo json_encode([
        "success" => false,
        "message" => "Address Line 1 must be required!"
    ]);
} else if ($cityId == 0) {
    echo json_encode([
        "success" => false,
        "message" => "City must be required!"
    ]);
} else {

    // --- DATABASE LOGIC SECTION ---
    try {
        // 1. Update basic User info
        Database::iud(
            "UPDATE `user` SET `fname` = ?, `lname` = ? WHERE `id` = ?",
            "ssi",
            [$fname, $lname, $userId]
        );

        // 2. Fetch profile AND address_id (Must include address_id)
        $profileCheck = Database::search(
            "SELECT `address_id` FROM `user_profile` WHERE `user_id` = ?",
            "i",
            [$userId]
        );

        if ($profileCheck && $profileCheck->num_rows > 0) {
            $row = $profileCheck->fetch_assoc();
            $userAddressId = $row["address_id"];

            // 3. Update or Create Address
            if ($userAddressId > 0) {
                $updateUserAddress = Database::iud(
                    "UPDATE `address` SET `line1` = ?, `line2` = ?, `city_id` = ? WHERE `id` = ?",
                    "ssii",
                    [$line1, $line2, $cityId, $userAddressId]
                );
                if (!$updateUserAddress) {
                    throw new Exception("Failed to update address!");
                }
            } else {
                $insertAddress = Database::iud(
                    "INSERT INTO `address` (`line1`, `line2`, `city_id`) VALUES (?, ?, ?)",
                    "ssi",
                    [$line1, $line2, $cityId]
                );
                if ($insertAddress) {
                    $userAddressId = Database::getConnection()->insert_id;
                }
            }

            // 4. Update Profile Table
            $updateProfile = Database::iud(
                "UPDATE `user_profile` SET `avatar_url` = ?, `bio` = ?, `gender_id` = ?, `mobile` = ?, `address_id` = ? WHERE `user_id` = ?",
                "ssisii",
                [$avatarUrl, $bio, $genderId, $mobile, $userAddressId, $userId]
            );
            if (!$updateProfile) {
                throw new Exception("Failed to update profile!");
            }
        } else {
            // 5. Create new address if not exists

            $addressId = 0;

            if (!empty($line1) || !empty($line2) || $cityId > 0) {

                if ($cityId > 0) {

                    $insertAddress = Database::iud(
                        "INSERT INTO `address` (`line1`, `line2`, `city_id`) VALUES (?, ?, ?)",
                        "ssi",
                        [$line1, $line2, $cityId]
                    );
                    if ($insertAddress) {
                        $addressId = Database::getConnection()->insert_id;
                    }
                }
            }
            //           6. Create new profile if not exists

            $insertProfile = Database::iud(
                "INSERT INTO `user_profile` (`user_id`, `avatar_url`, `bio`, `gender_id`, `mobile`, `address_id`) VALUES (?, ?, ?, ?, ?, ?)",
                "issisi",
                [$userId, $avatarUrl, $bio, $genderId, $mobile, $addressId]
            );
            if (!$insertProfile) {
                throw new Exception("Failed to create profile!");
            }
        }

        // Update session display name
        $_SESSION["user_name"] = $fname . " " . $lname;

        echo json_encode([
            "success" => true,
            "message" => "Profile updated successfully!"
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage()
        ]);
    }
}
