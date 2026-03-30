<?php
session_start();
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] != true) {
    header("Location: admin-login.php");
    exit;
}

require_once "db/connection.php";

// Fetch Stats for Admin Dashboard
$totalUsers = Database::search("SELECT COUNT(*) AS `c` FROM `user`")->fetch_assoc()["c"];
$totalProducts = Database::search("SELECT COUNT(*) AS `c` FROM `product`")->fetch_assoc()["c"];
$totalOrders = Database::search("SELECT COUNT(*) AS `c` FROM `order`")->fetch_assoc()["c"];
$totalEarnings = Database::search("SELECT SUM(`total_amount`) AS `s` FROM `order`")->fetch_assoc()["s"] ?? 0;

$recentOrders = Database::search(
    "SELECT o.*, u.`fname`, u.`lname`, p.`title` 
    FROM `order` o
    JOIN `user` u ON o.`user_id` = u.`id`
    JOIN `product` p ON o.`product_id` = p.`id`
    ORDER BY o.`created_at` DESC LIMIT 5",
);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin | SkillShop</title>
    <link rel="icon" href="Images/icon.png" type="image/png" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 min-h-screen">

    <div class="flex flex-col md:flex-row min-h-screen">

        <!-- Sidebar -->
        <aside class="w-full md:w-64 bg-slate-900 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-blue-500">SkillShop</h1>
                <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest">Admin Control</p>
            </div>

            <nav class="mt-6 px-4 space-y-2">
                <a href="admin-dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600 rounded-xl text-sm font-bold shadow-lg
                shadow-blue-500/20">
                    <span>📊</span> Dashboard
                </a>

                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-800 rounded-xl text-sm font-medium transition-colors">
                    <span>👥</span> User Management
                </a>

                <a href="admin-products.php" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-800 rounded-xl text-sm font-medium transition-colors">
                    <span>🛍️</span> Product Management
                </a>

                <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-800 rounded-xl text-sm font-medium transition-colors">
                    <span>📜</span> Transactions
                </a>
            </nav>

            <div class="mt-auto p-6 border-t border-slate-800">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center font-bold">A</div>
                    <div>
                        <p class="text-sm font-bold"><?= $_SESSION["admin_fname"] ?></p>
                        <p class="text-[10px] text-slate-400 uppercase">Administrator</p>
                    </div>
                </div>

                <a href="process/adminLogoutProcess.php" class="block w-full text-center py-2 bg-slate-800 hover:bg-red-900/40 hover:text-red-400
                rounded-lg text-xs font-bold transaction-all border border-slate-700">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">

            <!-- Top nav -->
            <header class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center sticky top-0 z-30">
                <h2 class="text-xl font-extrabold text-slate-900">Dashboard Overview</h2>
                <div class="flex items-center gap-4">
                    <button class="p-2 text-slate-400 hover:text-slate-600">🔔</button>
                    <div class="h-6 w-px bg-slate-200"></div>
                    <span class="text-xs font-boldtext-slate-500 uppercase tracking-wider"><?= date("D, M j") ?></span>
                </div>
            </header>

            <div class="p-8">

                <!-- Stats Grids -->
                <!-- Users card -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <span class="p-3 bg-blue-50 text-blue-600 rounded-xl text-xl">👥</span>
                            <span class="text-[10px] font-bold text-green-500 uppercase">+12%</span>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Total Users</p>
                        <h3 class="text-3xl font-black text-slate-900"><?= number_format($totalUsers); ?></h3>
                    </div>

                    <!-- Products card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <span class="p-3 bg-blue-50 text-blue-600 rounded-xl text-xl">🛍️</span>
                            <span class="text-[10px] font-bold text-green-500 uppercase">Skills</span>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Active Products</p>
                        <h3 class="text-3xl font-black text-slate-900"><?= number_format($totalProducts); ?></h3>
                    </div>

                    <!-- Revenue card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <span class="p-3 bg-blue-50 text-blue-600 rounded-xl text-xl">💰</span>
                            <span class="text-[10px] font-bold text-green-500 uppercase">Live</span>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Total Revenue</p>
                        <h3 class="text-3xl font-black text-slate-900"><?= number_format($totalEarnings, 2); ?></h3>
                    </div>

                    <!-- Orders card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <span class="p-3 bg-blue-50 text-blue-600 rounded-xl text-xl">📜</span>
                            <span class="text-[10px] font-bold text-green-500 uppercase">Scales</span>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Total Orders</p>
                        <h3 class="text-3xl font-black text-slate-900"><?= number_format($totalOrders); ?></h3>
                    </div>

                </div>

                <!-- Summerized Report -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">

                    <!-- Category Sales -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="font-bold text-slate-900 mb-6">Sales By Category</h3>
                        <div class="space-y-4">
                            <?php
                            $catSales = Database::search(
                                "SELECT c.`name`, COUNT(o.`order_id`) AS `count`, SUM(o.`total_amount`) AS `total`
                                FROM `category` c
                                LEFT JOIN `product` p ON c.`id` = p.`category_id`
                                LEFT JOIN `order` o ON p.`id` = o.`product_id`
                                GROUP BY c.`id` ORDER BY `total` DESC"
                            );
                            while ($cat = $catSales->fetch_assoc()):
                                $percent = $totalEarnings > 0 ? ($cat['total'] / $totalEarnings) * 100 : 0;
                            ?>

                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="font-bold text-slate-700"><?= $cat['name'] ?></span>
                                        <span class="text-slate-500"><?= number_format($percent, 1); ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-blue-600 h-full transition-all duration-100" style="width: <?= $percent ?>%;"></div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Monthly Trend (Simulation with current data) -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="font-bold text-slate-900 mb-6">Revenue Trend</h3>
                        <div class="flex items-end justify-between h-48 gap-2">
                            <?php
                            $months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                            foreach ($months as $m):
                                $h = rand(20, 100);
                            ?>

                                <div class="flex-1 flex flex-col items-center gap-2 group">
                                    <div class="w-full bg-slate-50 group-hover:bg-blue-50 rounded-t-lg transition-all relative flex items-in
                                    justify-center" style="height: <?= $h ?>%;">
                                        <div class="w-2/3 bg-blue-100 group-hover:bg-blue-600 h-0 group-hover:h-full transition-all 
                                duration-500 rounded-t-md"></div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase"><?= $m ?></span>
                                </div>

                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <!-- Recent Activities -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 flex justify-between text-center">
                        <h3 class="font-bold text-slate-900">Recent Transactions</h3>
                        <a href="#" class="text-xs font-bold text-blue-600 hover:underline uppercase tracking-widest">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                    <th class="px-8 py-4">Order ID</th>
                                    <th class="px-8 py-4">Buyer</th>
                                    <th class="px-8 py-4">Product</th>
                                    <th class="px-8 py-4">Amonut</th>
                                    <th class="px-8 py-4">Status</th>
                                    <th class="px-8 py-4">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                                    <?php while ($order = $recentOrders->fetch_assoc()): ?>

                                        <tr class="hover:bg-slate-50/50 transition-colors">

                                            <td class="px-8 py-4 text-sm font-bold text-slate-900">
                                                <?= substr($order["order_id"], 0, 8); ?>
                                            </td>
                                            <td class="px-8 py-4 text-sm font-medium text-slate-600">
                                                <?= $order["fname"] . " " . $order["lname"]; ?>
                                            </td>
                                            <td class="px-8 py-4 text-sm font-medium text-slate-600">
                                                <?= $order["title"]; ?>
                                            </td>
                                            <td class="px-8 py-4 text-sm font-medium text-slate-600">
                                                <?= number_format($order["total_amount"], 2); ?>
                                            </td>
                                            <td class="px-8 py-4">
                                                <span class="px-3 py-1 bg-green-50 text-green-700 text-[10px] font-bold rounded-full uppercase">
                                                    <?= $order["payment_status"]; ?>
                                                </span>
                                            </td>
                                            <td class="px-8 py-4 text-sm font-medium text-slate-600">
                                                <?= date("M j, Y", strtotime($order["created_at"])); ?>
                                            </td>

                                        </tr>

                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-8 py-12 text-center text-slate-400 italic">No Transactions Found!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>

    </div>

</body>

</html>