<?php

session_start();
require_once "../db/connection.php";

header("Content-Type: application/json");


// check remember me token exists
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] != true) {
    echo json_encode(["success" => false, "message" => "Unauthorized!"]);
    exit();
}

$userId = $_SESSION["user_id"];
$pid = isset($_POST["pid"]) ? intval($_POST["pid"]) : 0;
$rating = isset($_POST["rating"]) ? intval($_POST["rating"]) : 5;
$message = isset($_POST["message"]) ? $_POST["message"] : "";

if ($pid <= 0 || empty($message)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid feedback data!"
    ]);
    exit();
}


// verify user actually purchased the product
$purchaseCheck = Database::search(
    "SELECT ii.`id` FROM `invoice_item` ii
    JOIN `invoice` i ON ii.`invoice_id`=i.`id`
    WHERE i.`user_id`=? AND ii.`product_id`=? LIMIT 1",
    "ii",
    [$userId, $pid]
);

if (!$purchaseCheck || $purchaseCheck->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "You can give feedback on products you purchased!"]);
    exit();
}

// check feedback for same product

$existing = Database::search(
    "SELECT `id` FROM `feedback` WHERE `user_id`=? AND `product_id`=? LIMIT 1",
    "ii",
    [$userId, $pid]
);

if ($existing && $existing->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Feedback already provided for this product!"]);
    exit();
}

// save
$date = date("Y-m-d H:i:s");
$res = Database::iud(
    "INSERT INTO `feedback` (`user_id`,`product_id`, `rating`, `message`, `created_at`) VALUES(?,?,?,?,?)",
    "iiiss",
    [$userId, $pid, $rating, $message, $date]
);

if ($res) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save feedback to database!"]);
}
