<?php

if (!isset($_SESSION)) session_start();
require_once "../db/connection.php";

// Auth Check
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['active_account_type'] ?? "") != "Buyer") {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access!"
    ]);
    exit;
}

$userId = intval($_SESSION['user_id'] ?? 0);

// Fetch cart items
$cartItemsQ = Database::search(
    "SELECT c.`id` AS `cart_item_id`, p.*, 
    u.`fname` AS `seller_fname`, 
    u.`lname` AS `seller_lname`, 
    sa.`city_id` AS `seller_city_id`, 
    sa.`id` AS `seller_id` 
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

if (!$cartItemsQ || $cartItemsQ->num_rows == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Cart is empty!"
    ]);
    exit;
}

$buyerCityQ = Database::search(
    "SELECT a.`city_id` FROM `user_profile` up
JOIN `address` a ON up.`address_id` = a.`id`
WHERE up.`user_id` = ?",
    "i",
    [$userId]
);
$buyerCityId = ($buyerCityQ && $buyerCityQ->num_rows > 0) ? $buyerCityQ->fetch_assoc()["city_id"] : 0;

$subtotal = 0;
$totalDeliveryFee = 0;
$sellersInCart = [];

while ($item = $cartItemsQ?->fetch_assoc()) {
    $subtotal += floatval($item["price"]);

    $sellerId = $item["seller_id"];
    if (!isset($sellersInCart[$sellerId])) {
        $deliveryFee = ($item["seller_city_id"] == $buyerCityId && $buyerCityId != 0) ? 200 : 500;
        $totalDeliveryFee += $deliveryFee;
        $sellersInCart[$sellerId] = $deliveryFee;
    }
}

$total = $subtotal + $totalDeliveryFee;

$merchantId = "1234552";
$merchantSecret = "MTU4MzYzODg3NTc3ODE2Njg2OTIyNTUzMTY5MzQxMjU1NTYzMDcz";
$currency = "LKR";
$formattedTotal = number_format($total, 2, ".", "");
$orderId = "ORD" . uniqid();

$hash = strtoupper(
    md5(
        $merchantId .
        $orderId .
        $formattedTotal .
        $currency .
        strtoupper(md5($merchantSecret))
    )
);

$userQ = Database::search(
    "SELECT u.`fname`, u.`lname`, u.`email`,
    up.`mobile`,
    a.`line1`, a.`line2`,
    c.`name` AS `city_name`,
    co.`name` AS `country_name`
 FROM `user`u 
 JOIN `user_profile` up ON u.`id` = up.`user_id`
 JOIN `address` a ON up.`address_id` = a.`id`
 JOIN `city` c ON a.`city_id` = c.`id`
 JOIN `country` co ON c.`country_id` = co.`id`
 WHERE u.`id` = ?",
    "i",
    [$userId]
);
$user = $userQ->fetch_assoc();

$paymentObject = [
    "sandbox" => true,
    "merchant_id" => $merchantId,
    "return_url" => "http://localhost/Skillshop_online/buyer-dashboard.php",
    "cancel_url" => "http://localhost/Skillshop_online/buyer-dashboard.php",
    "notify_url" => "",
    "order_id" => $orderId,
    "items" => "SkillShop Purchase",
    "amount" => $formattedTotal,
    "currency" => $currency,
    "hash" => $hash,
    "first_name" => $user["fname"],
    "last_name" => $user["lname"],
    "email" => $user["email"],
    "phone" => $user["mobile"] ?? "0000000000",
    "address" => trim($user["line1"] . ", " . ($user["line2"] ?? "")),
    "city" => $user["city_name"],
    "country" => $user["country_name"]
];

echo json_encode([
    "success" => true,
    "data" => $paymentObject
]);
