<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once "db/connection.php";

// Check if user is logged in, if not, check for remember me token
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    require_once 'process/auth-check.php'; // Adjust the path as needed
}

$loggedIn = isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false;
$userRole = isset($_SESSION['active_account_type']) ? $_SESSION['active_account_type'] : '';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "";


require "header.php";

// Search and pagination variables
$searchQuery = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12;

// Build search query
$whereClause = "WHERE p.`status` = 'Active'";
$params = [];
$paramTypes = "";

if (!empty($searchQuery)) {
    $searchTerm = "%{$searchQuery}%";
    $whereClause .= " AND (p.`title` LIKE ? OR p.`description` LIKE ?)";
    $params = [$searchTerm, $searchTerm];
    $paramTypes = "ss";
}

// Get total result count
$countQuery = "SELECT COUNT(p.`id`) AS `total` FROM `product` p {$whereClause}";
$countResult = Database::search($countQuery, $paramTypes, $params);
$totalProducts = ($countResult && $row = $countResult->fetch_assoc()) ? $row['total'] : 0;
$totalPages = ceil($totalProducts / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch products
$productsQuery = " 
SELECT p.`id`, p.`title`, p.`description`, p.`image_url`, p.`price`, p.`level`, p.`created_at`, 
u.`fname` AS `seller_name`,u.`id` AS `seller_id`,
COUNT(DISTINCT o.`order_id`) AS `customer_count`,
AVG(COALESCE(f.`rating`, 0)) AS `avg_rating`,
COUNT(DISTINCT f.`id`) AS `review_count`
FROM `product` p
LEFT JOIN `user` u ON p.`seller_id` = u.`id`
LEFT JOIN `order` o ON p.`id` = o.`product_id`
LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
{$whereClause}
GROUP BY p.`id`
ORDER BY p.`created_at` DESC
LIMIT ? OFFSET ?
";

$params[] = $itemsPerPage;
$params[] = $offset;
$paramTypes .= "ii";

$productsResult = Database::search($productsQuery, $paramTypes, $params); // Use $productsResult
$products = [];

if ($productsResult && $productsResult->num_rows > 0) {
    while ($product = $productsResult->fetch_assoc()) {
        $products[] = $product;
    }
}

$watchlistIds = [];
if ($loggedIn && $userRole == "Buyer" && $userId) {
    $wq = Database::search(
        "SELECT `product_id` FROM `watchlist` WHERE `user_id` = ?",
        "i",
        [$userId]
    );
    while ($row = $wq?->fetch_assoc()) $watchlistIds[$row['product_id']] = true;
}

?>

<div class="min-h-screen bg-gray-50">

    <!-- Search header -->
    <section class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-10 md:py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl md:text-4xl font-bold mb-3 text-center">
                Find your perfect Skill
            </h1>
            <p class="text-blue-100 text-center mb-8">Discover expert led courses across various topics</p>

            <!-- Search box -->
            <form action="search-products.php" method="GET" class="max-w-2xl mx-auto flex gap-2">
                <input
                    type="text"
                    name="q"
                    placeholder="Search skills, topics and instructors"
                    value="<?php echo $searchQuery; ?>"
                    class="flex-1 px-5 py-3 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 
            focus:ring-yellow-400 text-sm"
                    autocomplete="off">
                <button type="submit"
                    class="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold rounded-lg transition-colors">
                    🔍 Search
                </button>
            </form>

            <!-- Results counter -->
            <p class="text-blue-100 text-center text-sm mt-6">

                <?php
                if ($totalProducts > 0) {
                    echo "<span class='font-bold'>" . $totalProducts . "</span>
                    " . ($totalProducts == 1 ? 'skill' : 'skills') . " found ";

                    if (!empty($searchQuery)) echo " &quot; " . $searchQuery . " &quot; ";
                } else {
                    echo "No results found!";
                }
                ?>

            </p>
        </div>
    </section>

    <!-- Results section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <?php if (count($products) > 0): ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">

                <?php foreach ($products as $product):
                    $rating = $product['avg_rating'] > 0 ? round($product['avg_rating'], 1) : 'New';
                ?>

                    <!-- Product card -->
                    <div class="relative bg-white rounded-xl shadow hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col 
border border-gray-100 group">

                        <a href="product-view.php?id=<?php echo $product['id'] ?>" class="absolute inset-0 z-10"
                            aria-label="<?php echo $product['title'] ?>"></a>

                        <!-- Image -->
                        <div class="relative w-full h-48 bg-gray-100 overflow-hidden flex-shrink-0">

                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo $product['image_url'] ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform durarion-300">

                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                                    <svg class="w-14 h-14 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4 12H4l4.5-4.5 3 3 4.5-4.5L16 15z "></path>
                                    </svg>
                                </div>

                            <?php endif; ?>
                            <span class="absolute top-2.5 left-2.5 px-2.5 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full">
                                <?php echo $product["level"] ?>
                            </span>
                        </div>

                        <!-- Wishlist -->
                        <?php if ($loggedIn && $userRole == "Buyer"):
                            $inWl = isset($watchlistIds[$product['id']]);
                        ?>
                            <button type="button" class="wl-heart absolute top-2.5 right-2.5 z-20 w-9 h-9 flex items-center 
                            justify-center rounded-full bg-white/90 shadow-md hover:scale-110 active:scale-95 transition-transform
                            <?php echo $inWl ? "text-rose-500" : "text-gray-400 hover:text-rose-400"; ?>"
                                data-product-id="<?php echo $product['id'] ?>"
                                data-in="<?php echo $inWl ? 1 : 0; ?>"
                                title="<?php echo $inWl ? "Remove from Watchlist" : "Add to Watchlist"; ?>">

                                <svg class="w-5 h-5" fill="<?php echo $inWl ? "currentColor" : "none"; ?>" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l8.682-8.682a4.5 4.5 0 00-6.364-6.364L12 
                                    7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>

                            </button>
                        <?php endif; ?>

                        <!-- Content -->
                        <div class="p-4 flex-1 flex flex-col">

                            <!-- Seller -->
                            <a href="seller-profile.php?id=1"
                                class="relative z-20 text-xs text-blue-600 hover:text-blue-700 font-medium mb-1.5 line-clamp-1 w-fit">
                                <?php echo $product['seller_name'] ?>
                            </a>

                            <!-- Title -->
                            <h3
                                class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 mb-2 group-hover:text-blue-600 transition-colors">
                                <?php echo $product['title'] ?>
                            </h3>

                            <!-- Stats row -->
                            <div class="flex items-center justify-between text-xs mb-3 pb-3 border-b border-gray-100">
                                <div class="flex items-center gap-1">
                                    <span class="text-yellow-400">⭐</span>
                                    <span class="font-semibold text-gray-900"><?php echo $rating ?></span>
                                    <span class="text-gray-400">(<?php echo intval($product['review_count']) ?>)</span>
                                </div>
                                <span class="font-medium text-gray-600"><?php echo intval($product['customer_count']) ?></span>
                            </div>

                            <!-- Price & Arrow -->
                            <div class="flex items-center justify-between mt-auto">
                                <span class="font-bold text-base text-blue-600">Rs. <?php echo $product['price'] ?></span>
                                <div class="px-2.5 py-1.5 bg-blue-50 text-blue-600 text-xs font-bold rounded-lg group-hover:bg-blue-600 
    group-hover:text-white transition-colors"> 👉</div>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- Paginaton -->

            <?php if ($totalPages > 1): ?>
                <nav class="flex justify-center items-center gap-1 mt-12 mb-8">

                    <?php if ($currentPage > 1): ?>
                        <a href="?q=<?php echo urlencode($searchQuery); ?>&page=1" class="p-2 text-gray-600 hover:bg-blue-50 rounded-lg transition-colors"
                            title="First">«</a>
                        <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $currentPage - 1; ?>"
                            class="p-2 text-gray-600 hover:bg-blue-50 rounded-lg transition-colors"
                            title="Previous">‹</a>

                    <?php endif; ?>

                    <?php

                    $start = max(1, $currentPage - 1);
                    $end = min($totalPages, $currentPage + 1);

                    if ($start > 1): ?>
                        <a href="?q=<?php echo urlencode($searchQuery); ?>&page=1"
                            class="p-2 text-sm text-gray-700 hover:bg-blue-50 rounded-lg transition-colors">1</a>
                        <?php if ($start > 2): ?> <span class="px-2 text-gray-400">...</span> <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>"
                            class="p-2 text-sm font-medium <?php echo ($i == $currentPage) ?
                                                                'text-white bg-blue-600' : 'text-gray-700 hover:bg-blue-50'; ?> 
rounded-lg transition-colors"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?> <span class="px-2 text-gray-400">...</span> <?php endif; ?>
                        <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $totalPages; ?>"
                            class="p-2 text-sm text-gray-700 hover:bg-blue-50 rounded-lg transition-colors">
                            <?php echo $totalPages; ?></a>
                    <?php endif; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $currentPage + 1; ?>"
                            class="p-2 text-gray-600 hover:bg-blue-50 rounded-lg transition-colors" title="Next">›</a>
                        <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $totalPages; ?>"
                            class="p-2 text-gray-600 hover:bg-blue-50 rounded-lg transition-colors" title="Last">»</a>
                    <?php endif; ?>


                </nav>
            <?php endif; ?>

        <?php else: ?>

            <!-- Empty state -->
            <div class="text-center py-20">
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No skills found!</h3>
                <p class="text-gray-600 mb-6">
                    <?php echo !empty($searchQuery) ? "Try different keyword or browse all available skills." :
                        "Try searching for a skill to get started." ?>
                </p>

                <div class="flex gap-3 justify-center">
                    <a href="home.php"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg ">
                        Browse all skills
                    </a>
                    <?php if (!empty($searchQuery)): ?>
                        <a href="search-products.php"
                            class="px-6 py-2.5 hover:bg-gray-100 text-gray-700 font-bold rounded-lg border border-gray-300 
                            hover:bg-gray-50 transition-colors">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>

            </div>

        <?php endif; ?>

    </section>

    <!-- featured section -->
    <section class="bg-white border-t border-gray-100 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Why choose SkillShop</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="group text-center p-6 rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="bg-blue-100 w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-200 
                    transition-colors">
                        <span class="text-blue-600 text-2xl font-bold">✍️</span>
                    </div>

                    <h3 class="font-bold text-gray-900 mb-1">Learn at Your Own Pace</h3>
                    <p class="text-sm text-gray-600">Access courses anytime, anywhere from any device.</p>
                </div>

                <div class="group text-center p-6 rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="bg-green-100 w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-200 
                    transition-colors">
                        <span class="text-green-600 text-2xl font-bold">🎓</span>
                    </div>

                    <h3 class="font-bold text-gray-900 mb-1">Expert Instructors</h3>
                    <p class="text-sm text-gray-600">Learn from experienced professionals in their fields.</p>
                </div>

                <div class="group text-center p-6 rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="bg-purple-100 w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-200 
                    transition-colors">
                        <span class="text-purple-600 text-2xl font-bold">💸</span>
                    </div>

                    <h3 class="font-bold text-gray-900 mb-1">Affordable Pricing</h3>
                    <p class="text-sm text-gray-600">Quality education that fits your budget.</p>
                </div>

            </div>
        </div>
    </section>

</div>

<?php if ($loggedIn && $userRole == "Buyer" && count($products) > 0):  ?>

<script>
    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(".wl-heart");
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const id = btn.dataset.productId;
        if (!id) return;

        const fd = new FormData();
        fd.append("product_id", id);

        try {
            const r = await fetch("process/watchlistProcess.php", {
                method: "POST",
                body: fd
            });

            const j = await r.json();
            if (j.success) {
                const inW = j.action == "added";
                btn.dataset.in = inW ? 1 : 0;
                btn.querySelector("svg").setAttribute("fill", inW ? "currentColor" : "none");
                btn.classList.toggle("text-red-500", inW);
                btn.classList.toggle("text-gray-400", !inW);
                btn.title = inW ? "Remove from watchlist" : "Add to watchlist";
            }

        } catch (_) {

        }
    })
</script>

<?php endif; ?>


<?php
require "footer.php";
?>