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
$cartItemId = intval($_POST['cart_item_id'] ?? 0);

if ($userId <= 0 || $cartItemId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request!"
    ]);
    exit;
}

//     Delete cart item
$deleteResult = Database::iud(
    "DELETE FROM `cart` WHERE `id`=? AND `user_id`=?",
    "ii",
    [$cartItemId, $userId]
);

if ($deleteResult) {
    $buyerCityQ = Database::search(
        "SELECT a.`city_id` FROM `user_profile` up
    JOIN `address` a ON up.`address_id` = a.`id`
    WHERE up.`user_id` = ?",
        "i",
        [$userId]
    );
    $buyerCityId = ($buyerCityQ && $buyerCityQ->num_rows > 0) ? $buyerCityQ->fetch_assoc()["city_id"] : 0;

    // Fetch cart items
    $cartItemsQ = Database::search(
        "SELECT c.`id` AS `cart_item_id`, p.*, u.`fname` AS `seller_fname`, u.`lname` AS `seller_lname`, 
        sa.`city_id` AS `seller_city_id`, sa.`id` AS `seller_id` 
    FROM `cart` c
    JOIN `product` p ON c.`product_id` = p.`id`
    JOIN `user` u ON p.`seller_id` = u.`id`
    LEFT JOIN `user_profile` up ON u.`id` = up.`user_id`
    LEFT JOIN `address` sa ON up.`address_id` = sa.`id`
    WHERE c.`user_id` = ?
    ORDER BY c.`created_at` DESC",
        "i",
        [$userId]
    );

    $subtotal = 0;
    $totalDeliveryFee = 0;
    $sellersInCart = [];
    $itemCount = 0;

    while ($item = $cartItemsQ?->fetch_assoc()) {
        $itemCount++;
        $subtotal += floatval($item["price"]);

        $sellerId = $item["seller_id"];
        if (!isset($sellersInCart[$sellerId])) {
            $deliveryFee = ($item["seller_city_id"] == $buyerCityId && $buyerCityId != 0) ? 200 : 500;
            $totalDeliveryFee += $deliveryFee;
            $sellersInCart[$sellerId] = $deliveryFee;
        }
    }

        echo json_encode([
            "success" => true,
            "subtotal" => $subtotal,
            "delivery" => $totalDeliveryFee,
            "total" => $subtotal + $totalDeliveryFee,
            "itemCount" => $itemCount
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to remove item!"
        ]);
    }

