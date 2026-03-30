<?php

if (!isset($_SESSION)) {
    session_start();
}

header("Content-Type: application/json");
require_once "../db/connection.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != true || $_SESSION['active_account_type'] != "Seller") {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized Access!"
    ]);
    http_response_code(401);
    exit;
}
$userId = $_SESSION['user_id'];
$productId = intval($_POST['productId'] ?? 0);

if ($productId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid product ID!"
    ]);
    http_response_code(400);
    exit;
}

// Fetch current product status
$statusResult = Database::search(
    "SELECT `status` FROM `product` WHERE `id` = ? AND `seller_id` = ?",
    "ii",
    [$productId, $userId]
);

if (!$statusResult || $statusResult->num_rows == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Product not found or unauthorized!"
    ]);
    http_response_code(403);
    exit;
}

$currentStatus = $statusResult->fetch_assoc()['status'];

// toggle product status
$newStatus = ($currentStatus == "Active") ? "Blocked" : "Active";

// Update product status
$result = Database::iud(
    "UPDATE `product` SET `status` = ? WHERE `id` = ? AND `seller_id` = ?",
    "sii",
    [$newStatus, $productId, $userId]
);

if ($result) {
    echo json_encode([
        "success" => true,
        "message" => "Product status updated successfully!",
        "newStatus" => $newStatus
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update product status!"
    ]);
    http_response_code(500);
    exit;
}

