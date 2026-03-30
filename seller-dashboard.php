<?php include "header.php";

if (strtolower($userRole) != "seller") {
    header("Location: home.php");
    exit;
}

require_once "controllers/SellerController.php";
$controller = new SellerController($userId);
$dashboardData = $controller->getDashboardStats();

$sellerProducts = $dashboardData["products"];
$sellerOrders = $dashboardData["orders"];
$totalEarnings = $dashboardData["totalEarnings"];
$totalBuyers = $dashboardData["totalBuyers"];
$activeProducts = $dashboardData["activeProducts"];
$avgRating = $dashboardData["avgRating"];

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Pagination and sorting
$itemsPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Validation
$allowedSorts = ["newest", "price_low", "price_high", "rating", "customers"];
if (!in_array($sortBy, $allowedSorts)) $sortBy = "newest";

// Build sort query
$sortQuery = match ($sortBy) {
    "price_low" => "ORDER BY p. `price` ASC",
    "price_high" => "ORDER BY p. `price` DESC",
    "rating" => "ORDER BY AVG(COALESCE(f.`rating`, 0)) DESC",
    "customers" => "ORDER BY COUNT(o.`id`) DESC",
    default => "ORDER BY p.`created_at` DESC"
};

// Get total product count
$countResult = Database::search(
    "SELECT COUNT(p.`id`) AS `total` FROM `product` p WHERE p.`seller_id` = ?",
    "i",
    [$userId]
);
$totalProducts = ($countResult && $row = $countResult->fetch_assoc()) ? $row['total'] : 0;
$totalPages = ceil($totalProducts / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;


// Fetch product with sorting
$productsQuery = "
SELECT p.`id`, p.`title`, p.`description`, p.`image_url`, p.`price`, p.`level`, p.`status`, p.`created_at`, 
COUNT(DISTINCT o.`order_id`) AS `customer_count`,
AVG(COALESCE(f.`rating`, 0)) AS `avg_rating`
FROM `product` p
LEFT JOIN `order` o ON p.`id` = o.`product_id`
LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
WHERE p.`seller_id` =?
GROUP BY p.`id`
{$sortQuery} 
LIMIT ? OFFSET ?
";

$productResult = Database::search($productsQuery, "iii", [$userId, $itemsPerPage, $offset]);
$storeFrontProducts = [];

if ($productResult && $productResult->num_rows > 0) {
    while ($product = $productResult->fetch_assoc()) {
        $storeFrontProducts[] = $product;
    }
}

?>

<div class="min-h-screen bg-gray-50">

    <!-- Tab Navigation -->
    <div class="bg-white border-b sticky top-16 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-8">

            <a href="?tab=dashboard" class="py-4 font-medium border-b-2 
        <?php echo $tab == "dashboard" ? "border-blue-600 text-blue-600" :
            "border-transparent text-gray-600 hover:text-gray-900"; ?>">
                Dashboard</a>

            <a href="?tab=storefront" class="py-4 font-medium border-b-2 
        <?php echo $tab == "storefront" ? "border-blue-600 text-blue-600" :
            "border-transparent text-gray-600 hover:text-gray-900"; ?>">
                Public Store</a>

            <a href="?tab=messages" class="py-4 font-medium border-b-2 
        <?php echo $tab == "messages" ? "border-blue-600 text-blue-600" :
            "border-transparent text-gray-600 hover:text-gray-900"; ?>">
                Messages</a>
        </div>
    </div>

    <!-- Dashboard tab -->

    <?php if ($tab == "dashboard"): ?>
        <section class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">Dashboard</h2>
                    <p class="text-gray-600">Manage your skills and earnings</p>
                </div>
                <a href="product-register.php"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg">+ New Skill</a>
            </div>
        </section>

        <!-- Stats -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <p class="text-gray-600 text-sm">Total Earnings</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">
                        Rs. <?php echo number_format($totalEarnings, 2); ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo $totalBuyers; ?> Order received</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <p class="text-gray-600 text-sm">Total Buyers</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $totalBuyers; ?></p>
                    <p class="text-xs text-gray-500 mt-1">Active Buyers</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <p class="text-gray-600 text-sm">Active Skills</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2"><?php echo $activeProducts; ?></p>
                    <p class="text-xs text-gray-500 mt-1">Available for sale</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <p class="text-gray-600 text-sm">Average Ratings</p>
                    <p class="text-3xl font-bold text-yellow-500 mt-2"><?php echo round($avgRating, 1); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Customer reviews</p>
                </div>
            </div>
        </section>

        <!-- Your skills and Recent orders -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid md:grid-cols-3 gap-6">

                <!-- Skill list -->
                <div class="md:col-span-2">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Your Skills</h3>
                    <div class="space-y-3">
                        <?php foreach ($sellerProducts as $product): ?>
                            <div class="bg-white p-4 rounded-lg shadow hover:shadow-lg flex justify-between items-center">
                                <div>
                                    <p class="font-bold text-gray-900"><?php echo $product['title']; ?></p>
                                    <p class="text-xs text-gray-500">50 Customers | <?php echo $product['ratings']; ?>⭐</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="product-edit.php?id=<?php echo $product["id"]; ?>"
                                        class="px-3 py-1 text-blue-600 text-sm hover:bg-blue-50 rounded">Edit</a>
                                    <span class="px-3 py-1 <?Php echo $product["status"] == 'Active' ? "bg-green-100 text-green-800" :
                                                                "bg-red-100 text-red-800"; ?> text-xs rounded font-medium">
                                        <?php echo $product["status"]; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </section>

        <!-- Storefront -->
    <?php elseif ($tab == "storefront"): ?>
        <section class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">Your Storefront</h2>
                        <p class="text-gray-600">Manage and Customize how your skills appear to customers</p>
                    </div>
                    <a href="product-register.php"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg">+ Add Skill</a>
                </div>
            </div>
        </section>

        <!-- Sorting and filtering -->
        <section class="bg-white border-b sticky top-32 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex gap-2 items-center">
                        <label for="sortSelect" class="text-sm font-medium text-gray-700">Sort By:</label>
                        <select onchange="updateSort(this.value);" id="sortSelect"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 
                            focus:ring-blue-600 bg-white">
                            <option value="newest" <?php echo $sortBy == "newest" ? "selected" : ""; ?>>
                                Newest first
                            </option>
                            <option value="price_low" <?php echo $sortBy == "price_low" ? "selected" : ""; ?>>
                                Price Low to High
                            </option>
                            <option value="price_high" <?php echo $sortBy == "price_high" ? "selected" : ""; ?>>
                                Price High to Low
                            </option>
                            <option value="rating" <?php echo $sortBy == "rating" ? "selected" : ""; ?>>
                                Highest Rating
                            </option>
                            <option value="customers" <?php echo $sortBy == "customers" ? "selected" : ""; ?>>
                                Most Customers
                            </option>
                        </select>
                    </div>
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-bold"><?php echo count($storeFrontProducts); ?></span> of
                        <span class="font-bold"><?php echo $totalProducts; ?></span> Skills
                    </div>
                </div>
            </div>
        </section>

        <!-- Storefront view -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Products grid -->
            <?php if (count($storeFrontProducts) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">

                    <?php foreach ($storeFrontProducts as $product): ?>
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group">

                            <!-- product image -->
                            <div class="relative h-48 bg-gray-200 overflow-hidden">
                                <?php if ($product["image_url"]): ?>
                                    <img src="<?php echo $product["image_url"]; ?>"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform">

                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-300 flex items-center justify-center text-gray-500">No Image</div>

                                <?php endif; ?>


                                <!-- Status badge -->
                                <div class="absolute top-3 right-3">
                                    <span class="px-3 py-1 <?php echo $product["status"] == "Active" ?
                                                                "bg-green-100 text-green-800" :
                                                                "bg-red-100 text-red-800"; ?> text-xs font-bold rounded-full">
                                        <?php echo ucfirst($product["status"]); ?>
                                    </span>
                                </div>

                                <!-- Level badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                                        <?php echo $product["level"]; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Product info -->
                            <div class="p-4">
                                <h3 class="font-bold text-gray-900 text-lg line-clamp-2"><?php echo $product["title"]; ?></h3>
                                <p class="text-gray-600 text-sm mt-1 line-clamp-2">
                                    <?php echo substr($product["description"], 0, 50); ?>...
                                </p>

                                <!-- Rating and customer -->
                                <div class="flex justify-between items-center mt-3 text-sm text-gray-600">
                                    <span><?php echo intval($product["customer_count"]); ?> Customers</span>
                                    <span class="text-yellow-500 font-medium">⭐
                                        <?php echo $product["avg_rating"] > 0 ? round($product["avg_rating"], 1) : "N/A"; ?>
                                    </span>
                                </div>

                                <!-- price -->
                                <div class="text-2xl font-bold text-blue-600 mt-3">
                                    Rs. <?php echo number_format($product["price"], 2); ?>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2 mt-4">
                                    <a href="product-edit.php?id=<?php echo $product["id"]; ?>"
                                        class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 
                                        text-center">Edit
                                    </a>
                                    <button
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 
                            toggle-btn <?php echo $product["status"] == "Active" ?
                                            "bg-red-100 text-red-800 border-red-300" :
                                            "bg-green-100 text-green-800 border-green-300"; ?>"
                                        data-product-id="<?php echo $product["id"]; ?>"
                                        onclick="toggleProductStatus(<?php echo $product['id']; ?>);">
                                        <?php echo $product["status"] == "Active" ? "Deactivate" : "Activate"; ?>
                                    </button>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>

                    <div class="flex justify-center items-center gap-2 mt-8">

                        <?php if ($currentPage > 1): ?>

                            <a href="?tab=storefront&page&sort=<?php echo $sortBy; ?>&page=1"
                                class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                First
                            </a>
                            <a href="?tab=storefront&page&sort=<?php echo $sortBy; ?>&page=<?php echo $currentPage - 1; ?>"
                                class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                < Previous
                                    </a>

                                <?php endif; ?>

                                <?php
                                $start = max(1, $currentPage - 2);
                                $end = min($totalPages, $currentPage + 2);

                                for ($i = $start; $i <= $end; $i++): ?>
                                    <a href="?tab=storefront&sort=<?php echo $sortBy; ?>&page=<?php echo $i; ?>"
                                        class="px-3 py-2 border
                                        <?php echo $i == $currentPage ? " bg-blue-600 text-white border-blue-600"
                                            : " border-gray-300 hover:bg-gray-50"; ?> rounded-lg">
                                        <?php echo $i; ?>
                                    </a>

                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?tab=storefront&sort=<?php echo $sortBy; ?>&page=<?php echo $currentPage + 1; ?>"
                                        class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Next >
                                    </a>
                                    <a href="?tab=storefront&sort=<?php echo $sortBy; ?>&page=<?php echo $totalPages; ?>"
                                        class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Last
                                    </a>
                                <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>

                <div class="text-center py-16">
                    <div class="text-gray-400 text-6xl mb-4">📦</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No Skills yet</h3>
                    <p class="text-gray-600 mb-6">Start by creating your first skill to build your storefront.</p>
                    <a href="product-register.php"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 inline-block transition-all">
                        Create your first skill
                    </a>
                </div>

            <?php endif; ?>

        </section>

        <!-- Messages Tab -->
    <?php elseif ($tab == "messages"): ?>

        <section class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h2 class="text-3xl font-bold text-gray-900">Messages</h2>
                <p class="text-gray-600">Communicate with your buyers</p>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid md:grid-cols-3 gap-6 h-[600px] border border-gray-100 rounded-3xl overflow-hidden shadow-sm bg-white">

                <!-- Conversation list -->
                <div class="flex flex-col border-r border-gray-100 h-[600px] overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex-shrink-0">
                        <input type="text" id="chatSearch" onkeyup="filterChats()" placeholder="Search...."
                            class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl text-sm focus:ring-4 focus:ring-blue-50/50 outline-none 
                        transition-all">
                    </div>
                    <div id="chatList" class="overflow-y-auto flex-1 divide-y divide-gray-50 min-h-0">
                        <!-- Loaded with JS -->
                        <div class="p-8 text-center text-gray-400">Loading Chats....</div>
                    </div>
                </div>

                <!-- Chat area -->
                <div class="md:col-span-2 flex flex-col bg-gray-50/30 h-[600px] overflow-hidden">
                    <div id="chatHeader" class="p-6 border-b border-gray-100 bg-white flex justify-between items-center hidden flex-shrink-0">
                        <div>
                            <p id="chatWith" class="font-extrabold text-gray-900"></p>
                            <p class="text-xs text-blue-600 font-bold uppercase tracking-wider">Buyer Chat</p>
                        </div>
                    </div>
                    <div id="messageArea" class="overflow-y-auto flex-1 p-6 space-y-4 min-h-0">
                        <div class="flex-1 flex flex-col items-center justify-center text-center p-12 opacity-50">
                            <div class="text-5xl mb-4">💬</div>
                            <h3 class="font-bold text-gray-900">Select a Buyer</h3>
                            <p class="text-sm text-gray-500 mt-1">Choose a conversation to reply to your customers</p>
                        </div>
                    </div>
                    <div id="chatInputArea" class="p-6 border-t border-gray-100 bg-white hidden flex-shrink-0">
                        <form id="msgForm" onsubmit="sendMessage(event);" class="flex gap-4">
                            <input type="hidden" id="activeToId">
                            <input type="text" id="msgContent" required placeholder="Type your message..."
                                class="flex-1 px-5 py-3 bg-gray-50 border-none rounded-2xl text-sm focus:ring-4 focus:ring-blue-50/50 outline-none
                            transition-all">
                            <button type="submit"
                                class="px-8 py-2 bg-gray-900 text-white rounded-2xl font-bold hover:bg-black transition-all shadow-lg 
                            active:scale-95">
                                Send
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </section>

        <!-- JS for Messages -->
        <script>
            var activeOtherId = null;

            async function loadChatList() {
                const res = await fetch("process/getChatList.php");
                const chats = await res.json();
                const list = document.getElementById("chatList");
                list.innerHTML = chats.length ? '' : '<div class="p-8 text-center text-gray-400">No Conversations Found</div>';

                chats.forEach((chat) => {
                    const div = document.createElement("div");
                    div.className = `p-4 cursor-pointer hover:bg-gray-50 transition-all border-l-4 ${activeOtherId === chat.id ?
         'bg-blue-50/50 border-blue-600' : 'border-transparent'}`;
                    div.onclick = () => selectChat(chat.id, chat.name);

                    const unreadTrack = chat.unread_count > 0 ? `<span class="bg-blue-600 rounded-full px-1.5 py-0.5 font-bold 
        text-white text-[10px]">${chat.unread_count}</span>` : '';

                    div.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-bold text-gray-900 text-sm">${chat.name}</p>
                    <p class="text-xs text-gray-500 truncate mt-1 max-w-[150px] font-medium">${chat.last_message
                    || `Start-chatting....`}</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">${chat.time ? new Date(chat.time).
                    toLocaleString([], { hour: '2-digit', minute: '2-digit'}) : ''}</span>
                </div>
                </div>
                `;
                    list.appendChild(div);
                });
            }

            async function selectChat(id, name) {
                activeOtherId = id;
                document.getElementById("activeToId").value = id;
                document.getElementById("chatWith").innerHTML = name;
                document.getElementById("chatHeader").classList.remove("hidden");
                document.getElementById("chatInputArea").classList.remove("hidden");
                loadMessages();
                loadChatList();

                if (window.chatInterval) clearInterval(window.chatInterval);
                window.chatInterval = setInterval(loadMessages, 3000);
            }

            async function loadMessages() {
                if (!activeOtherId) return;

                const res = await fetch(`process/loadMessages.php?other_id=${activeOtherId}`);
                const msgs = await res.json();
                const area = document.getElementById("messageArea");

                var html = '';

                msgs.forEach(m => {
                    const side = m.side == 'right' ? 'justify-end' : 'justify-start';
                    const color = m.side == 'right' ? 'bg-gray-900 text-white rounded-tr-none' : 'bg-white border border-gray-100 text-gray-800 rounded-tl-none';

                    var seenHtml = '';
                    if (m.side == 'right') {
                        if (m.status == 'Seen') {
                            seenHtml = '<span class="ml-2 text-blue-400 font-bold">✓✓</span>';
                        } else {
                            seenHtml = '<span class="ml-2 text-gray-400 font-bold">✓</span>';
                        }
                    }

                    html += `
                <div class="flex ${side}">
                    <div class="${color} px-5 py-3 rounded-2xl max-w-[85%] shadow-sm relative group">
                        <p class="text-sm leading-relaxed">${m.content}</p>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-[10px] opacity-50 font-bold">${new Date(m.time).toLocaleString([], 
                            { hour: '2-digit', minute: '2-digit'})}</p>
                            ${seenHtml}
                        </div>
                    </div>
                </div>
                `;

                    // Only scroll if content changed
                    if (area.innerHTML != html) {
                        area.innerHTML = html;
                        area.scrollTop = area.scrollHeight;
                    }
                })
            }

            async function sendMessage(e) {
                e.preventDefault();
                const content = document.getElementById('msgContent').value;
                const toId = document.getElementById('activeToId').value;
                if (!content.trim()) return;

                const fd = new FormData();
                fd.append('to_id', toId);
                fd.append('content', content);

                const res = await fetch('process/sendMessage.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('msgContent').value = '';
                    loadMessages();
                    loadChatList();
                } else {
                    alert(data.message);
                }
            }

            function filterChats() {
                const q = document.getElementById('chatSearch').value.toLowerCase();
                const items = document.querySelectorAll('#chatList > div');
                items.forEach(item => {
                    const name = item.querySelector('.font-bold').innerText.toLowerCase();
                    item.style.display = name.includes(q) ? 'block' : 'none';
                });
            }

            loadChatList().then(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const otherId = urlParams.get('other_id');
                const otherName = urlParams.get('other_name');
                if (otherId && otherName) {
                    selectChat(otherId, otherName);
                }
            });
        </script>

    <?php endif; ?>

</div>

<script>
    function updateSort(sortValue) {
        const sortBy = sortValue;
        window.location.href = `?tab=storefront&sort=${sortBy}&page=1`;
    }

    function toggleProductStatus(productId) {
        const button = document.querySelector(`[data-product-id="${productId}"]`);
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = "Processing...";

        const formData = new FormData();
        formData.append("productId", productId);

        fetch("process/productStatusProcess.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {

                    const newStatus = data.newStatus;

                    if (newStatus == "Block") {
                        button.classList.remove("bg-red-100", "text-red-800", "border-red-300");
                        button.classList.add("bg-green-100", "text-green-800", "border-green-300");
                        button.textContent = "Activate";
                    } else {
                        button.classList.remove("bg-green-100", "text-green-800", "border-green-300");
                        button.classList.add("bg-red-100", "text-red-800", "border-red-300");
                        button.textContent = "Deactivate";
                    }

                    // Change status badge
                    const statusBadge = button.closest(".bg-white").querySelector("span.px-3.py-1");
                    if (statusBadge) {
                        statusBadge.classList.remove("bg-green-100", "text-green-800",
                            "bg-red-100", "text-red-800");
                        if (newStatus == "Active") {
                            statusBadge.classList.add("bg-green-100", "text-green-800");
                            statusBadge.textContent = "Active";
                        } else {
                            statusBadge.classList.add("bg-red-100", "text-red-800");
                            statusBadge.textContent = "Block";
                        }
                    }

                    // Show success message
                    const message = document.createElement("div");
                    message.className = "fixed top-24 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50";
                    message.textContent = `Product ${newStatus == "Active" ? "activated" : "deactivated"} successfully`;
                    document.body.appendChild(message);

                    setTimeout(() => {
                        message.remove();
                    }, 3000);

                } else {
                    alert("Error: " + data.message);
                    button.textContent = originalText;
                }

                button.disabled = false;

            })
            .catch(error => {
                console.error("Error:", error);
                alert("An Error occurred while updating the product status.");
                button.textContent = originalText;
                button.disabled = false;

            });

    }
</script>

<?php include "footer.php"; ?>