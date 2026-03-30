<?php
include("header.php");

// Fetch featured products with seller name, review count, and average rating
$productResult = Database::search(
    "SELECT 
    p.`id`, 
    p.`title`,
    p.`description`,
    p.`price`,
    p.`image_url`,
    p.`status`,
    u.`fname`,
    COUNT(f.`id`) AS `review_count`,
    COALESCE(AVG(f.`rating`), 0) AS `avg_rating`
    FROM `product` p 
    JOIN `user` u ON p.`seller_id` = u.`id`
    LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
    WHERE p.`status` = ?
    GROUP BY p.`id`, u.`fname`
    ORDER BY p.`id` DESC
    LIMIT 6",
    "s",
    ["Active"]
);

// Store products in an array for easier access in the HTML
$products = [];
if ($productResult && $productResult->num_rows > 0) {
    while ($product = $productResult->fetch_assoc()) {
        $products[] = $product;
    }
}

// Get featured testimonials with user name and rating
$testimonialResult = Database::search(
    "SELECT 
    f.`rating`,
    f.`message`,
    u.`fname`,
    u.`lname`,
    u.`id`,
    up.`avatar_url`
    FROM `feedback` f 
    JOIN `user` u ON f.`user_id` = u.`id` 
    LEFT JOIN `user_profile` up ON u.`id` = up.`user_id`
    WHERE f.`is_featured` = 1 
    LIMIT 3"
);

// Store testimonials in an array for easier access in the HTML
$testimonials = [];
if ($testimonialResult && $testimonialResult->num_rows > 0) {
    while ($testimonial = $testimonialResult->fetch_assoc()) {
        $testimonials[] = $testimonial;
    }
}

// Fetch stats for users, products, feedback, and orders
$usersResult = Database::search("SELECT COUNT(DISTINCT `id`) AS `total_users` FROM `user`");
$productsCountResult = Database::search("SELECT COUNT(`id`) AS `total_products` FROM `product`");
$feedbackStatusResult = Database::search("SELECT AVG(`rating`) AS `avg_rating` FROM `feedback`");
$ordersStatusResult = Database::search("SELECT COUNT(`order_id`) AS `total_orders`, SUM(`total_amount`) AS `total_revenue` FROM `order`");

// Extract stats values with fallback to 0 if query fails
$totalUsers = ($usersResult && $row = $usersResult->fetch_assoc()) ? $row['total_users'] : 0;
$totalProducts = ($productsCountResult && $row = $productsCountResult->fetch_assoc()) ? $row['total_products'] : 0;
$avgRating = ($feedbackStatusResult && $row = $feedbackStatusResult->fetch_assoc()) ?
    round(($row['avg_rating'] / 5) * 100, 0)
    : 0;

$totalOrders = ($ordersStatusResult && $row = $ordersStatusResult->fetch_assoc()) ? $row['total_orders'] : 0;
$totalRevenue = ($ordersStatusResult && $row = $ordersStatusResult->fetch_assoc()) ?
    intval($row['total_revenue'] / 100000) : 0;

?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-8 items-center">

            <!-- Div 1 -->
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Learn New Skills, Sell Your Expertise</h1>
                <p class="text-blue-100 text-lg mb-6">Connect with expert skill sellers and buyers in our thriving community</p>

                <!-- Search Bar -->
                <form action="search-products.php" method="GET" class="relative w-full">
                    <div class="mb-6 bg-white rounded-lg p-3 flex gap-2">
                        <input type="text"
                            placeholder="What skills are you looking for?..."
                            class="flex-1 bg-transparent text-gray-900 outline-none text-sm">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium text-sm transition-colors">
                            Search
                        </button>
                    </div>
                </form>

                <div class="flex gap-4 flex-col md:flex-row">
                    <?php if (isset($_SESSION['user_email'])): ?>

                        <?php if ($userRole == "Buyer"): ?>
                            <a href="buyer-dashboard.php"
                                class="bg-white text-blue-600 px-6 py-3 rounded-lg font-bold hover:shadow-lg inline-block transition-all">
                                Go to Dashboard</a>
                            <a href="#browse"
                                class="border-2 border-white text-white px-6 py-3 rounded-lg font-bold hover:bg-white hover:bg-opacity-10 
                    inline-block transition-all">
                                Browse Skills
                            </a>
                        <?php endif; ?>

                        <?php if ($userRole == "Seller"): ?>
                            <a href="seller-dashboard.php"
                                class="bg-white text-blue-600 px-6 py-3 rounded-lg font-bold hover:shadow-lg inline-block transition-all">
                                Go to Dashboard</a>
                            <a href="product-register.php"
                                class="border-2 border-white text-white px-6 py-3 rounded-lg font-bold hover:bg-white hover:bg-opacity-10 
                    inline-block transition-all">
                                Create Skills
                            </a>
                        <?php endif; ?>

                        <!-- New User -->
                    <?php else: ?>
                        <a href="index.php"
                            class="bg-white text-blue-600 px-6 py-3 rounded-lg font-bold hover:shadow-lg inline-block transition-all">
                            Get Started</a>
                        <a href="#"
                            class="border-2 border-white text-white px-6 py-3 rounded-lg font-bold hover:bg-white hover:bg-opacity-10 
                    inline-block transition-all">
                            Learn More
                        </a>

                    <?php endif; ?>

                </div>

            </div>

            <!-- Div 2 -->
            <div class="hidden md:block">
                <div class="bg-white bg-opacity-10 rounded-xl p-8 backdrop-blur-sm">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 text-blue-100">
                            <span class="text-2xl">✨</span>
                            <p>Expert Instructors ready to teach</p>
                        </div>
                        <div class="flex items-center gap-3 text-blue-100">
                            <span class="text-2xl">🚀</span>
                            <p>Grow your skills or business</p>
                        </div>
                        <div class="flex items-center gap-3 text-blue-100">
                            <span class="text-2xl">💰</span>
                            <p>Secure Transactions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Featured Carousel -->
