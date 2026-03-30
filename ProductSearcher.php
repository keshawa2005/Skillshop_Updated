<?php

class ProductSearcher
{

    private $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    private function buildWhereClause($filters)
    {
        $where = "WHERE p.`status` = 'Active'";
        $having = "";
        $params = [];
        $paramTypes = "";
        $havingParams = [];
        $havingTypes = "";

        // Search query
        if (!empty($filters["q"])) {
            $searchTerm = "%{$filters["q"]}%";
            $where .= " AND (p.`title` LIKE ? OR p.`description` LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $paramTypes .= "ss";
        }

        // Category filter
        if (!empty($filters["category"])) {
            $where .= " AND p.`category_id` = ?";
            $params[] = intval($filters["category"]);
            $paramTypes .= "i";
        }

        // Level filter
        if (!empty($filters["level"])) {
            $where .= " AND p.`level` = ?";
            $params[] = $filters["level"];
            $paramTypes .= "s";
        }

        // Price range filter
        if (!empty($filters["price_min"])) {
            $where .= " AND p.`price` >=?";
            $params[] = floatval($filters["price_min"]);
            $paramTypes .= "d";
        }

        if (!empty($filters["price_max"])) {
            $where .= " AND p.`price` <= ?";
            $params[] = floatval($filters["price_max"]);
            $paramTypes .= "d";
        }

        // Ratings filter
        if ($filters["rating"] != "") {
            $ratingVal = floatval($filters["rating"]);
            if ($ratingVal == 0) {
                $having .= "HAVING avg_rating = 0";
            } else {
                $having .= "HAVING avg_rating >= ?";
                $havingParams[] = $ratingVal;
                $havingTypes .= "d";
            }
        }
        return [
            "where" => $where,
            "params" => $params,
            "types" => $paramTypes,
            "having" => $having,
            "havingParams" => $havingParams,
            "havingTypes" => $havingTypes
        ];
    }

    // Build ORDER BY Clause for sorting
    private function buildSortQuery($sort)
    {
        $allowedSorts = ["newest", "price_low", "price_high", "rating", "popular", "reviews"];
        $sort = in_array($sort, $allowedSorts) ? $sort : "newest";

        return match ($sort) {
            "price_low" => "ORDER BY p.`price` ASC",
            "price_high" => "ORDER BY p.`price` DESC",
            "rating" => "ORDER BY avg_rating DESC",
            "popular" => "ORDER BY customer_count DESC",
            "reviews" => "ORDER BY review_count DESC",
            default => "ORDER BY p.`created_at` DESC"
        };
    }

    // Get total count for pagination
    public function getCount($filters)
    {
        $Clause = $this->buildWhereClause($filters);
        $query = "SELECT p.`id` , AVG(COALESCE(f.`rating`, 0)) AS `avg_rating` FROM `product` p
        LEFT JOIN `order` o ON p.`id` = o.`product_id`
        LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
        LEFT JOIN `user` u ON p.`seller_id` = u.`id`
        {$Clause["where"]}
        GROUP BY p.`id`
        {$Clause["having"]}";

        $params = array_merge($Clause["params"], $Clause["havingParams"]);
        $types = $Clause["types"] . $Clause["havingTypes"];

        $result = Database::search($query, $types, $params);
        return ($result && $result->num_rows > 0) ? $result->num_rows : 0;
    }

    // Search product with filters and sorting
    public function search($filters, $page = 1, $perPage = 12)
    {
        $clause = $this->buildWhereClause($filters);
        $sortQuery = $this->buildSortQuery($filters["sort"] ?? "newest");

        $offset = ($page - 1) * $perPage;

        $query = "SELECT p.`id`, p.`title`, p.`description`, p.`image_url`, p.`price`, p.`level`, p.`created_at`, 
        u.`fname` AS `seller_name`,u.`id` AS `seller_id`,
        COUNT(DISTINCT o.`order_id`) AS `customer_count`,
        AVG(COALESCE(f.`rating`, 0)) AS `avg_rating`,
        COUNT(DISTINCT f.`id`) AS `review_count`
        FROM `product` p
        LEFT JOIN `user` u ON p.`seller_id` = u.`id`
        LEFT JOIN `order` o ON p.`id` = o.`product_id`
        LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
        {$clause["where"]}
        GROUP BY p.`id`
        {$clause["having"]}
        {$sortQuery}
        LIMIT ? OFFSET ?";

        $params = array_merge($clause["params"], $clause["havingParams"], [$perPage, $offset]);
        $types = $clause["types"] . $clause["havingTypes"] . "ii";

        $result = Database::search($query, $types, $params);
        $products = [];

        if ($result && $result->num_rows > 0) {
            while ($product = $result->fetch_assoc()) {
                $products[] = $product;
            }
        }
        return $products;
    }
}
