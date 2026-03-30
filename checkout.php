<?php

if (!isset($_SESSION)) session_start();
require_once "../db/connection.php";


// auth check
if (!isset($_SESSION["logged_in"]) || !$_SESSION["logged_in"] || ($_SESSION["active_account_type"] ?? "") != "Buyer") {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized!"
    ]);
    exit;
}

$userId = intval($_SESSION["user_id"] ?? 0);
$productId = intval($_GET["id"] ?? 0);


if ($userId <= 0 || $productId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid input!"
    ]);
    exit;
}

$exists = Database::search(
    "SELECT `id` FROM `cart` WHERE `user_id`=? AND `product_id`=? LIMIT 1",
    "ii",
    [$userId, $productId]
);

if (!$exists || $exists->num_rows == 0) {
    $done  = Database::iud("INSERT INTO `cart`(`user_id`,`product_id`) VALUES (?,?)", "ii", [$userId, $productId]);
    header("Location: ../buyer-dashboard.php?tab=cart");
} else {
    header("Location: ../buyer-dashboard.php?tab=cart");
}
