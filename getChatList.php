<?php
session_start();
require_once "../db/connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] != true) {
    echo json_encode([]);
    exit();
}

$userId = $_SESSION["user_id"];

// Fetch unique users the current user has chatted with
$sql = "SELECT DISTINCT 
            CASE WHEN from_user_id = ? THEN to_user_id ELSE from_user_id END AS other_user_id,
            MAX(created_at) as last_chat_time
        FROM chat 
        WHERE from_user_id = ? OR to_user_id = ?
        GROUP BY other_user_id
        ORDER BY last_chat_time DESC";

$res = Database::search($sql, "iii", [$userId, $userId, $userId]);
$chats = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $otherId = $row["other_user_id"];
        
        // Get other user info
        $userRes = Database::search("SELECT fname, lname FROM user WHERE id=?", "i", [$otherId]);
        if ($userRes && $userRes->num_rows > 0) {
            $user = $userRes->fetch_assoc();
            
            // Get last message content
            $lastMsgQ = Database::search(
                "SELECT content, created_at FROM chat 
                 WHERE (from_user_id=? AND to_user_id=?) OR (from_user_id=? AND to_user_id=?) 
                 ORDER BY created_at DESC LIMIT 1",
                 "iiii", [$userId, $otherId, $otherId, $userId]
            );
            $lastMsg = ($lastMsgQ && $lastMsgQ->num_rows > 0) ? $lastMsgQ->fetch_assoc() : ["content" => "", "created_at" => ""];
            
            // Get unread count
            $unreadQ = Database::search(
                "SELECT COUNT(id) as c FROM chat WHERE from_user_id=? AND to_user_id=? AND status='unseen'",
                "ii", [$otherId, $userId]
            );
            $unreadCount = ($unreadQ && $unreadQ->num_rows > 0) ? $unreadQ->fetch_assoc()["c"] : 0;

            $chats[] = [
                "id" => $otherId,
                "name" => $user["fname"] . " " . $user["lname"],
                "last_message" => $lastMsg["content"],
                "time" => $lastMsg["created_at"],
                "unread_count" => $unreadCount
            ];
        }
    }
}

echo json_encode($chats);
