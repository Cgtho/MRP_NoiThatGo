<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$navItemClass = static function (string $page) use ($currentPage): string {
    return $currentPage === $page
        ? 'flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-600 text-white font-medium'
        : 'flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition';
};
?>

<!-- SIDEBAR -->
<aside class="w-64 bg-[#0f172a] shrink-0 flex flex-col border-r border-slate-700 overflow-y-auto scrollbar-custom">
    <div class="p-4 border-b border-slate-700">
        <div class="flex items-center gap-3 text-slate-300 bg-slate-800 p-2.5 rounded-lg border border-slate-700">
            <i class="fa-solid fa-shield-halved text-blue-400 text-xl"></i>
            <div class="leading-tight">
                <div class="text-[10px] text-slate-400 uppercase">Đang thao tác với quyền:</div>
                <div class="font-semibold text-sm text-white">Quản lý kho</div>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-6">
        <!-- HỆ THỐNG -->
        <div>
            <div class="text-[11px] font-bold text-slate-500 mb-2 px-3">HỆ THỐNG</div>
            <a href="dashboard.php" class="<?= $navItemClass('dashboard.php') ?>" <?= $currentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-border-all w-5"></i> Tổng quan hệ thống
            </a>
        </div>

        <!-- NHIỆM VỤ QUẢN LÝ KHO -->
        <div>
            <div class="flex justify-between items-center mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500">NHIỆM VỤ QUẢN LÝ KHO</span>
                <span class="text-[10px] bg-blue-900 text-blue-300 px-1.5 py-0.5 rounded">Ưu tiên</span>
            </div>
            <ul class="space-y-1">
                <li>
                    <a href="bom_formula.php" class="<?= $navItemClass('bom_formula.php') ?>" <?= $currentPage === 'bom_formula.php' ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid fa-layer-group text-blue-400 w-5 text-center"></i> Định mức sản phẩm (BOM)
                    </a>
                </li>
                <li>
                    <a href="production_orders.php" class="<?= $navItemClass('production_orders.php') ?>" <?= $currentPage === 'production_orders.php' ? 'aria-current="page"' : '' ?>>
                        <i class="fa-regular fa-clipboard text-indigo-400 w-5 text-center"></i> Lệnh sản xuất (Yêu cầu)
                    </a>
                </li>
                <li>
                    <a href="import_materials.php" class="<?= $navItemClass('import_materials.php') ?>" <?= $currentPage === 'import_materials.php' ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid fa-box-open text-emerald-400 w-5 text-center"></i> Nhập kho NVL (Phiếu nhập)
                    </a>
                </li>
                <li>
                    <a href="export_materials.php" class="<?= $navItemClass('export_materials.php') ?>" <?= $currentPage === 'export_materials.php' ? 'aria-current="page"' : '' ?>>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-dolly text-amber-400 w-5 text-center"></i> Phê duyệt xuất NVL
                        </div>
                        <span class="bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded">1</span>
                    </a>
                </li>
                <li>
                    <a href="export_products.php" class="<?= $navItemClass('export_products.php') ?>" <?= $currentPage === 'export_products.php' ? 'aria-current="page"' : '' ?>>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-truck-fast text-emerald-500 w-5 text-center"></i> Phê duyệt xuất Thành phẩm
                        </div>
                        <span class="bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded">1</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?= $navItemClass('reports.php') ?>" <?= $currentPage === 'reports.php' ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid fa-chart-simple text-purple-400 w-5 text-center"></i> Báo cáo thống kê Kho
                    </a>
                </li>
            </ul>
        </div>

        <!-- TRA CỨU & DỮ LIỆU -->
        <div>
            <div class="text-[11px] font-bold text-slate-500 mb-2 px-3">TRA CỨU & DỮ LIỆU</div>
            <ul class="space-y-1">
                <li>
                    <a href="inventory.php" class="<?= $navItemClass('inventory.php') ?>" <?= $currentPage === 'inventory.php' ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid fa-cubes-stacked text-amber-500 w-5 text-center"></i> Tồn kho Vật tư & Sản phẩm
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition">
                        <i class="fa-solid fa-database text-emerald-500 w-5 text-center"></i> Cấu trúc CSDL & Code PHP
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>

<!-- MAIN CONTENT AREA -->
<main class="flex-1 overflow-y-auto bg-slate-50 p-6 scrollbar-custom">