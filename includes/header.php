<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['current_user'], $_SESSION['user_name'], $_SESSION['role'])) {
    header('Location: ../index.php');
    exit;
}

$isManager = (int) $_SESSION['role'] === 0;
$roleLabel = $isManager ? 'Quản lý kho' : 'Nhân viên kho';
$roleIcon = $isManager ? 'fa-crown' : 'fa-user';
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
                <div id="notificationList" class="divide-y divide-slate-100">
                    <p class="px-4 py-5 text-center text-sm text-slate-500">Đang tải thông báo...</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2 ml-2">
            <div class="bg-slate-800 px-3 py-1.5 rounded-md border border-slate-600 flex items-center gap-2 text-amber-400">
                <i class="fa-solid <?= $roleIcon ?>"></i>
                <span><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <form id="logoutForm" action="../ajax/logout.php" method="post">
                <button type="submit" class="bg-rose-600 hover:bg-rose-500 px-3 py-1.5 rounded-md flex items-center gap-2 text-white transition" title="Đăng xuất">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="hidden xl:inline">Đăng xuất</span>
                </button>
            </form>
        </div>
    </div>
</header>

<div class="flex flex-1 overflow-hidden">
<?php include 'sidebar.php'; ?>