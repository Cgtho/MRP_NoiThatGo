<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['current_user'], $_SESSION['user_name'], $_SESSION['role'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';

$isManager = (int) $_SESSION['role'] === 0;
$isAdmin = (int) $_SESSION['role'] === 2;
$roleLabel = $isAdmin ? 'Quản trị hệ thống' : ($isManager ? 'Quản lý kho' : 'Nhân viên kho');
$roleIcon = $isAdmin ? 'fa-user-shield' : ($isManager ? 'fa-crown' : 'fa-user');

if ($isAdmin && basename($_SERVER['PHP_SELF']) !== 'employees.php') {
    header('Location: employees.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KHO & SẢN XUẤT</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome cho Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-custom::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-custom::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden text-sm">

<!-- TOP NAVBAR -->
<header class="bg-[#0f172a] text-white h-14 flex items-center justify-between px-4 shrink-0 border-b border-slate-700">
    <div class="flex items-center gap-3">
        <div class="bg-blue-600 text-white p-1.5 rounded text-lg"><i class="fa-solid fa-cubes"></i></div>
        <div>
            <h1 class="font-bold text-base leading-tight">KHO & SẢN XUẤT <span class="bg-blue-500 text-xs px-1.5 py-0.5 rounded ml-1">V1.0</span></h1>
            <p class="text-[10px] text-slate-400">Hệ thống định mức vật tư BOM, quản lý kho & lệnh sản xuất</p>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <?php if (!$isAdmin): ?>
        <div class="relative">
            <button id="notificationButton" type="button" data-notifications-url="../ajax/get_notifications.php" class="relative flex h-9 w-9 items-center justify-center rounded-md text-slate-300 transition hover:bg-slate-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400" aria-label="Mở thông báo" aria-expanded="false" aria-controls="notificationPanel">
                <i class="fa-regular fa-bell text-xl"></i>
                <span id="notificationBadge" class="absolute -right-0.5 -top-0.5 hidden min-w-4 rounded-full bg-amber-500 px-1 py-0.5 text-center text-[10px] font-bold leading-none text-white"></span>
            </button>
            <div id="notificationPanel" class="absolute right-0 top-11 z-50 hidden w-80 overflow-hidden rounded-lg border border-slate-200 bg-white text-slate-700 shadow-xl" role="dialog" aria-label="Thông báo">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <h2 class="font-semibold text-slate-800">Thông báo</h2>
                    <span id="notificationCount" class="text-xs text-slate-500">Đang tải...</span>
                </div>
                <div id="notificationList" class="max-h-[31rem] overflow-y-auto divide-y divide-slate-100">
                    <p class="px-4 py-5 text-center text-sm text-slate-500">Đang tải thông báo...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="flex items-center gap-2 ml-2">
            <div class="bg-slate-800 px-3 py-1.5 rounded-md border border-slate-600 flex items-center gap-2 text-amber-400">
                <i class="fa-solid <?= $roleIcon ?>"></i>
                <span><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <button id="openChangePasswordModal" type="button" class="bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-md flex items-center gap-2 text-white transition" title="Đổi mật khẩu">
                <i class="fa-solid fa-key"></i>
                <span class="hidden xl:inline">Đổi mật khẩu</span>
            </button>
            <form id="logoutForm" action="../ajax/logout.php" method="post">
                <button type="submit" class="bg-rose-600 hover:bg-rose-500 px-3 py-1.5 rounded-md flex items-center gap-2 text-white transition" title="Đăng xuất">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="hidden xl:inline">Đăng xuất</span>
                </button>
            </form>
        </div>
    </div>
</header>

<div id="changePasswordModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 items-center justify-center px-4">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="changePasswordTitle">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 id="changePasswordTitle" class="font-bold text-slate-800"><i class="fa-solid fa-key text-blue-600 mr-2"></i>Đổi mật khẩu</h2>
            <button id="closeChangePasswordModal" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="changePasswordForm" action="../ajax/change_password.php" method="post" class="p-5">
            <?= csrf_field() ?>
            <label for="currentPassword" class="mb-1.5 block text-sm font-medium text-slate-700">Mật khẩu hiện tại</label>
            <input id="currentPassword" name="currentPassword" type="password" autocomplete="current-password" required class="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <label for="newPassword" class="mb-1.5 block text-sm font-medium text-slate-700">Mật khẩu mới</label>
            <input id="newPassword" name="newPassword" type="password" minlength="6" autocomplete="new-password" required class="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <label for="confirmPassword" class="mb-1.5 block text-sm font-medium text-slate-700">Nhập lại mật khẩu mới</label>
            <input id="confirmPassword" name="confirmPassword" type="password" minlength="6" autocomplete="new-password" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <p id="changePasswordMessage" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button id="cancelChangePassword" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Cập nhật mật khẩu</button>
            </div>
        </form>
    </div>
</div>

<div class="flex flex-1 overflow-hidden">
<?php include 'sidebar.php'; ?>