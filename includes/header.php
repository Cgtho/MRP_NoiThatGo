<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Giả lập user đang đăng nhập
$_SESSION['current_user'] = 'NV01'; 
$_SESSION['role'] = 0; // 0: Quản lý
$_SESSION['user_name'] = 'Nguyễn Văn Quản';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KHO & SẢN XUẤT ERP V2.4</title>
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
            <h1 class="font-bold text-base leading-tight">KHO & SẢN XUẤT <span class="bg-blue-500 text-xs px-1.5 py-0.5 rounded ml-1">ERP V2.4</span></h1>
            <p class="text-[10px] text-slate-400">Hệ thống định mức vật tư BOM, quản lý kho & lệnh sản xuất</p>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <button class="bg-slate-800 hover:bg-slate-700 text-emerald-400 px-3 py-1.5 rounded-md border border-slate-600 flex items-center gap-2 transition">
            <i class="fa-solid fa-database"></i> CSDL & Code PHP
        </button>
        
        <div class="relative cursor-pointer">
            <i class="fa-regular fa-bell text-xl text-slate-300 hover:text-white"></i>
            <span class="absolute -top-1 -right-1 bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">2</span>
        </div>

        <div class="flex items-center gap-2 border-l border-slate-700 pl-4">
            <i class="fa-regular fa-circle-user text-2xl text-slate-300"></i>
            <div class="leading-tight">
                <div class="font-semibold text-sm"><?= $_SESSION['user_name'] ?></div>
                <div class="text-[11px] text-slate-400">Quản lý kho (vaiTro = 0)</div>
            </div>
        </div>
        
        <button class="bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded-md border border-slate-600 flex items-center gap-2 text-amber-400 transition ml-2">
            <i class="fa-solid fa-crown"></i> QL: <?= $_SESSION['user_name'] ?> (<?= $_SESSION['current_user'] ?>) <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
        </button>
    </div>
</header>

<div class="flex flex-1 overflow-hidden">
<?php include 'sidebar.php'; ?>