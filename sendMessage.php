<?php
session_start();
require_once "../db/connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] != true) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$from_id = $_SESSION["user_id"];
$to_id = isset($_POST["to_id"]) ? intval($_POST["to_id"]) : 0;
$content = isset($_POST["content"]) ? trim($_POST["content"]) : "";

if ($to_id <= 0 || empty($content)) {
    echo json_encode(["success" => false, "message" => "Invalid message data"]);
    exit();
}

if ($from_id == $to_id) {
    echo json_encode(["success" => false, "message" => "You cannot send a message to yourself."]);
    exit();
}

$res = Database::iud(
    "INSERT INTO `chat` (`from_user_id`, `to_user_id`, `content`) VALUES (?, ?, ?)",
    "iis", [$from_id, $to_id, $content]
);

if ($res) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to send message."]);
}
