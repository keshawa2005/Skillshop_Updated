<?php

class SellerController
{

    private $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    private function fetchResults($query, $types, $params)
    {
        $result = Database::search($query, $types, $params);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getSellerProducts()
    {
        return $this->fetchResults(
            "SELECT p.*, COUNT(DISTINCT f.`id`) AS `review_count`, COALESCE(AVG(f.`rating`),0) AS `avg_rating`,
            GROUP_CONCAT(f.`rating`) AS `ratings`
FROM `product` p
LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
WHERE p.`seller_id` =?
GROUP BY p.`id`
ORDER BY p.`created_at` DESC",
            "i",
            [$this->userId]
        );
    }

    public function getSellerOrders()
    {
        return $this->fetchResults(
            "SELECT o.*,p.`title`, u.`fname` AS `buyer_name`
        FROM `order` o
        JOIN `product` p ON o.`product_id` = p.`id`
        JOIN `user` u ON o.`user_id` = u.`id`
        WHERE p.`seller_id` = ?
        ORDER BY o.`created_at` DESC",
            "i",
            [$this->userId]
        );
    }

    public function getDashboardStats()
    {
        $products = $this->getSellerProducts();
        $orders = $this->getSellerOrders();
        $earnings = array_sum(array_column($orders, 'total_amount') ?: []);
        $ratings = array_column($products, 'avg_rating');
        return [
            "totalEarnings" => $earnings,
            "totalBuyers" => count($orders),
            "activeProducts" => count($products),
            "avgRating" => count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0,
            "products" => $products,
            "orders" => $orders
        ];
    }
}
