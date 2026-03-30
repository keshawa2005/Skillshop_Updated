<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
    header('Location: admin-dashboard.php');
    exit;
}
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

<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden min-h-[500px] flex flex-col">

            <!-- Header -->
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-8 py-10 text-center text-white flex-shrink-0">
                <div id="headerIcon" class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto font-bold 
                shadow-lg mb-4 text-2xl">
                    A
                </div>
                <h1 class="text-3xl font-bold mb-1" id="headerTitle">Admin Panel</h1>
                <p class="text-slate-400 text-sm" id="headerSubtitle">Sign in to manage SkillShop</p>
            </div>

            <!-- Steps Container -->
            <div class="px-8 py-8 flex-1 flex flex-col justify-center">

                <!-- Step 1: Email -->
                <div class="space-y-6" id="step1">
                    <form action="" onsubmit="adminLogin(event);" class="space-y-6 text-center">
                        <div>
                            <label for="email" class="block text-sm font-bold text-slate-700 mb-2">Admin Email</label>
                            <input type="email" id="email" name="email" required placeholder="admin@skillshop.com"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 
                                focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                        </div>
                        <button type="submit" id="loginBtn" class="w-full bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-black
                                transition-all shadow-lg active-scale-[0.98]">Send Verification Code
                        </button>
                    </form>
                </div>

                <!-- Step 2: Verification Code -->
                <div class="space-y-6 hidden" id="step2">
                    <form onsubmit="adminVerify(event);" class="space-y-6 text-center">
                        <div>
                            <label for="vcode" class="block text-sm font-bold text-slate-700 mb-4">Enter 6-digit code</label>
                            <input type="text" id="vcode" name="vcode" required placeholder="000000"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 
                                focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all text-center text-3xl 
                                font-bold tracking-[0.5em]" maxlength="6">
                        </div>
                        <button type="submit" id="verifyBtn" class="w-full bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-black
                                transition-all shadow-lg active-scale-[0.98]">Verify & Login
                        </button>

                        <button type="button" onclick="goToStep1();" class="text-xs text-slate-400 hover:text-slate-600 font-bold
                        uppercase tracking-widest">
                            Change Email
                        </button>
                    </form>
                </div>

                <div id="message" class="hidden mt-6 p-4 rounded-xl text-sm font-medium text-center"></div>

            </div>

            <!-- Footer -->
            <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 text-center flex-shrink-0">
                <a href="home.php" class="text-sm text-slate-500 hover:text-blue-600 transition-colors">Back to SkillShop</a>
            </div>

        </div>

        <script>
            // Go to Step 1
            function goToStep1() {
                document.getElementById("step1").classList.remove("hidden");
                document.getElementById("step2").classList.add("hidden");
                document.getElementById("headerIcon").innerText = "A";
                document.getElementById("headerTitle").innerText = "Admin Panel";
                document.getElementById("headerSubtitle").innerText = "Sign in to manage SkillShop";
                document.getElementById("message").classList.add("hidden");
            }

            // Admin Login
            async function adminLogin(e) {
                e.preventDefault();
                const email = document.getElementById("email").value;
                const btn = document.getElementById("loginBtn");
                const msg = document.getElementById("message");

                btn.disabled = true;
                btn.innerText = "Sending...";
                msg.classList.add("hidden");

                const fd = new FormData();
                fd.append("email", email);

                try {
                    const res = await fetch("process/adminLoginProcess.php", {
                        method: "POST",
                        body: fd
                    });
                    const data = await res.json();

                    if (data.success) {

                        document.getElementById("step1").classList.add("hidden");
                        document.getElementById("step2").classList.remove("hidden");
                        document.getElementById("headerIcon").innerText = "🔒";
                        document.getElementById("headerTitle").innerText = "Verification";
                        document.getElementById("headerSubtitle").innerText = "Code sent to " + email;
                        document.getElementById("message").classList.add("hidden");

                        msg.className = "p-4 rounded-xl text-sm font-medium text-center bg-green-50 text-green-700 mt-6";
                        msg.innerText = data.message;
                        msg.classList.remove("hidden");

                    } else {
                        msg.className = "p-4 rounded-xl text-sm font-medium text-center bg-red-50 text-red-700 mt-6";
                        msg.innerText = data.message;
                        msg.classList.remove("hidden");
                    }

                } catch (err) {
                    alert(err)
                } finally {
                    btn.disabled = false;
                    btn.innerText = "Send Verification Code";
                }
            }

            // Admin Verification
            async function adminVerify(e) {
                e.preventDefault();
                const vcode = document.getElementById("vcode").value;
                const btn = document.getElementById("verifyBtn");
                const msg = document.getElementById("message");

                btn.disabled = true;
                btn.innerText = "Verifying...";
                msg.classList.add("hidden");

                const fd = new FormData();
                fd.append("vcode", vcode);

                try {
                    const res = await fetch("process/adminVerifyProcess.php", {
                        method: "POST",
                        body: fd
                    });
                    const data = await res.json();

                    if (data.success) {

                        msg.className = "p-4 rounded-xl text-sm font-medium text-center bg-green-50 text-green-700 mt-6";
                        msg.innerText = data.message;
                        msg.classList.remove("hidden");
                        setTimeout(() => {
                            window.location.href = "admin-dashboard.php";
                        }, 1000);

                    } else {
                        msg.className = "p-4 rounded-xl text-sm font-medium text-center bg-red-50 text-red-700 mt-6";
                        msg.innerText = data.message;
                        msg.classList.remove("hidden");
                    }

                } catch (err) {
                    alert(err)
                } finally {
                    btn.disabled = false;
                    btn.innerText = "Verify & Login";
                }
            }
        </script>

</body>

</html>