<?php

session_start();
require_once "../db/connection.php";

header("Content-Type: application/json");

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$id = isset($_POST["id"]) ? $_POST["id"] : "";

if (empty($id)) {
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit();
}

$res = Database::search("SELECT `status` FROM `user` WHERE `id`=?", "i", [$id]);

if ($res && $res->num_rows > 0) {
    
$user = $res->fetch_assoc();
$newStatus = ($user["status"] == "Active") ? "Blocked" : "Active";

Database::iud("UPDATE `user` SET `status`=? WHERE `id`=?", "si", [$newStatus, $id]);

echo json_encode(["success" => true, "newStatus" => $newStatus]);

}else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}