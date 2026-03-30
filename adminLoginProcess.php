<?php
session_start();
require_once "../db/connection.php";

header('Content-Type: application/json');

$email = isset($_POST["email"]) ? $_POST["email"] : "";

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email is required."]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit();
}

$res = Database::search("SELECT * FROM `admin` WHERE `email`=?", "s", [$email]);

if ($res && $res->num_rows > 0) {
    $admin = $res->fetch_assoc();
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    Database::iud("UPDATE `admin` SET `vcode`=? WHERE `email`=?", "ss", [$code, $email]);

    require_once "email/email.php";
    $admin_name = $admin["fname"] . " " . $admin["lname"];
    $mail_sent = EmailHelper::sendAdminVerificationCode($email, $admin_name, $code);

    $_SESSION["admin_verify_email"] = $email;

    echo json_encode([
        "success" => true,
        "message" => $mail_sent ? "Verification code sent to $email" : "Code generated, but email sending failed. Check SMTP config."
    ]);
}
else {
    echo json_encode(["success" => false, "message" => "Admin email not found."]);
}
