<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once "db/connection.php";

// Check if user is logged in, if not, check for remember me token
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    require_once 'process/auth-check.php'; // Adjust the path as needed
}

$loggedIn = isset($_SESSION['logged_in']) ?? false;
$userRole = isset($_SESSION['active_account_type']) ?? "";
$userId = isset($_SESSION['user_id']) ?? 0;

$productId = intval($_GET["id"] ?? 0);
if ($productId <= 0) {
    header("Location: search-products.php");
    exit();
}

// Product + Seller + Category
$p = Database::search(
    "SELECT p.*, c.name AS cat_name,
    u.`fname`, u.`lname`, u.`id` AS `sid`, u.`created_at` AS `s_joined`
    FROM `product` p
    LEFT JOIN `category` c ON p.`category_id` = c.`id`
    LEFT JOIN `user` u ON p.`seller_id` = u.`id`
    WHERE p.`id` = ? AND p.`status` = 'Active'",
    "i",
    [$productId]
);

if (!$p || $p->num_rows == 0) {
    header("Location: search-products.php");
    exit();
}

$p = $p->fetch_assoc();

// Stats
$rs = Database::search(
    "SELECT AVG (`rating`) AS `r`, COUNT(*) AS `c` FROM `feedback` WHERE `product_id` = ?",
    "i",
    [$productId]
)->fetch_assoc();

$sc = Database::search(
    "SELECT COUNT(DISTINCT `user_id`) AS `c` FROM `order` WHERE `product_id` = ?",
    "i",
    [$productId]
)->fetch_assoc();

$avgR = $rs["r"] ? round($rs["r"], 1) : 0;
$revC = intval($rs["c"]);
$stuC = intval($sc["c"]);

// Reviews
$revQ = Database::search(
    "SELECT f.*, u.`fname`, u.`lname`
    FROM `feedback` f
    LEFT JOIN `user` u ON f.`user_id` = u.`id`
    WHERE f.`product_id` = ?
    ORDER BY f.`created_at` DESC LIMIT 8",
    "i",
    [$productId]
);
$reviews = [];
while ($r = $revQ?->fetch_assoc()) $reviews[] = $r;

// Seller stats
$ss = Database::search(
    "SELECT COUNT(DISTINCT p.`id`) `tc`, COUNT(DISTINCT o.`user_id`) `ts`, AVG(COALESCE(f.`rating`, 0)) `tr`
   FROM `product` p
   LEFT JOIN `order` o ON p.`id` = o.`product_id`
   LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
   WHERE p.`seller_id` = ? AND p.`status` = 'Active'",
    "i",
    [$p["sid"]]
)->fetch_assoc();

// Related
$relQ = Database::search(
    "SELECT p.`id`, p.`title`, p.`image_url`, p.`price`, p.`level`, AVG(COALESCE(f.`rating`, 0)) `ar`
    FROM `product` p
    LEFT JOIN `feedback` f ON p.`id` = f.`product_id`
    WHERE p.`seller_id` = ? AND p.`id` != ? AND p.`status` = 'Active'
    GROUP BY p.`id` LIMIT 3",
    "ii",
    [$p["sid"], $productId]
);
$related = [];
while ($r = $relQ?->fetch_assoc()) $related[] = $r;

// Purchased
$bought = false;
if ($loggedIn && $userId) {
    $bq = Database::search(
        "SELECT `order_id` FROM `order` WHERE `product_id` = ? AND `user_id` = ?  LIMIT 1",
        "ii",
        [$productId, $userId]
    );
    $bought = $bq && $bq?->num_rows > 0;
}

// watchlist
$inWatchlist = false;
if ($loggedIn && $userId) {
    $wq = Database::search(
        "SELECT `id` FROM `watchlist` WHERE `product_id` = ? AND `user_id` = ? LIMIT 1",
        "ii",
        [$productId, $userId]
    );
    $inWatchlist = $wq && $wq?->num_rows > 0;
}

// In cart
$inCart = false;
if ($loggedIn && $userId) {
    $cq = Database::search(
        "SELECT `id` FROM `cart` WHERE `product_id` = ? AND `user_id` = ? LIMIT 1",
        "ii",
        [$productId, $userId]
    );
    $inCart = $cq && $cq?->num_rows > 0;
}

$sellerName = $p["fname"] . " " . $p["lname"];

require "header.php";
?>

