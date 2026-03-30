<?php
session_start();
require_once "../db/connection.php";

header("Content-Type: application/json");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] != true) {
    echo json_encode(["success" => false, "message" => "Unauthorized!"]);
    exit();
}

$user_id = $_SESSION["user_id"];

$res = Database::search(
    "SELECT COUNT(`id`) as `c` FROM `chat` WHERE `to_user_id` = ? AND `status` = 'unseen'",
    "i",
    [$user_id]
);

$count = ($res && $res->num_rows > 0) ? $res->fetch_assoc()["c"] : 0;

echo json_encode(["count" => intval($count)]);

?>