<section id="browse" class="py-16 md:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Featured Skills</h2>
        <p class="text-gray-600">Explore our top-rated skills handpicked for you</p>
    </div>

    <!-- Carousel Container -->
    <div class="relative group">
        <div class="overflow-hidden rounded-xl">
            <div id="carousel" class="flex transition-transform duration-500 ease-out" style="transform:translateX(0);">

                <!-- Carousel Items -->

                <?php if (count($products) > 0) : ?>
                    <?php foreach ($products as $product) : ?>
                        <div class="carousel-item flex-shrink-0 w-full md:w-1/3 px-3 pb-4">
                            <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all h-full">
                                <img src="<?php echo $product['image_url']; ?>" class="w-full h-40 object-cover">
                                <div class="p-5">
                                    <h3 class="text-lg font-bold mb-2"><?php echo $product['title']; ?></h3>
                                    <p class="text-gray-600 text-sm mb-4"><?php echo $product['description']; ?></p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-blue-600 font-bold">Rs. <?php echo $product['price']; ?></span>
                                        <span class="text-yellow-400">⭐ <?php echo round($product['avg_rating'], 1);
                                                                        ?> (<?php echo $product['review_count']; ?>)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <!-- No Products Found message -->
                    <div class="carousel-item flex-shrink-0 w-full md:w-1/3 px-3 pb-4">
                        <div class="bg-white rounded-xl overflow-hidden shadow-lg p-8 text-center h-full flex items-center">
                            <p class="text-gray-500">No products available at the moment. Check back later!</p>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>

        <!-- Navigation Buttons -->
        <button onclick="slideCarousel(-1);"
            class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-5 bg-blue-600 text-white p-3 rounded-full hover:bg-blue-700 
         transition-all hover:scale-110 z-10 opacity-0 group-hover:opacity-100">«</button>
        <button onclick="slideCarousel(1);"
            class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-5 bg-blue-600 text-white p-3 rounded-full hover:bg-blue-700 
         transition-all hover:scale-110 z-10 opacity-0 group-hover:opacity-100">»</button>

    </div>

    <!-- Carousel Indicators -->
    <div id="carousel-indicators" class="flex justify-center mt-8 gap-2">
        <button onclick="goToSlide(0);" class="carousel-dot w-3 h-3 bg-blue-600 rounded-full transition-all"></button>
        <button onclick="goToSlide(1);" class="carousel-dot w-3 h-3 bg-gray-600 rounded-full transition-all"></button>
    </div>
</section>

