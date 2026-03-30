<?php
// This script retrieves cities based on the provided country ID and returns them in JSON format.
header('Content-Type: application/json');

require "../db/connection.php";
// Check if the country_id is provided in the POST request
if (isset($_POST["country_id"])) {

    $countryId = intval($_POST["country_id"]);

    $citiesResult = Database::search(
        "SELECT `id`, `name` FROM `city` WHERE `country_id` = ? ORDER BY `name`",
        "i",
        [$countryId]
    );

    $cities = [];
    if ($citiesResult && $citiesResult->num_rows > 0) {
        while ($city = $citiesResult->fetch_assoc()) {
            $cities[] = $city;
        }
    }
// Return the cities in JSON format
    echo json_encode([
        "success" => true,
        "cities" => $cities
    ]);
} else {
    echo json_encode([
        "success" => false,
        "cities" => []
    ]);
}