<div class="min-h-screen" style="background: #f8fafc;">

    <!-- Hero -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-blue-900" style="min-height: 280px;">
        <?php if ($p["image_url"]): ?>
            <img src="<?= $p["image_url"] ?>" alt=""
                class="absolute inset-0 w-full h-full object-cover opacity-10 scale-105 blur-sm pointer-events-none">
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent pointer-events-none">

            <div class="relative max-w-6xl mx-auto px-4 pt-10 pb-16">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm text-slate-400 mb-6">
                    <a href="search-products.php" class="hover:text-white transition-colors">Skills</a>
                    <?php if ($p["cat_name"]): ?>
                        <span>›</span>
                        <a href="search-products.php?cat=<?= $p["category_id"] ?>" class="hover:text-white transition-colors">
                            <?= $p["cat_name"] ?> </a>
                    <?php endif; ?>
                </nav>

                <!-- Badge -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="inline-flex items-center text-xs font-bold px-3 py-1 rounded-full bg-blue-500/30 text-blue-200">
                        <?= $p["level"] ?>
                    </span>
                    <?php if ($p["cat_name"]): ?>
                        <span class="inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full bg-white-10 text-slate-300">
                            <?= $p["cat_name"] ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($bought): ?>
                        <span class="inline-flex items-center text-xs font-bold px-3 py-1 rounded-full bg-green-500/25 text-green-300">
                            ✔️ Enrolled
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="text-3xl md:text-4xl font-extrabold text-white leading-tight mb-4 max-w-2xl">
                    <?= $p["title"] ?>
                </h1>

                <!-- Quick stats -->
                <div class="flex flex-wrap items-center gap-5 text-sm text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <span class="text-yellow-400">⭐</span>
                        <strong class="text-white"><?= $avgR ?: "New" ?></strong>
                        <span>(<?= $revC ?> review<?= $revC > 1 ? "s" : "" ?>)</span>
                    </span>
                    <span>👥 <?= number_format($stuC) ?> Students</span>
                    <span>👤 By <strong class="text-white"><?= $sellerName ?></strong></span>
                    <span>📅 <?= date("M Y", strtotime($p["created_at"])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="flex flex-col lg:flex-row gap-8">

            <!-- Left column -->
            <div class="flex-1 min-w-0 space-y-6">
                <!-- Description -->
                <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-3">About this skill</h2>
                    <p class="text-gray-600 leading-relaxed text-sm"><?= nl2br($p["description"]) ?></p>
                </div>

                <!-- what's included -->
                <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-3">What's included</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php foreach (
                            [
                                "Full course access",
                                "Lifetime access",
                                "Direct support",
                                "Resources",
                                "Certificate",
                                "Expert content"
                            ] as $f
                        ): ?>
                            <div class="flex items-center gap-2 text-sm text-gray-700 bg-green-50 rounded-xl px-3 py-2">
                                <span class="text-green-500 font-bold text-base">✓</span> <?= $f ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-lg font-bold text-gray-900">Reviews</h2>
                        <div class="text-right">
                            <span class="text-3xl font-extrabold text-gray-900"><?= $avgR ?: "-" ?></span>
                            <div class="text-yellow-400 text-sm" id="avg-stars"></div>
                            <span class="text-xs text-gray-400"><?= $revC ?> review<?= $revC != 1 ? "s" : ""; ?></span>
                        </div>
                    </div>

                    <?php if ($reviews): ?>
                        <div class="space-y-4">
                            <?php foreach ($reviews as $rv):
                                $name = $rv["fname"] . " " . $rv["lname"] ?: "Anonymous";
                                $init = strtoupper(($rv["fname"][0] ?? "A") . ($rv["lname"][0] ?? ""));
                            ?>

                                <div class="flex gap-3 p-4 rounded-xl bg-slate-50">
                                    <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs 
                            font-bold bg-gradient-to-br from-blue-500 to-indigo-600"><?= $init ?></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-gray-900 text-sm"><?= $name ?></span>
                                            <span class="text-gray-400 text-xs"><?= date("M j, Y", strtotime($rv["created_at"])) ?></span>
                                        </div>
                                        <div class="text-yellow-400 text-xs my-0.5" data-star="<?= intval($rv["rating"]) ?>"></div>
                                        <?php if ($rv["message"]): ?>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <?= nl2br($rv["message"]) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Related Skills -->
                <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">More by <?= $sellerName ?></h2>

                    <div class="grid sm:grid-cols-3 gap-4">
                        <?php foreach ($related as $r): ?>
                            <a href="product-view.php?id=<?= $r["id"] ?>" class="group block rounded-xl overflow-hidden border 
                            border-slate-100 hover:border-blue-200 hover:shadow-md transition-all">
                                <div class="h-28 bg-slate-200 overflow-hidden">

                                    <?php if ($r["image_url"]): ?>
                                        <img src="<?= $r["image_url"] ?>"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <p class="text-xs text-blue-600 font-semibold"><?= $r["level"] ?></p>
                                    <p class="text-sm font-bold text-gray-900 hover:text-blue-600 transition-colors line-clamp-2">
                                        <?= $r["title"] ?></p>
                                    <p class="text-sm font-bold text-blue-600 mt-1">
                                        Rs. <?= number_format($r["price"], 2) ?>
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Right column -->
            <aside class="lg:w-80 flex-shrink-0">
                <div class="sticky top-24 space-y-4">

                    <!-- CTA card -->
                    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-xl">
                        <?php if ($p["image_url"]): ?>
                            <div class="h-40 overflow-hidden">
                                <img src="<?= $p["image_url"] ?>" class="w-full h-full object-cover">
                            </div>
                        <?php endif; ?>

                        <div class="p-5">
                            <p class="text-2xl font-extrabold text-gray-900 mb-0.5">Rs. <?= number_format($p["price"], 2) ?></p>
                            <p class="text-xs text-gray-400 mb-4">One time - Lifetime access</p>

                            <!-- Enroll / Status -->
                            <?php if ($bought): ?>
                                <div class="flex items-center justify-center gap-2 py-3 mb-3 rounded-xl text-sm font-bold text-green-700
                                bg-green-50 border-2 border-green-300">
                                    ✓ Already enrolled
                                </div>
                                <a href="buyer-dashboard.php?tab=learning"
                                    class="block w-full py-3.5 text-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 
                            hover:to-indigo-700 text-white font-bold rounded-xl transition-all active:scale-95 shadow-md mb-3">
                                    Go to My Learnings
                                </a>

                            <?php elseif ($loggedIn && $userRole == "Buyer"): ?>
                                <a href="process/checkout.php?id=<?= $p["id"]; ?>" class="block w-full py-3.5 text-center bg-gradient-to-r from-blue-600 to-indigo-600 
                         hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl transition-all 
                         active:scale-95 shadow-md mb-3">🎓 Enroll Now</a>

                                <!-- Cart / Watchlist -->
                                <div class="flex gap-2 mb-4">
                                    <button id="cart-btn" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border-2 font-semibold
                            text-sm transition-all border-slate-200 text-slate-600 hover:border-blue-400 hover:text-blue-600"
                                        data-product-id="<?= $productId; ?>" data-in="<?= $inCart ? 1 : 0; ?>">
                                        🛒 <span id="cart-text"><?= $inCart ? "In Cart" : "Add to Cart"; ?></span></button>

                                    <button id="wl-btn" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 
                                    rounded-xl border-2 font-semibold text-sm transition-all border-slate-200 text-slate-600 
                                    hover:border-rose-400 hover:text-rose-600"
                                        data-product-id="<?= $productId; ?>" data-in="<?= $inWatchlist ? 1 : 0; ?>">
                                        <?= $inWatchlist ? "♥" : "♡" ?>
                                        <span id="wl-text"><?= $inWatchlist ? "In Watchlist" : "Watchlist"; ?></span></button>
                                </div>


                            <?php elseif (!$loggedIn): ?>
                                <a href="index.php"
                                    class="block w-full py-3.5 text-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 
                            hover:to-indigo-700 text-white font-bold rounded-xl transition-all active:scale-95 shadow-md mb-3">
                                    Sign In to enroll</a>

                            <?php else: ?>
                                <div class="text-center py-3 text-sm text-gray-400 mb-4">Sellers cannot enroll</div>
                            <?php endif; ?>



                            <!-- Meta info -->
                            <div class="space-y-2.5 text-sm border-t border-slate-100 pt-4">
                                <?php foreach (
                                    [
                                        ["📊", "Level", $p["level"]],
                                        ["👥", "Students", number_format($stuC) . " enrolled"],
                                        ["⭐", "Rating", $avgR > 0 ? "$avgR / 5 ($revC review" . ($revC != 1 ? "s" : "") . ")" : "No reviews yet"],
                                        ["📅", "Added", date("F Y", strtotime($p["created_at"]))]
                                    ] as [$ico, $lbl, $val]
                                ): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500"><?= $ico ?><?= $lbl ?></span>
                                        <span class="font-semibold text-gray-800 text-right"><?= $val ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Instructor card -->
                    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-xl">
                        <h3 class="font-bold text-gray-900 mb-4">Instructor</h3>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-white font-bold
                            text-lg bg-gradient-to-br from-blue-600 to-indigo-600"><?= strtoupper($p["fname"][0]) ?? "S" ?></div>

                            <div>
                                <p class="font-bold text-gray-900"><?= $sellerName ?></p>
                                <p class="text-xs text-blue-600 font-medium">Skill Instructor</p>
                                <?php if ($p["s_joined"]): ?>
                                    <p class="text-xs text-gray-400">Since <?= date("M Y", strtotime($p["s_joined"])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center mb-4">
                            <div class="rounded-xl py-2 bg-blue-50">
                                <p class="text-xs text-blue-600 font-extrabold"><?= intval($ss["tc"]) ?></p>
                                <p class="text-xs text-gray-500">Courses</p>
                            </div>
                            <div class="rounded-xl py-2 bg-green-50">
                                <p class="text-xs text-green-600 font-extrabold"><?= number_format($ss["ts"]) ?></p>
                                <p class="text-xs text-gray-500">Students</p>
                            </div>
                            <div class="rounded-xl py-2 bg-yellow-50">
                                <p class="text-xs text-yellow-600 font-extrabold"><?= $ss["tr"] > 0 ? round($ss["tr"], 1) : "-"; ?></p>
                                <p class="text-xs text-gray-500">Ratings</p>
                            </div>
                        </div>

                        <?php if ($loggedIn && $userRole == "Buyer"): ?>
                            <a href="buyer-dashboard.php?tab=messages&other_id=<?= $p["sid"] ?>&other_name=<?= urlencode($sellerName) ?>"
                                class="block w-full gap-2 py-2.5 text-center border-2 rounded-xl font-semibold
                            text-sm transition-all border-slate-200 text-gray-600 hover:border-blue-400 hover:text-blue-600">
                                ✉️ Message Instructor
                            </a>
                        <?php endif; ?>
            </aside>

        </div>
    </div>

</div>

<script>
    // watchlist
    function stars(n) {
        return Array.from({
            length: 5
        }, (_, i) => `<span>${ i < n ? "★" : "☆" }</span>`).join('');
    }
    document.querySelectorAll('[data-star]').forEach(e => e.innerHTML = stars(+e.dataset.star));
    const avgEl = document.getElementById("avg-stars");
    if (avgEl) avgEl.innerHTML = stars(<?= round($avgR) ?>);

    (function() {
        const wl = document.getElementById('wl-btn');
        if (!wl) return;

        wl.addEventListener("click", async (e) => {


            const id = wl.dataset.productId;
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
                    wl.dataset.in = inW ? 1 : 0;
                    wl.querySelector("#wl-text").textContent = inW ? "In Watchlist" : "Watchlist";
                    if (wl.firstChild) wl.firstChild.textContent = (inW ? "♥" : "♡") + ' ';
                }

            } catch (_) {

            }
        });

    })();

    // Cart
    (function() {
        const ct = document.getElementById('cart-btn');
        if (!ct || !ct.dataset.productId) return;

        ct.addEventListener("click", async (e) => {

            e.preventDefault
            const id = ct.dataset.productId;
            if (!id) return;

            const fd = new FormData();
            fd.append("product_id", id);

            try {
                const r = await fetch("process/cartProcess.php", {
                    method: "POST",
                    body: fd
                });

                const j = await r.json();
                if (j.success) {

                    const inC = j.action == "added";
                    ct.dataset.in = inC ? 1 : 0;

                    document.getElementById("cart-text").textContent = inC ? "In Cart" : "Add to Cart";
                    ct.className = `flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border-2 font-semibold
                    text-sm transition-all ${inC ? "border-blue-400 text-blue-600 bg-blue-50" : 
                    "border-slate-200 text-slate-600 hover:border-blue-400 hover:text-blue-600"}`;

                    // Update header
                    const cc = document.getElementById("cart-count");
                    if (cc) {
                        let count = parseInt(cc.textContent) || 0;
                        count = inC ? count + 1 : count - 1;
                        cc.textContent = count;
                        //cc.classList.toggle("hidden", count <= 0);
                        //cc.classList.toggle("flex", count > 0);
                    }

                }

            } catch (_) {

            }
        });

    })();
</script>

<?php require "footer.php"; ?>