<?php

if (!isset($_SESSION)) session_start();
require_once "../db/connection.php";

header("Content-Type: application/json");

// Auth Check
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['active_account_type'] ?? "") != "Buyer") {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access!"
    ]);
    exit;
}

$userId = intval($_SESSION['user_id'] ?? 0);
$productId = intval($_POST['product_id'] ?? 0);

if ($userId <= 0 || $productId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request!"
    ]);
    exit;
}

// Check if already in cart
$exists = Database::search(
    "SELECT `id` FROM `cart` WHERE `user_id`=? AND `product_id`=? LIMIT 1",
    "ii",
    [$userId, $productId]
);

if ($exists && $exists->num_rows > 0) {

    //     Remove from cart
    $done = Database::iud(
        "DELETE FROM `cart` WHERE `user_id`=? AND `product_id`=?",
        "ii",
        [$userId, $productId]
    );
    echo json_encode([
        "success" => $done,
        "action" => "removed"
    ]);
} else {
    //     Add to cart
    $done = Database::iud(
        "INSERT INTO `cart` (`user_id`, `product_id`) VALUES (?, ?)",
        "ii",
        [$userId, $productId]
    );
    echo json_encode([
        "success" => $done,
        "action" => "added"
    ]);
}
