<?php

session_start();
require "../db/connection.php";

$email = $_POST["email"];
$password = $_POST["password"];
$rememberMe = isset($_POST["rememberMe"]) ? $_POST["rememberMe"] : "false";

// Validate email format
if (empty($email)) {
    echo "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email address.";
} elseif (empty($password)) {
    echo "Password is required.";
} else {

    $result = Database::search(
        "SELECT `id`, `fname`, `lname`, `email`, `password_hash`, `active_account_type_id` 
        FROM `user` WHERE `email` = ?",
        "s",
        [$email],
    );

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if($user["status"] == "blocked"){
            echo "Your account has been blocked. Please contact support.";
            exit();
        }

        if (password_verify($password, $user["password_hash"])) {

        // Day 7: Fetch account type name
        $r = Database::search("SELECT `name` FROM `account_type` WHERE `id` = ?", "i", [$user["active_account_type_id"]]);
        if ($r && $r->num_rows > 0) {
            $user_type_row = $r->fetch_assoc();
        }

        // Create session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["fname"] . " " . $user["lname"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["account_type_id"] = $user["active_account_type_id"];
            $_SESSION["active_account_type"] = $user_type_row["name"]; //Day 7: Store account type name in session
            $_SESSION["logged_in"] = true;

            if ($rememberMe === "true") {
                $rememberToken = bin2hex(random_bytes(32)); // Generate a random token
                $tokenHash = password_hash($rememberToken, PASSWORD_DEFAULT); // Hash the token for secure storage

                $expiry = date("Y-m-d H:i:s", strtotime("+30 days")); // Set token expiry (e.g., 30 days)
                Database::iud(
                    "INSERT INTO `remember_tokens` (`user_id`, `token_hash`, `expiry`) VALUES (?, ?, ?)",
                    "iss",
                    [$user["id"], $tokenHash, $expiry]
                );

                // Set the remember me cookie with the token (expires in 30 days)
                setcookie(
                    "skillshop_remember",
                    $rememberToken,
                    strtotime("+30 days"),
                    "/",
                );

                setcookie(
                    "skillshop_user_id",
                    $user["id"],
                    strtotime("+30 days"),
                    "/",
                );

                setcookie(
                    "skillshop_user_email",
                    $user["email"],
                    strtotime("+30 days"),
                    "/",
                );
            } else {
                // Clear any existing remember me cookies
                setcookie("skillshop_remember", "", time() - 3600, "/");
                setcookie("skillshop_user_id", "", time() - 3600, "/");
                setcookie("skillshop_user_email", "", time() - 3600, "/");
            }
            echo "success";
        } else {
            echo "Invalid user credentials.";
        }
    } else {
        echo "Invalid user credentials.";
    }
}
