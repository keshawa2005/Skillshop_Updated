<?php
session_start();
require_once "../db/connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION["admin_verify_email"])) {
    echo json_encode(["success" => false, "message" => "Session expired. Please login again."]);
    exit();
}

$email = $_SESSION["admin_verify_email"];
$vcode = isset($_POST["vcode"]) ? $_POST["vcode"] : "";

if (empty($vcode)) {
    echo json_encode(["success" => false, "message" => "Verification code is required."]);
    exit();
}

$res = Database::search("SELECT * FROM `admin` WHERE `email`=? AND `vcode`=?", "ss", [$email, $vcode]);

if ($res && $res->num_rows > 0) {
    // Correct code
    $admin = $res->fetch_assoc();
    
    // Set admin session
    $_SESSION["admin_logged_in"] = true;
    $_SESSION["admin_email"] = $email;
    $_SESSION["admin_fname"] = $admin["fname"];
    $_SESSION["admin_lname"] = $admin["lname"];
    
    // Clear code from DB
    Database::iud("UPDATE `admin` SET `vcode`=NULL WHERE `email`=?", "s", [$email]);
    
    // Clear verify session
    unset($_SESSION["admin_verify_email"]);

    echo json_encode(["success" => true, "message" => "Welcome back, " . $admin["fname"]]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid verification code."]);
}