<!-- Catergories Section -->
<section id="categories" class="bg-gray-50 py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-12">Explore Categories</h2>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <a href="#"
                class="group bg-white p-6 rounded-lg text-center shadow hover:shadow-lg transition-all cursor-pointer hover:scale-105
            hover:border-2 hover:border-blue-600">
                <div class="text-5xl mb-3 group-hover:scale-110 transition-transform">🎨</div>
                <p class="font-semibold text-gray-800 group-hover:text-blue-600">Design</p>
            </a>

            <a href="#"
                class="group bg-white p-6 rounded-lg text-center shadow hover:shadow-lg transition-all cursor-pointer hover:scale-105
            hover:border-2 hover:border-blue-600">
                <div class="text-5xl mb-3 group-hover:scale-110 transition-transform">💻</div>
                <p class="font-semibold text-gray-800 group-hover:text-blue-600">Development</p>
            </a>

            <a href="#"
                class="group bg-white p-6 rounded-lg text-center shadow hover:shadow-lg transition-all cursor-pointer hover:scale-105
            hover:border-2 hover:border-blue-600">
                <div class="text-5xl mb-3 group-hover:scale-110 transition-transform">📱</div>
                <p class="font-semibold text-gray-800 group-hover:text-blue-600">Mobile</p>
            </a>

            <a href="#"
                class="group bg-white p-6 rounded-lg text-center shadow hover:shadow-lg transition-all cursor-pointer hover:scale-105
            hover:border-2 hover:border-blue-600">
                <div class="text-5xl mb-3 group-hover:scale-110 transition-transform">📚</div>
                <p class="font-semibold text-gray-800 group-hover:text-blue-600">Education</p>
            </a>

            <a href="#"
                class="group bg-white p-6 rounded-lg text-center shadow hover:shadow-lg transition-all cursor-pointer hover:scale-105
            hover:border-2 hover:border-blue-600">
                <div class="text-5xl mb-3 group-hover:scale-110 transition-transform">🎥</div>
                <p class="font-semibold text-gray-800 group-hover:text-blue-600">Video</p>
            </a>

            <a href="#"
                class="group bg-white p-6 rounded-lg text-center shadow hover:shadow-lg transition-all cursor-pointer hover:scale-105
            hover:border-2 hover:border-blue-600">
                <div class="text-5xl mb-3 group-hover:scale-110 transition-transform">💼</div>
                <p class="font-semibold text-gray-800 group-hover:text-blue-600">Business</p>
            </a>

        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section id="testimonials" class="py-16 md:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-12">What Our Users Say</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <?php if (count($testimonials) > 0): ?>
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-2xl transition-shadow hover:scale-105">
                    <div class="flex items-center mb-4">
                        <img src="<?php echo $testimonial['avatar_url']; ?>" class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo $testimonial['fname'] . ' ' . $testimonial['lname']; ?></p>
                            <p class="text-sm text-yellow-400">
                                <?php
                                $stars = '';
                                for ($i = 0; $i < $testimonial['rating']; $i++) {
                                    $stars .= '⭐';
                                }
                                echo $stars;
                                ?></p>
                        </div>
                    </div>
                    <p class="text-gray-600"><?php echo '"' . $testimonial['message'] . '"'; ?></p>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg p-8 text-center col-span-3">
                <p class="text-gray-500">No testimonials yet. Be the first to share your experience!</p>
            </div>

        <?php endif; ?>

    </div>
</section>

<!-- Stats Section -->
<section id="stats" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">

            <div class="hover:scale-110 transition-transform duration-300 cursor-pointer">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?php echo number_format($totalUsers); ?>+</div>
                <p class="text-sm mt-2">Active Users</p>
            </div>
            <div class="hover:scale-110 transition-transform duration-300 cursor-pointer">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?php echo number_format($totalProducts); ?>+</div>
                <p class="text-sm mt-2">Skills Listed</p>
            </div>
            <div class="hover:scale-110 transition-transform duration-300 cursor-pointer">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?php echo $avgRating; ?>%</div>
                <p class="text-sm mt-2">Satisfaction Rate</p>
            </div>
            <div class="hover:scale-110 transition-transform duration-300 cursor-pointer">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?php echo number_format($totalRevenue); ?>M+</div>
                <p class="text-sm mt-2">Transaction Value</p>
            </div>
        </div>
    </div>
</section>

