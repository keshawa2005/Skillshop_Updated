<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once "db/connection.php";

// Check if user is logged in, if not, check for remember me token
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    require_once 'process/auth-check.php'; // Adjust the path as needed
}

// Initialize variables to avoid undefined index errors
$loggedIn = isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$userRole = isset($_SESSION['active_account_type']) ? $_SESSION['active_account_type'] : '';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "";

// Cart Count
$cartCount = 0;
if ($loggedIn && $userRole == "Buyer") {
    $ccQ = Database::search("SELECT COUNT(*) AS `c` FROM `cart` WHERE `user_id` = ?", "i", [$userId]);
    $cartCount = $ccQ ? $ccQ->fetch_assoc()['c'] : 0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Shop | Buy and Sell Skills</title>
    <link rel="icon" href="Images/icon.png" type="image/png" />
    <link rel="stylesheet" href="CSS/style.css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white">

    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center gap-6">

            <!-- Logo -->
            <a href="home.php" class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text
text-transparent hover:opacity-80 transition-opacity flex-shrink-6">SkillShop</a>

            <!-- Search Bar -->
            <div class="hidden md:flex flex-1 max-w-lg items-center gap-2">
                <form action="search-products.php" method="GET" class="relative flex-1">
                    <input
                        type="text"
                        placeholder="Search for skills & instructors..."
                        class="w-full py-2.5 px-4 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 
        focus:ring-blue-500 focus:border-transparent text-sm text-gray-900 placeholder-gray-500 transition-all">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 transition-colors">
                        🔍
                    </button>
                </form>

                <a href="advance-search-products.php"
                    class="px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                    Advanced Search
                </a>
            </div>

            <!-- Right Side Navigation -->
            <div class="flex gap-1 sm:gap-2 items-center">
                <?php if ($loggedIn): ?>

                    <!-- Notifications -->
                    <div class="relative group hidden sm:block">
                        <button
                            class="relative p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                            🔔
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                        </button>

                        <div class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-lg border border-gray-100 opacity-0 
                            invisible transition-all duration-200 group-hover:opacity-100 group-hover:visible">
                            <div class="p-4 border-b border-gray-100">
                                <p class="font-semibold text-sm text-gray-900">Notifications</p>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <div class="p-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer transition-colors">
                                    <p class="text-xs font-medium text-gray-900">💬 New message from John Doe</p>
                                    <p class="text-xs text-gray-500 mt-1">Just Now</p>
                                </div>
                                <div class="p-3 hover:bg-gray-50 cursor-pointer transition-colors">
                                    <p class="text-xs font-medium text-gray-900">✅ Order completed</p>
                                    <p class="text-xs text-gray-500 mt-1">2 hours ago</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Messages -->

                    <?php
                    $msgCount = 0;
                    if ($loggedIn) {
                        $mcQ = Database::search(
                            "SELECT COUNT(*) AS `c` FROM `chat` WHERE `to_user_id` = ? AND `status` = 'unseen'",
                            "i",
                            [$userId]
                        );
                        $msgCount = $mcQ ? $mcQ->fetch_assoc()['c'] : 0;
                    }
                    ?>

                    <a href="<?php echo $userRole == "Buyer" ?
                                    "buyer-dashboard.php?tab-messages" :
                                    "seller-dashboard.php?tab-messages"; ?>"
                        class="relative p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors 
                        duration-200 hidden sm:block">
                        ✉️

                        <?php if ($msgCount > 0): ?>
                            <span id="msgCount" class="absolute top-1 right-1 text-[10px] bg-blue-600 text-white w-5 h-5 rounded-full 
                            flex items-center justify-center font-bold"><?= $msgCount ?></span>

                        <?php else: ?>
                            <span id="msgCount" class="absolute top-1 right-1 text-[10px] bg-blue-600 text-white w-5 h-5 rounded-full 
                            flex items-center justify-center font-bold">0</span>

                        <?php endif; ?>
                        <!-- <span class="absolute top-1 right-1 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span> -->
                    </a>

                    <!-- Cart/Watchlist for Buyers -->
                    <?php if ($userRole == "Buyer"): ?>
                        <a href="buyer-dashboard.php?tab=cart"
                            class="relative p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors 
                            duration-200 hidden sm:block">
                            🛒

                            <?php if ($cartCount > 0): ?>
                                <span id="cart-count"
                                    class="absolute top-1 right-1 text-xs bg-blue-600 text-white w-5 h-5 rounded-full flex items-center 
                            justify-center font-bold"><?= $cartCount; ?></span>

                            <?php else: ?>
                                <span id="cart-count"
                                    class="absolute top-1 right-1 text-xs bg-blue-600 text-white w-5 h-5 rounded-full flex items-center 
                            justify-center font-bold">0</span>

                            <?php endif; ?>

                        </a>
                        <a href="watchlist.php"
                            class="p-2 text-gray-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors 
                        duration-200 hidden sm:block" title="Watchlist">
                            ❤️
                        </a>

                    <?php endif; ?>

                    <!-- Profile Dropdown -->
                    <div class="relative ml-2 sm:ml-4 pl-2 sm:pl-2 border-1 border-gray-200">
                        <button
                            id="profileBtn"
                            class="flex items-center gap-2 hover:bg-gray-50 rounded-lg transition-colors duration-200 px-2 py-1">
                            <img src="Images/avatar.png"
                                class="w-8 h-8 rounded-full ring-2 ring-transparent hover:ring-blue-300 transition-all">
                            <div class="hidden sm:block">
                                <p class="text-sm font-semibold text-gray-900"><?php echo $userName ?></p>
                                <p class="text-xs text-gray-500 loading-none"><?php echo $userRole ?></p>
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="profileDropdown"
                            class="absolute right-0 mt-3 w-56 bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 
                            invisible transition-all duration-200 z-50" style="display: none;">

                            <div class="p-3 border-b border-gray-50">
                                <p class="font-semibold text-sm text-gray-900"><?php echo $userName ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo $userRole ?></p>
                            </div>

                            <div class="py-2">
                                <a href="<?php echo $userRole == "Buyer" ?
                                                "buyer-dashboard.php" :
                                                "seller-dashboard.php"; ?>"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 
                                transition-colors duration-150">
                                    📊 Dashboard
                                </a>
                                <?php if ($userRole == "Buyer"): ?>
                                    <a href="buyer-dashboard.php?tab=purchase-history"
                                        class="block px-4 py-2.5 text-sm text-gray-700
                                hover:bg-blue-50 hover:text-blue-600 transition-colors duration-150" title="purchase-history">
                                        📜 Purchase History
                                    </a>
                                    <a href="watchlist.php"
                                        class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 
                                transition-colors duration-150">
                                        ❤️ Watchlist
                                    </a>
                                <?php endif; ?>
                                <a href="user-profile.php"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 
                                transition-colors duration-150">
                                    👤 Profile
                                </a>
                                <a href="process/logoutProcess.php"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 
                                transition-colors duration-150">
                                    🚪 Sign out
                                </a>
                            </div>
                        </div>


                    <?php else: ?>
                        <a href="index.php"
                            class="text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 font-medium text-sm transition-colors">
                            Sign in</a>
                        <a href="index.php"
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-2 rounded-lg hover:shadow-lg 
            hover:from-blue-700 hover:to-indigo-700 font-medium transition-all duration-200 text-sm">
                            Join </a>

                    <?php endif; ?>
                    </div>

            </div>
    </nav>

    <script>
        // Toggle profile dropdown visibility
        var profileBtn = document.getElementById('profileBtn');
        var profileDropdown = document.getElementById('profileDropdown');

        if (profileBtn && profileDropdown) {
            // Toggle dropdown on button click
            profileBtn.addEventListener('click', function(e) {
                var isHidden = profileDropdown.style.display == "none";
                profileDropdown.style.display = isHidden ? "block" : "none";
                profileDropdown.classList.toggle('opacity-0');
                profileDropdown.classList.toggle('invisible');
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                    profileDropdown.style.display = "none";
                    profileDropdown.classList.add('opacity-0');
                    profileDropdown.classList.add('invisible');
                }
            });

            // Close the dropdown when clicking links inside it
            profileDropdown.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    profileDropdown.style.display = "none";
                    profileDropdown.classList.add('opacity-0');
                    profileDropdown.classList.add('invisible');
                });
            });

        }

        // Live message count update
        if (<?php echo $loggedIn ? "true" : "false"; ?>) {

            setInterval(async () => {
                try {

                    const res = await fetch("process/getUnseenMessageCount.php");
                    const data = await res.json();
                    const mc = document.getElementById("msg-count");

                    if (mc) {
                        mc.innerText = data.count;
                        if (data.count > 0) {
                            mc.classList.remove("hidden");
                            mc.classList.add("flex");
                        } else {
                            mc.classList.remove("flex");
                            mc.classList.add("hidden");
                        }
                    }

                } catch (e) {

                }
            }, 5000);

        }
    </script>