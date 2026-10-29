<?php
session_start();

if (isset($_SESSION['current_user'], $_SESSION['role']) && (int) $_SESSION['role'] === 2) {
    header('Location: employees.php');
    exit;
}

if (isset($_SESSION['current_user'], $_SESSION['role'])) {
    header('Location: ../views/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị | KHO & SẢN XUẤT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-2xl text-blue-400">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h1 class="text-2xl font-bold">Khu vực quản trị</h1>
                <p class="mt-2 text-sm text-slate-500">Đăng nhập bằng tài khoản quản trị hệ thống</p>
            </div>

            <form id="loginForm" class="space-y-5" action="../ajax/auth_login.php" method="post" novalidate>
                <input type="hidden" name="loginScope" value="admin">
                <div>
                    <label for="employeeId" class="mb-2 block text-sm font-semibold">Mã nhân viên</label>
                    <input id="employeeId" name="employeeId" type="text" autocomplete="username" required class="h-12 w-full rounded-xl border border-slate-300 px-4 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <p id="employeeError" class="mt-1.5 hidden text-xs text-red-600">Vui lòng nhập mã nhân viên.</p>
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold">Mật khẩu</label>
                    <div class="flex items-center rounded-xl border border-slate-300 pr-3 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-100">
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="h-12 min-w-0 flex-1 rounded-xl border-0 px-4 outline-none">
                        <button id="togglePassword" type="button" aria-label="Hiện mật khẩu" class="p-1 text-slate-400 hover:text-slate-700"><i class="fa-regular fa-eye"></i></button>
                    </div>
                    <p id="passwordError" class="mt-1.5 hidden text-xs text-red-600">Vui lòng nhập mật khẩu.</p>
                </div>
                <button type="submit" class="flex h-12 w-full items-center justify-center gap-3 rounded-xl bg-slate-900 font-semibold text-white transition hover:bg-slate-700">
                    Đăng nhập quản trị <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
                <p id="loginMessage" class="hidden rounded-lg bg-red-50 px-4 py-3 text-center text-xs text-red-600" role="alert"></p>
            </form>
        </section>
    </main>
    <script src="../assets/js/admin/admin_login.js?v=1"></script>
</body>
</html>
