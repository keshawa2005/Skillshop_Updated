<?php

require "../db/connection.php";
require "email/email.php";

$email = $_POST["email"];
$action = $_POST["action"];

// Basic validation for email and action
if ($action === "verify") {

    $code = $_POST["code"];

    if (empty($code)) {
        echo "Verification code is required.";
    } else {

        $result = Database::search(
            "SELECT `id` FROM `user` WHERE `email` = ?",
            "s",
            [$email]
        );

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Fetch the latest token for the user
            $codeResult = Database::search(
                "SELECT `token_hash`, `expiry` FROM `password_reset_tokens` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT 1",
                "i",
                [$user["id"]]
            );

            // Check if a code exists and is valid
            if (!$codeResult || $codeResult->num_rows == 0) {
                echo "No code requested.";
            } else {
                $codeRecord = $codeResult->fetch_assoc();
                $expiry = strtotime($codeRecord["expiry"]);
                $now = time();

                if ($now > $expiry) {
                    echo "Code expired."; // Code has expired
                } else if (password_verify($code, $codeRecord["token_hash"])) {
                    echo "success"; // Code is valid
                } else {
                    echo "Invalid code."; // Code does not match

                }
            }
        }
    }
    // If action is reset
} elseif ($action === "reset") {

    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    if (empty($password)) {
        echo "Password is required.";
    } else if ($password !== $confirmPassword) {
        echo "Passwords do not match.";
    } else if (strlen($password) < 8) {
        echo "Password must be at least 8 characters long.";
    } else {
        $result = Database::search(
            "SELECT `id` FROM `user` WHERE `email` = ?",
            "s",
            [$email]
        );

        if ($result || $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Update the user's password
            Database::iud(
                "UPDATE `user` SET `password_hash` = ? WHERE `id` = ?",
                "si",
                [password_hash($password, PASSWORD_DEFAULT), $user["id"]]
            );

            // delete the used token to prevent reuse
            Database::iud(
                "DELETE FROM `password_reset_tokens` WHERE `user_id` = ?",
                "i",
                [$user["id"]]
            );

            echo "success"; // Password reset successful
        }
    }
} else {
    echo "Invalid action.";
}