<!-- How it Works Section -->
<section id="how-it-works" class="py-16 md:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-12">How It Works</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        <div class="text-center group">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full w-16 h-16 flex items-center justify-center
         text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">1</div>
            <h3 class="text-lg font-bold mb-2 text-gray-900">Create an Account</h3>
            <p class="text-gray-600 text-sm">Sign up for a free account to access all features and benefits.</p>
        </div>

        <div class="text-center group">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full w-16 h-16 flex items-center justify-center
         text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">2</div>
            <h3 class="text-lg font-bold mb-2 text-gray-900">Browse or List Skills</h3>
            <p class="text-gray-600 text-sm">Explore our wide range of skills and find the perfect one for you.</p>
        </div>

        <div class="text-center group">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full w-16 h-16 flex items-center justify-center
         text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">3</div>
            <h3 class="text-lg font-bold mb-2 text-gray-900">Connect & Learn</h3>
            <p class="text-gray-600 text-sm">Connect with instructors and learners to enhance your skills and knowledge.</p>
        </div>

        <div class="text-center group">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full w-16 h-16 flex items-center justify-center
         text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">4</div>
            <h3 class="text-lg font-bold mb-2 text-gray-900">Grow & Earn</h3>
            <p class="text-gray-600 text-sm">Earn money by teaching skills you're passionate about.</p>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-16 md:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl p-8 md:p-16 text-center shadow-2xl">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Start Your Journey?</h2>
        <p class="text-blue-100 text-lg mb-8">Join thousands of learners and instructors on SkillShop today.</p>
        <div class="flex flex-col md:flex-row gap-4 justify-center">
            <?php if (isset($_SESSION["user_email"])): ?>

                <?php if ($userRole == "Buyer"): ?>
                    <a href="buyer-dashboard.php"
                        class="bg-white text-blue-600 rounded-lg font-bold hover:shadow-2xl px-8 py-3 inline-block transition-all 
                    hover:scale-105">Go to Dashboard</a>
                    <a href="#browse" class="border-2 border-white text-white px-8 py-3 rounded-lg font-bold inline-block
                    hover:bg-white hover:bg-opacity-20 transition-all">Explore More</a>

                <?php endif; ?>

                <?php if ($userRole == "Seller"): ?>
                    <a href="seller-dashboard.php"
                        class="bg-white text-blue-600 rounded-lg font-bold hover:shadow-2xl px-8 py-3 inline-block transition-all 
                transition-all hover:scale-105">Go to Dashboard</a>
                    <a href="#" class="border-2 border-white text-white px-8 py-3 rounded-lg font-bold inline-block
                    hover:bg-white hover:bg-opacity-20 transition-all">Create New Skills</a>

                <?php endif; ?>

            <?php else: ?>
                <a href="index.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-bold hover:shadow-2xl inline-block
                transition-all hover:scale-105">Sign Up Now</a>
                <a href="#browse" class="border-2 border-white text-white px-8 py-3 rounded-lg font-bold inline-block
                    hover:bg-white hover:bg-opacity-20 transition-all">Learn More</a>


            <?php endif; ?>

        </div>
    </div>
</section>

<script>
    // Carousel Logic
    var currentSlide = 0;
    var itemsPerView = window.innerWidth >= 768 ? 3 : 1;
    var totalItems = <?php echo count($products); ?>;
    var maxSlide = Math.ceil(totalItems / itemsPerView) - 1;

    function slideCarousel(direction) {
        currentSlide += direction;
        if (currentSlide > maxSlide) {
            currentSlide = 0;
        } else if (currentSlide < 0) {
            currentSlide = maxSlide;
        }
        updateCarousel();
    }

    function goToSlide(slide) {
        currentSlide = slide;
        updateCarousel();
    }

    function updateCarousel() {
        var carousel = document.getElementById("carousel");
        var slideWidth = 100; // 100 / itemsPerView; // Each slide takes up 100% of the view width divided by items per view
        var offset = -currentSlide * slideWidth;
        // carousel.style.transform = "translateX(${offset}%)";
        carousel.style.transform = "translateX(" + offset + "%)";

        // Update dots
        var dotsContainer = document.getElementById("carousel-indicators");
        var dots = dotsContainer.querySelectorAll(".carousel-dot");

        dots.forEach(function(dot, index) {
            if (index === currentSlide) {
                dot.classList.add("bg-blue-600");
                dot.classList.remove("bg-gray-600");
            } else {
                dot.classList.add("bg-gray-600");
                dot.classList.remove("bg-blue-600");
            }
        });
    }

    // Auto slide carousel every 5 seconds
    setInterval(function() {
        slideCarousel(1);
    }, 5000);

    // Handle responsive changes
    window.addEventListener("resize", () => {
        updateCarousel();
    });

    // Initialize dots onload
    window.addEventListener("load", () => {
        var dotsContainer = document.getElementById("carousel-indicators");
        dotsContainer.innerHTML = "";
        for (let i = 0; i <= maxSlide; i++) {
            var dot = document.createElement("button");
            dot.onclick = () => goToSlide(i);
            // dot.className = "carousel-dot w-3 h-3 rounded-full transition-all " ${i === 0 ? "bg-blue-600" : "bg-gray-600"};
            dot.className = "carousel-dot w-3 h-3 rounded-full transition-all " + (i === 0 ? "bg-blue-600" : "bg-gray-600");
            dotsContainer.appendChild(dot);
        }
    });
</script>

<?php include "footer.php"; ?>