<?php include "header.php";

if (strtolower($userRole) != "buyer") {
    header("Location: home.php");
    exit;
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Cart data
$cartItems = [];
$subtotal = 0;
$totalDeliveryFee = 0;

if ($tab == "cart") {

    // Delivery fee calculation (buyer, city_id)
    $buyerCityQ = Database::search(
        "SELECT a.`city_id` FROM `user_profile` up
    JOIN `address` a ON up.`address_id` = a.`id`
    WHERE up.`user_id` = ?",
        "i",
        [$userId]
    );
    $buyerCityId = ($buyerCityQ && $buyerCityQ->num_rows > 0) ? $buyerCityQ->fetch_assoc()["city_id"] : 0;

    // Fetch cart items
    $cartItemsQ = Database::search(
        "SELECT c.`id` AS `cart_item_id`, p.*, u.`fname` AS `seller_fname`, u.`lname` AS `seller_lname`, 
        sa.`city_id` AS `seller_city_id`,
        sa.`id` AS `seller_id` 
    FROM `cart` c
    JOIN `product` p ON c.`product_id` = p.`id`
    JOIN `user` u ON p.`seller_id` = u.`id`
    LEFT JOIN `user_profile` up ON u.`id` = up.`user_id`
    LEFT JOIN `address` sa ON up.`address_id` = sa.`id`
    WHERE c.`user_id` = ?
    ORDER BY c.`created_at` DESC",
        "i",
        [$userId]
    );

    $sellersInCart = [];

    while ($item = $cartItemsQ?->fetch_assoc()) {
        $cartItems[] = $item;
        $subtotal += floatval($item["price"]);

        $sellerId = $item["seller_id"];
        if (!isset($sellersInCart[$sellerId])) {
            $deliveryFee = ($item["seller_city_id"] == $buyerCityId && $buyerCityId != 0) ? 200 : 500;
            $totalDeliveryFee += $deliveryFee;
            $sellersInCart[$sellerId] = $deliveryFee;
        }
    }

    $total = $subtotal + $totalDeliveryFee;
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

            <a href="?tab=cart" class="py-4 font-medium border-b-2 
        <?php echo $tab == "cart" ? "border-blue-600 text-blue-600" :
            "border-transparent text-gray-600 hover:text-gray-900"; ?>">
                Cart</a>

            <a href="?tab=messages" class="py-4 font-medium border-b-2 
        <?php echo $tab == "messages" ? "border-blue-600 text-blue-600" :
            "border-transparent text-gray-600 hover:text-gray-900"; ?>">
                Messages</a>

            <a href="?tab=purchase-history" class="py-4 font-medium border-b-2 
        <?php echo $tab == "purchase-history" ? "border-blue-600 text-blue-600" :
            "border-transparent text-gray-600 hover:text-gray-900"; ?>">
                Purchase History</a>

        </div>
    </div>

    <!-- Dashboard tab -->
    <?php if ($tab == "dashboard"): ?>

        <section class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h2 class="text-3xl font-bold text-gray-900">Buyer Dashboard</h2>
                <p class="text-gray-600">Manage your learning journey</p>
            </div>
        </section>

    <?php elseif ($tab == "cart"): ?>

        <section class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h2 class="text-3xl font-bold text-gray-900">Shopping Cart</h2>
                <p class="text-gray-600">Review your items before checkout</p>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if (empty($cartItems)): ?>
                <div class="bg-white rounded-2xl border border-slate-100 p-16 text-center shadow-sm">
                    <div class="text-6xl mb-6">🛒</div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
                    <p class="text-slate-500 mb-8 max-w-sm mx-auto">Explore our wide range of skills and start your learning journey today</p>
                    <a href="search-products.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-xl
                    hover:bg-blue-700 transition-all shadow-lg active:scale-95">Browse Skills</a>
                </div>

            <?php else: ?>
                <div class="flex flex-col lg:flex-row gap-8">
                    <div class="flex-1 space-y-4">


                        <?php foreach ($cartItems as $item):
                            $sellerName = $item["seller_fname"] . " " . $item["seller_lname"];

                            $productData = [
                                'title' => $item['title'],
                                'seller' => $sellerName,
                                'level' => $item['level'],
                                'price' => $item['price'],
                                'image' => $item['image_url'] ? $item['image_url'] : '',
                                'description' => $item['description']
                            ];
                        ?>

                            <!-- Cart Item -->
                            <div onclick='openProductModal(<?= json_encode($productData) ?>);' id="cart-item-<?= $item["cart_item_id"]; ?>"
                                class="bg-white rounded-2xl border border-slate-100 p-4 flex gap-4 shadow-sm 
                        hover:shadow-md transition-shadow group cursor-pointer">
                                <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-slate-100">
                                    <?php if ($item["image_url"]): ?>
                                        <img src="<?= $item["image_url"] ?>" class="w-full h-full object-cover">

                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-3xl">📚</div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-bold text-gray-900 leading-tight group-hover:text-blue-600 transition-colors line-clamp-1">
                                                <?= $item["title"] ?>
                                            </h3>
                                            <p class="text-sm text-slate-500 mt-0.5"><?= $sellerName ?></p>
                                        </div>
                                        <button onclick="removeItem(<?= $item['cart_item_id'] ?>, event); "
                                            class="text-slate-300 hover:text-rose-500 p-1 transition-colors" title="remove">
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 
                                            11v6">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-3 mt-4">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                            <?= $item['level'] ?>
                                        </span>
                                        <span class="text-lg font-extrabold text-blue-600 ml-auto">
                                            Rs. <?= number_format($item['price'], 2) ?>
                                        </span>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>

                    </div>

                    <!-- Summary column -->
                    <aside class="lg:w-96 flex-shrink-0">
                        <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-xl sticky top-24">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>

                            <div class="space-y-4 pb-6 border-b border-slate-100">
                                <div class="flex justify-between text-slate-600">
                                    <span>Subtotal</span>
                                    <span class="font-bold text-gray-900">Rs. <span id="subtotal"><?= number_format($subtotal, 2); ?>
                                        </span></span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        <span>Course Document Delivery Fee</span>
                                        <div class="group relative">
                                            <span class="text-xs cursor-help bg-slate-100 w-4 h-4 rounded-full flex items-center 
                                            justify-center font-bold">?</span>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-900 text-white
                                            text-[10px] shadow-xl opacity-0 invisible group-hover:visible group-hover:opacity-100
                                            transition-all">Rs. 200 within same city, Rs. 500 across cities. Charged per seller.
                                            </div>
                                        </div>
                                    </div>
                                    <span class="font-bold text-gray-900">Rs. <span id="delivery"><?= number_format($totalDeliveryFee, 2); ?>
                                        </span></span>
                                </div>
                            </div>

                            <div class="pt-6">
                                <div class="flex justify-between items-center mb-8">
                                    <span class="text-lg font-bold text-gray-900">Total</span>
                                    <span class="text-2xl font-black text-blue-600 font-mono">Rs.
                                        <span id="total"><?= number_format($total, 2); ?></span></span>
                                </div>

                                <button onclick="checkout();" class="block w-full py-4 text-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700
                                hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all active:scale-95 mb-4">
                                    Proceed to Checkout
                                </button>
                                <p class="text-center text-xs text-slate-400">Secure checkout powered by PayHere</p>
                            </div>
                        </div>
                </div>
                </aside>

</div>
<?php endif; ?>
</section>

<!-- Toast for notification -->
<div id="toast" class="fixed top-5 right-5 z-50 transform translate-y-[-100px] transition-transform duration-300 pointer-events-none">
    <div class="bg-slate-900 text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 font-semibold">
        <span id="toast-icon">✓</span>
        <span id="toast-msg">Removed from cart</span>
    </div>
</div>

<!-- Product modal -->
<div class="fixed inset-0 z-[100] hidden" id="productModal">
    <!-- Overlay -->
    <div id="modalOverlay" class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm opacity-0 transition-opacity duration-300"
        onclick="closeProductModal();"></div>

    <!-- Model content -->
    <div id="modalContent" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-11/12 max-w-4xl bg-white rounded-3xl shadow-2xl
 overflow-hidden flex flex-col md:flex-row transform scale-95 opacity-0 transition-all duration-300 md:h-[550px]">
        <!-- Image section -->
        <div class="md:w-1/2 relarive bg-slate-100 aspect-video md:aspect-auto">
            <img src="" alt="" class="w-full h-full object-cover hidden" id="modalImg">
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
            <div id="modalPlaceholder" class="w-full h-full flex items-center justify-center text-7xl">📚</div>
            <span id="modalLevel" class="absolute top-4 left-4 text-xs font-bold px-3 py-1 rounded-md bg-blue-600 text-white 
    uppercase tracking-wider shadow-md">
                Beginner
            </span>
        </div>

        <!-- Content section -->
        <div class="p-8 md:w-1/2 flex flex-col relative">

            <!-- Close button -->
            <button class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full 
 w-10 h-10 flex items-center justify-center transition-colors" onclick="closeProductModal();">
                ✕
            </button>

            <!-- Title -->
            <h3 id="modalTitle" class="text-3xl font-extrabold text-gray-900 mt-8 leading-tight">
                Course Title
            </h3>

            <!-- Seller -->
            <p id="modalSeller" class="text-sm text-slate-500 mb-6">
                My Seller Name
            </p>

            <!-- Description -->
            <p id="modalDesc" class="text-base text-slate-700 mb-7 flex-1 overflow-y-auto max-h-56 leading-relaxed">
                Description here...
            </p>

            <!-- Price -->
            <div class="mt-auto pt-6 flex items-center justify-between border-t border-slate-200">
                <div>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Price</span>
                    <p id="modalPrice" class="text-3xl font-black text-blue-600">Rs. 0.00</p>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Cart JS -->
<?php if (!empty($cartItems)): ?>
    <script type="text/javaScript" src="https://www.payhere.lk/lib/payhere.js"></script>
    <script>
        let tid;

        function showToast(msg, icon = "✓") {
            clearTimeout(tid);
            const toast = document.getElementById("toast");
            document.getElementById("toast-msg").innerText = msg;
            document.getElementById("toast-icon").innerHTML = icon;
            toast.style.transform = "translateY(0)";
            tid = setTimeout(() => {
                toast.style.transform = "translateY(-100px)";
            }, 3000);
        }

        async function removeItem(cartItemId, e) {
            e.preventDefault();
            e.stopPropagation();
            if (!confirm("Remove this item from your cart?")) return;

            const itemEl = document.getElementById(`cart-item-${cartItemId}`);
            itemEl.style.opacity = "0.5";
            itemEl.style.pointerEvents = "none";

            const formData = new FormData();
            formData.append("cart_item_id", cartItemId);

            try {

                const res = await fetch("process/removeFromCart.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    itemEl.remove();
                    document.getElementById("subtotal").innerText = parseFloat(data.subtotal).toLocaleString(undefined, {
                        maximumFractionDigits: 2
                    });
                    document.getElementById("delivery").innerText = parseFloat(data.delivery).toLocaleString(undefined, {
                        maximumFractionDigits: 2
                    });
                    document.getElementById("total").innerText = parseFloat(data.total).toLocaleString(undefined, {
                        maximumFractionDigits: 2
                    });

                    const cc = document.getElementById("cart-count");
                    if (cc) {
                        cc.textContent = data.itemCount;
                    }

                    showToast("Item removed successfully!");

                    // If empty, page reload
                    if (parseFloat(data.itemCount) == 0) location.reload();

                } else {
                    itemEl.style.opacity = "1";
                    itemEl.style.pointerEvents = "auto";
                    alert(data.message || "Error removal! Please try again.");
                }

            } catch (e) {
                itemEl.style.opacity = "1";
                itemEl.style.pointerEvents = "auto";
                alert("Something went wrong! Please try again.")
            }
        }

        // Modal Logic
        function openProductModal(data) {
            const modal = document.getElementById("productModal");
            const overlay = document.getElementById("modalOverlay");
            const content = document.getElementById("modalContent");

            modal.classList.remove("hidden");
            document.body.style.overflow = "hidden";

            //    Animate in
            setTimeout(() => {
                overlay.classList.add("opacity-100");
                content.classList.add("scale-100", "opacity-100");
            }, 10);

            // Fill content
            document.getElementById("modalTitle").innerText = data.title;
            document.getElementById("modalSeller").innerText = "By " + data.seller;
            document.getElementById("modalLevel").innerText = data.level;
            document.getElementById("modalPrice").innerText = "Rs. " + parseFloat(data.price).toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("modalDesc").innerText = data.description || "No Description available!";

            if (data.image) {
                const modalImg = document.getElementById("modalImg");
                modalImg.src = data.image;
                modalImg.classList.remove("hidden");
                document.getElementById("modalPlaceholder").classList.add("hidden");
            } else {
                document.getElementById("modalImg").classList.add("hidden");
                document.getElementById("modalPlaceholder").classList.remove("hidden");
            }
        }

        function closeProductModal() {
            const modal = document.getElementById("productModal");
            const overlay = document.getElementById("modalOverlay");
            const content = document.getElementById("modalContent");

            //    Animate out
            overlay.classList.remove("opacity-100");
            content.classList.remove("scale-100", "opacity-100");
            setTimeout(() => {
                modal.classList.add("hidden");
                document.body.style.overflow = "";
            }, 300);
        }

        // Payhere Intergration
        payhere.onCompleted = async function onCompleted(orderId) {
            try {

                const fd = new FormData();
                fd.append("order_id", orderId);

                const r = await fetch("process/saveInvoice.php", {
                    method: "POST",
                    body: fd
                });

                const data = await r.json();

                if (data.success) {
                    window.location.href = "invoice.php?id=" + data.invoice_id;
                } else {
                    alert("Payment successful, but failed to process internal order: " + data.message);
                }

            } catch (e) {
                alert("Error Saving Invoice!.");
            }
        }

        payhere.onDismissed = function onDismissed() {
            alert("Payment dismissed!");
        }

        payhere.onError = function onError(error) {
            alert("Payment Error: " + error);
        }

        async function checkout() {
            try {

                const res = await fetch("process/payhereProcess.php", {
                    method: "POST"
                });

                const data = await res.json();
                if (data.success) {
                    payhere.startPayment(data.data);
                } else {
                    alert(data.message || "Error Generating payment details!");
                }

            } catch (e) {
                alert("Error connecting to checkout service!")
            }
        }
    </script>
<?php endif; ?>

<!-- Messages Tab -->
<?php elseif ($tab == "messages"): ?>

    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h2 class="text-3xl font-bold text-gray-900">Messages</h2>
            <p class="text-gray-600">Chat with Sellers and Support</p>
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
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-wider">Active Chat</p>
                    </div>
                </div>
                <div id="messageArea" class="overflow-y-auto flex-1 p-6 space-y-4 min-h-0">
                    <div class="flex-1 flex flex-col items-center justify-center text-center p-12 opacity-50">
                        <div class="text-5xl mb-4">💬</div>
                        <h3 class="font-bold text-gray-900">Select a Conversation</h3>
                        <p class="text-sm text-gray-500 mt-1">Choose a contact from the left to start messaging</p>
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

    <!-- Purchase History Tab -->
<?php elseif ($tab == "purchase-history"):
        $invoiceQ = Database::search(
            "SELECT * FROM `invoice` WHERE `user_id`=? ORDER BY `date` DESC",
            "i",
            [$userId]
        )
?>

    <main class="max-w-7xl mx-auto px-4 lg:px-8 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900">Purchase History</h1>
            <p class="text-gray-500 mt-2">View your past orders and share your feedback on the skills you've learned.</p>

        </div>

        <?php if ($invoiceQ && $invoiceQ->num_rows > 0): ?>
            <div class="space-y-6">
                <?php while ($invoice = $invoiceQ->fetch_assoc()): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">


                        <!-- Invoice Header -->
                        <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex flex-wrap justify-between items-center
                            gap-4">

                            <div class="flex items-center gap-6">
                                <div>
                                    <p class="text-xs font-bold  text-gray-400 uppercase tracking-wider">Date Placed</p>
                                    <p class="text-sm font-semibold text-gray-900"><?= date("M d, Y", strtotime($invoice["date"])); ?></p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold  text-gray-400 uppercase tracking-wider">Total Amount</p>
                                    <p class="text-sm font-semibold text-gray-900">Rs. <?= number_format($invoice["total"], 2); ?></p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold  text-gray-400 uppercase tracking-wider">Order Id</p>
                                    <p class="text-sm font-semibold text-gray-900">#<?= $invoice["order_order_id"]; ?></p>
                                </div>

                            </div>

                            <div>
                                <a href="invoice.php?id=<?= $invoice["order_order_id"] ?>" class="text-xs font-bold px-3 py-1.5 
                                rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">View Full Invoice</a>
                            </div>
                        </div>



                        <div class="divide-y divide-gray-100">
                            <?php
                            $itemsQ = Database::search(
                                "SELECT ii.*, p.`title`, p.`image_url`, u.`fname` AS `seller_first`, u.`lname` AS `seller_last`,
                                (SELECT `id` FROM `feedback` WHERE `user_id`=? AND `product_id`=ii.`product_id`
                                LIMIT 1) AS `feedback_id`
                                FROM `invoice_item` ii
                                JOIN `product` p ON ii.`product_id`=p.`id`
                                JOIN `user` u ON p.`seller_id` = u.`id`
                                WHERE ii.`invoice_id`=?",
                                "ii",
                                [$userId, $invoice["id"]]
                            );

                            while ($item = $itemsQ->fetch_assoc()):
                            ?>

                                <a href="product-view.php?id=<?= $item["product_id"] ?>" class="p-6 flex flex-col md:flex-row
                                    gap-6 items-start md:items-center hover:bg-blue-200 transition-colors">
                                    <div class="w-full md:w-32 h-20 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0 ">
                                        <?php if ($item["image_url"]): ?>
                                            <img src="<?= $item["image_url"]; ?>" class="w-full h-full object-cover" />
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex-grow">
                                        <h3 class="text-lg font-bold text-gray-900"><?= $item["title"]; ?></h3>
                                        <p class="text-sm text-gray-500 mt-1">Sold by :
                                            <span class="font-medium text-gray-700"><?= $item["seller_first"] . " " . $item["seller_last"]; ?>
                                            </span>
                                        </p>
                                        <p class="text-sm font-bold text-blue-600 mt-2">Rs. <?= number_format($item["price"], 2);  ?></p>

                                    </div>

                                    <div class="flex-shrink-0 w-full md:w-auto">
                                        <?php if ($item["feedback_id"]): ?>
                                            <span class="inline-flex items-center gap-1 text-green-600 font-bold text-sm 
                                            bg-green-50 px-4 py-2 rounded-xl">
                                                🟢 Feedback Submitted
                                            </span>
                                        <?php else: ?>
                                            <button onclick="openFeedbackModal(<?= $item['product_id'] ?>, '<?= addslashes($item['title']) ?>', event)"
                                                class="w-full md:w-auto px-6 py-2.5 bg-gray-900 text-white
                                                    font-bold rounded-xl shadow-sm hover:bg-black transition-all text-sm">
                                                Give Feedback
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </a>

                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>


        <?php else : ?>
            <div class="bg-white rounded-3xl p-12  text-center border-2 border-dashed border-gray-100">
                <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6
                text-3xl">
                    🛍️
                </div>
                <h3 class="text-xl font-bold text-gray-900">No Purchase yet</h3>
                <p class="text-gray-500 mt-2 max-w-sm mx-auto">Explore our wide range of skills and start your learning journey
                    today.
                </p>
                <a href="index.php" class="inline-block mt-8 px-8 py-3 bg-blue-600 text-white font-bold rounded-xl
            hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">Start Shopping</a>

            </div>
        <?php endif; ?>

    </main>

    <!-- feedback Modal -->
    <div id="feedback-modal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeFeedbackModal();"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-1">Give Feedback</h2>
                    <p id="modal-product-title" class="text-blue-600 font-medium text-sm mb-6"></p>

                    <form id="feedback-form" onsubmit="submitFeedback(event);">
                        <input type="hidden" id="modal-product-id" />

                        <div class="mb-6 ">
                            <label class="block text-sm font-bold text-gray-700 mb-3">Rating</label>

                            <div class="flex gap-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" onclick="setRating(<?= $i ?>);" class="rating-star text-3xl 
                                         text-gray-300 hover:text-yellow-400 transition-colors" data-value="<?= $i ?>">

                                        ★

                                    </button>
                                <?php endfor; ?>
                                <input type="hidden" id="modal-rating" value="5" />

                            </div>

                        </div>

                        <div class="mb-8">
                            <label for="modal-message" class="block text-sm font-bold text-gray-700 mb-2">Your Experience</label>
                            <textarea id="modal-message" rows="4" required placeholder="Share your thoughts about this skill..."
                                class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:border-blue-500
                                        focus:ring-4 focus:ring-blue-100 transition-all outline-none text-gray-700 resize-none"></textarea>

                        </div>

                        <div class="flex gap-4">
                            <button type="button" onclick="closeFeedbackModal();" class="flex-1 px-6 py-3 border border-gray-200
                                    text-gray-600 font-bold rounded-2xl hover:bg-gray-50 transition-all">Cancel</button>

                            <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white font-bold  border border-gray-200
                                   rounded-2xl hover:bg-gray-700 transition-all shadow-lg shadow-blue-200">Submit</button>

                        </div>
                    </form>

                </div>

            </div>

        </div>

    </div>

    <script>
        function setRating(val) {
            document.getElementById("modal-rating").value = val;
            const stars = document.querySelectorAll(".rating-star");
            stars.forEach(s => {
                if (parseInt(s.dataset.value) <= val) {
                    s.classList.remove("text-gray-300");
                    s.classList.add("text-yellow-400");

                } else {
                    s.classList.remove("text-yellow-400");
                    s.classList.add("text-gray-300");

                }
            });
        }

        function openFeedbackModal(pid, title, e) {
            e.preventDefault();
            document.getElementById("modal-product-id").value = pid;
            document.getElementById("modal-product-title").innerText = title;
            document.getElementById("modal-message").value = "";

            setRating(5);
            document.getElementById("feedback-modal").classList.remove("hidden")

        }

        function closeFeedbackModal() {
            document.getElementById("feedback-modal").classList.add("hidden");
        }

        async function submitFeedback(e) {
            e.preventDefault();
            const pid = document.getElementById("modal-product-id").value;
            const rating = document.getElementById("modal-rating").value;
            const msg = document.getElementById("modal-message").value;

            const fd = new FormData();
            fd.append("pid", pid);
            fd.append("rating", rating);
            fd.append("message", msg);

            try {
                const res = await fetch("process/saveFeedback.php", {
                    method: "POST",
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    alert("Thank you for your feedback!");
                    location.reload();
                } else {
                    alert(data.message || "Failed to save feedback!");

                }

            } catch (err) {
                alert("Error Submitting Feedback!");

            }
        }
    </script>


<?php endif; ?>

</div>

<?php include "footer.php"; ?>