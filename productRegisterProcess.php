<?php

if (!isset($_SESSION)) {
    session_start();
}
header("Content-Type: text/plain");

require_once "../db/connection.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "Unauthorized Access!";
    exit;
}

$userRole = isset($_SESSION['active_account_type']) ? $_SESSION['active_account_type'] : '';
if (strtolower($userRole) != "seller") {
    echo "Only sellers can register products!";
    exit;
}

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "";

// Get POST data
$productTitle = isset($_POST['productTitle']) ? trim($_POST['productTitle']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$categoryId = isset($_POST['category']) ? intval($_POST['category']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : '';
$level = isset($_POST['level']) ? trim($_POST['level']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validation
if (empty($productTitle)) {
    echo "Product title is required!";
} else if (strlen($productTitle) > 150) {
    echo "Product title must be less than 150 characters!";
} else if (empty($description)) {
    echo "Product description is required!";
} else if (strlen($description) > 1000) {
    echo "Product description must be less than 1000 characters!";
} else if ($categoryId <= 0) {
    echo "Please select a valid category!";
} else {

    // Verify category exists
    $categoryCheck = Database::search(
        "SELECT `id` FROM `category` WHERE `id`=?",
        "i",
        [$categoryId]
    );
    $validLevels = ["Beginner", "Intermediate", "Advanced"];
    $validStatuses = ["Active", "Inactive"];

    if (!$categoryCheck || $categoryCheck->num_rows == 0) {
        echo "Invalid category selected!";
    } else if ($price <= 0) {
        echo "Price must be greater than 0!";
    } else if (empty($level)) {
        echo "Please select a level!";
    } else if (!in_array($level, $validLevels)) {
        echo "Invalid level selected!";
    } else if (empty($status)) {
        echo "Please select a status!";
    } else if (!in_array($status, $validStatuses)) {
        echo "Invalid status selected!";
    } else {

        // Handle file upload
        if (!isset($_FILES['productImage']) || $_FILES['productImage']['error'] !== UPLOAD_ERR_OK) {
            echo "Please upload a product image!";
            exit;
        }

        $image = $_FILES["productImage"];

        // Validate the image
        $allowedMimes = ["image/jpeg", "image/png", "image.webp", "image/gif"];
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fInfo, $image["tmp_name"]);
        finfo_close($fInfo);

        if (!in_array($mimeType, $allowedMimes)) {
            echo "Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.";
            exit;
        }

        // check file size (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($image["size"] > $maxSize) {
            echo "Image size must be less than 5MB";
            exit;
        }

        // Create upload directory if not exists
        $uploadDir = __DIR__ . "/../uploads/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique file name
        $fileExtension = pathinfo($image["name"], PATHINFO_EXTENSION);
        $fileName = "product_" . $userId . "_" . time() . "_" . bin2hex(random_bytes(4)) . "_" . $fileExtension;
        $filePath = $uploadDir . $fileName;
        $fileUrl = "uploads/products/" . $fileName;

        // Move upload file
        if (!move_uploaded_file($image["tmp_name"], $filePath)) {
            echo "Failed to upload image. Please try again!";
            exit;
        }

        // Insert product to the database
        try {

            $result = Database::iud(
                "INSERT INTO `product` (`seller_id`, `category_id`, `title`, `description`, `price`, `level`, `status`, `image_url`)
    VALUES (?,?,?,?,?,?,?,?)",
                "iissdsss",
                [$userId, $categoryId, $productTitle, $description, $price, $level, $status, $fileUrl]
            );

            if ($result) {
                echo "success";
            } else {
                // Delete uploaded file on error
                unlink($filePath);
                echo "Failed to register the product. Please try again!";
            }
        } catch (Exception $e) {
            // Delete uploaded file on error
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            echo "An error occurred:" . $e->getMessage();
        }
    }
}
