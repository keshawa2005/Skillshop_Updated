<?php
session_start();
require_once "../db/connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] != true) {
    echo json_encode([]);
    exit();
}

$userId = $_SESSION["user_id"];
$otherId = isset($_GET["other_id"]) ? intval($_GET["other_id"]) : 0;

if ($otherId <= 0) {
    echo json_encode([]);
    exit();
}

// Fetch messages
$sql = "SELECT * FROM `chat` 
        WHERE (`from_user_id`=? AND `to_user_id`=?) OR (`from_user_id`=? AND `to_user_id`=?) 
        ORDER BY `created_at` ASC";

$res = Database::search($sql, "iiii", [$userId, $otherId, $otherId, $userId]);
$messages = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $messages[] = [
            "id" => $row["id"],
            "from_id" => $row["from_user_id"],
            "to_id" => $row["to_user_id"],
            "content" => $row["content"],
            "time" => $row["created_at"],
            "status" => $row["status"],
            "side" => ($row["from_user_id"] == $userId) ? "right" : "left"
        ];
    }
    
    // Mark as seen
    Database::iud("UPDATE `chat` SET `status`='seen' WHERE `from_user_id`=? AND `to_user_id`=? AND `status`='unseen'", "ii", [$otherId, $userId]);
}

echo json_encode($messages);
