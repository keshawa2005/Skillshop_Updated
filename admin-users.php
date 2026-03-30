<?php
session_start();
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] != true) {
    header("Location: admin-login.php");
    exit;
}

require_once "db/connection.php";

//  Fetch all users
$query = "SELECT u.*, GROUP_CONCAT(at.`name`) AS `roles`
FROM `user` u
LEFT JOIN `user_has_account_type` uhat ON u.`id` = uhat.`user_id`
LEFT JOIN `account_type` at ON uhat.`account_type_id` = at.`id`
GROUP BY u.`id`
ORDER BY u.`created_at` DESC";

$users = Database::search($query);

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
                <a href="admin-dashboard.php" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-800 rounded-xl text-sm font-medium transition-colors">
                    <span>📊</span> Dashboard
                </a>

                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600 rounded-xl text-sm font-bold shadow-lg
                shadow-blue-500/20">
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
                <h2 class="text-xl font-extrabold text-slate-900">Manage Users</h2>
                <div class="flex items-center gap-4">
                    <input type="text" id="userSearch" onkeyup="filterUsers();" placeholder="Search Users...."
                        class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-blue-500/10 outline-none">
                </div>
            </header>

            <div class="p-8">

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100">
                    <table class="w-full text-left" id="usersTable">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                <th class="px-8 py-4">User Details</th>
                                <th class="px-8 py-4">Roles</th>
                                <th class="px-8 py-4">Joined Date</th>
                                <th class="px-8 py-4">Status</th>
                                <th class="px-8 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors user-row">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center font-bold 
                                            text-slate-400">
                                                <?= substr($user["fname"], 0, 1) ?>
                                            </div>

                                            <div>
                                                <p class="text-sm font-bold text-slate-900 user-name"><?= $user["fname"] . " " . $user["lname"] ?></p>
                                                <p class="text-xs text-slate-500 user-email"><?= $user["email"] ?></p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-8 py-5">
                                        <div class="flex gap-1">
                                            <?php
                                            $roles = explode(",", $user["roles"]);
                                            foreach ($roles as $role) :
                                                $color = ($role == "Seller") ? "bg-indigo-50 text-indigo-600" : "bg-blue-50 text-blue-600";
                                            ?>
                                                <span class="px-2 py-0.5 <?= $color ?> text-[9px] font-black uppercase rounded-md">
                                                    <?= $role ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>

                                    <td class="px-8 py-5 text-xs text-slate-500">
                                        <?= date("M j, Y", strtotime($user["created_at"])) ?>
                                    </td>

                                    <td class="px-8 py-5">
                                        <span id="status-<?= $user["id"] ?>" class="px-3 py-1 text-[10px] font-bold rounded-full uppercase 
                                        <?= ($user["status"] == "Active") ? "bg-green-50 text-green-700" : "bg-red-50 text-red-700"; ?>">
                                            <?= $user["status"]; ?>
                                        </span>
                                    </td>

                                    <td class="px-8 py-5 text-right">
                                        <button onclick="toggleUserStatus(<?= $user['id']; ?>);" id="btn-<?= $user["id"]; ?>"
                                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all 
                                            <?= $user['status'] == "Active" ?
                                                "bg-red-50 text-red-600 hover:bg-red-700 hover:text-white" :
                                                "bg-green-600 text-white hover:bg-green-700" ?>">
                                            <?= ($user["status"] == "Active") ? "Block" : "Unblock";  ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>

                    </table>
                </div>

            </div>

        </main>

    </div>

<script>
        function filterUsers() {
            const input = document.getElementById('userSearch');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const name = row.querySelector('.user-name').innerText.toLowerCase();
                const email = row.querySelector('.user-email').innerText.toLowerCase();
                if (name.includes(filter) || email.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        async function toggleUserStatus(userId) {
            const btn = document.getElementById('btn-' + userId);
            const statusSpan = document.getElementById('status-' + userId);
            
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = '...';

            const fd = new FormData();
            fd.append('id', userId);

            try {
                const res = await fetch('process/toggleUserStatus.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    if (data.newStatus == 'Active') {
                        statusSpan.innerText = 'Active';
                        statusSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-full uppercase bg-green-50 text-green-700';
                        btn.innerText = 'Block';
                        btn.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-all bg-red-50 text-red-600 hover:bg-red-600 hover:text-white';
                    } else {
                        statusSpan.innerText = 'Blocked';
                        statusSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-full uppercase bg-red-50 text-red-700';
                        btn.innerText = 'Unblock';
                        btn.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-all bg-green-600 text-white hover:bg-green-700';
                    }
                }
            } catch (err) {
                console.error(err);
                btn.innerText = originalText;
            } finally {
                btn.disabled = false;
            }
        }
    </script>

</body>

</html>