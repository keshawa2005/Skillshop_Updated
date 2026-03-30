<?php

require "../db/connection.php";
require "email/email.php";

$email = $_POST["email"];

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email address.";
} else {

    $result = Database::search(
        "SELECT `id`, `fname` FROM `user` WHERE `email` = ?",
        "s",
        [$email]
    );

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Delete old records
        Database::iud(
            "DELETE FROM `password_reset_tokens` WHERE `user_id` = ?",
            "i",
            [$user["id"]]
        );

        // Generate new code and store in database
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiry = date("Y-m-d H:i:s", time() + 600); // expires 10 minutes from now

        Database::iud(
            "INSERT INTO `password_reset_tokens` (`user_id`, `token_hash`, `expiry`) 
            VALUES (?, ?, ?)",
            "iss",
            [$user["id"], $codeHash, $expiry]
        );

        EmailHelper::sendResetCode($email, $user["fname"], $code);
        echo "success";
    } else {
        echo "No User found!";
    }
}
