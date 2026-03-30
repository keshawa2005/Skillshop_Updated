<?php
// This file is included at the top of pages that require authentication. 
// It checks if the user is logged in and redirects to the sign-in page if not.

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../db/connection.php'; // Adjust the path as needed

// Check if user is logged in
if (isset($_COOKIE['skillshop_remember']) && isset($_COOKIE['skillshop_user_id'])) {
    $rememberToken = $_COOKIE['skillshop_remember'];
    $userId = intval($_COOKIE['skillshop_user_id']);

    // Check if the token is valid and not expired
    $tokenResult = Database::search(
        "SELECT `token_hash` FROM `remember_tokens` WHERE `user_id` = ? AND `expiry` > NOW() ORDER BY `created_at` DESC LIMIT 1",
        "i",
        [$userId]
    );
    // If a valid token is found, log the user in by setting session variables
    if ($tokenResult && $tokenResult->num_rows > 0) {
        $tokenRecord = $tokenResult->fetch_assoc();

        if (password_verify($rememberToken, $tokenRecord['token_hash'])) {

            // Fetch user details and account type in one query
            $userResult = Database::search(
                "SELECT u.`id`, u.`fname`, u.`lname`, u.`email`, u.`active_account_type_id` , at.`name`
                FROM `user` u 
                JOIN `account_type` at ON u.`active_account_type_id` = at.`id`
                WHERE u.`id` = ?",
                "i",
                [$userId]
            );

            if ($userResult && $user = $userResult->fetch_assoc()) {

                // Set session variables
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["fname"] . " " . $user["lname"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["account_type_id"] = $user["active_account_type_id"];
                $_SESSION["active_account_type"] = $user["name"]; // Store account type name in session
                $_SESSION["logged_in"] = true;

                // Delete all other tokens for this user except the current one to prevent multiple active sessions
                Database::iud(
                    "DELETE FROM `remember_tokens` WHERE `user_id` = ? AND `token_hash` !=?",
                    "is",
                    [$userId, $tokenRecord['token_hash']]
                );

                return true; // User is logged in via remember me token

            }
        }
    }

    // If token is invalid or expired, clear the cookies
    setcookie('skillshop_remember', '', time() - 3600, '/');
    setcookie('skillshop_user_id', '', time() - 3600, '/');
    setcookie('skillshop_user_email', '', time() - 3600, '/');
}
