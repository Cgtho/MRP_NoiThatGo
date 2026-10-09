<?php
require_once '../includes/header.php';
require_once '../config.php';

$isManager = (int) ($_SESSION['role'] ?? 1) === 0;
$pendingTasks = 0;
$dashboardError = null;
$stats = ['materialKinds' => 0, 'materialQty' => 0, 'productKinds' => 0, 'productQty' => 0, 'pendingExports' => 0, 'orders' => 0];
$pendingExports = [];
$productionOrders = [];

try {
    if ($isManager) {
        $stmt = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM PHIEUXUATNVL WHERE maQL = :maQL_NVL AND trangThai = 0)
                +
                (SELECT COUNT(*) FROM PHIEUXUATTP WHERE maQL = :maQL_TP AND trangThai = 0)'
        );
        $stmt->execute(['maQL_NVL' => $_SESSION['current_user'], 'maQL_TP' => $_SESSION['current_user']]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM YEUCAU WHERE maNV = :maNV');
        $stmt->execute(['maNV' => $_SESSION['current_user']]);
    }
    $pendingTasks = (int) $stmt->fetchColumn();

    $stats['materialKinds'] = (int) $pdo->query('SELECT COUNT(*) FROM NGUYENVATLIEU')->fetchColumn();
    $stats['materialQty'] = (int) $pdo->query('SELECT COALESCE(SUM(soLuong), 0) FROM NGUYENVATLIEU')->fetchColumn();
    $stats['productKinds'] = (int) $pdo->query('SELECT COUNT(*) FROM THANHPHAM')->fetchColumn();
    $stats['productQty'] = (int) $pdo->query('SELECT COALESCE(SUM(soLuong), 0) FROM THANHPHAM')->fetchColumn();
    $stats['orders'] = (int) $pdo->query('SELECT COUNT(*) FROM YEUCAU')->fetchColumn();
    if ($isManager) {
        $stats['pendingExports'] = $pendingTasks;
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM PHIEUXUATNVL WHERE maNV = :maNV_nvl AND trangThai = 0)
                +
                (SELECT COUNT(*) FROM PHIEUXUATTP WHERE maNV = :maNV_tp AND trangThai = 0)'
        );
        $stmt->execute(['maNV_nvl' => $_SESSION['current_user'], 'maNV_tp' => $_SESSION['current_user']]);
        $stats['pendingExports'] = (int) $stmt->fetchColumn();
    }

    // Quản lý xem toàn bộ phiếu chờ; nhân viên chỉ xem phiếu do mình lập.
    $exportOwnerFilterNvl = $isManager ? '1 = 1' : 'p.maNV = :employeeNvl';
    $exportOwnerFilterTp = $isManager ? '1 = 1' : 'p.maNV = :employeeTp';
    $pendingStmt = $pdo->prepare(
        "SELECT p.maPX, p.ngayXuat, p.maNV, nv.hoTen, 'NVL' AS loai, COUNT(ct.maNVL) AS soDong
         FROM PHIEUXUATNVL p
         INNER JOIN NHANVIEN nv ON nv.maNV = p.maNV
         LEFT JOIN CHITIETPHIEUXUATNVL ct ON ct.maPX = p.maPX
         WHERE p.trangThai = 0 AND {$exportOwnerFilterNvl}
         GROUP BY p.maPX, p.ngayXuat, p.maNV, nv.hoTen
         UNION ALL
         SELECT p.maPX, p.ngayXuat, p.maNV, nv.hoTen, 'TP' AS loai, COUNT(ct.maTP) AS soDong
         FROM PHIEUXUATTP p
         INNER JOIN NHANVIEN nv ON nv.maNV = p.maNV
         LEFT JOIN CHITIETPHIEUXUATTP ct ON ct.maPX = p.maPX
         WHERE p.trangThai = 0 AND {$exportOwnerFilterTp}
         GROUP BY p.maPX, p.ngayXuat, p.maNV, nv.hoTen
         ORDER BY ngayXuat DESC LIMIT 5"
    );
    $pendingStmt->execute($isManager ? [] : [
        'employeeNvl' => $_SESSION['current_user'],
        'employeeTp' => $_SESSION['current_user'],
    ]);
    $pendingExports = $pendingStmt->fetchAll();

    $productionOrders = $pdo->query(
        "SELECT yc.maYC, yc.ngayYC, nv.hoTen,
                GROUP_CONCAT(CONCAT(tp.tenTP, ' (', ct.soLuong, ' ', tp.donViTinh, ')') SEPARATOR ', ') AS sanPham
         FROM YEUCAU yc
         INNER JOIN NHANVIEN nv ON nv.maNV = yc.maNV
         LEFT JOIN CHITIETYEUCAU ct ON ct.maYC = yc.maYC
         LEFT JOIN THANHPHAM tp ON tp.maTP = ct.maTP
         GROUP BY yc.maYC, yc.ngayYC, nv.hoTen
         ORDER BY yc.ngayYC DESC LIMIT 5"
    )->fetchAll();
} catch (PDOException $e) {
    $dashboardError = 'Không thể tải dữ liệu tổng quan từ cơ sở dữ liệu.';
}

function dashboardDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('d/m/Y H:i', $timestamp);
}
?>

<?php if ($dashboardError): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        <?= htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<!-- Banner Xin Chào -->
<div class="bg-[#0f172a] rounded-2xl p-6 mb-6 text-white shadow-lg relative overflow-hidden">
    <div class="relative z-10">
        <h2 class="text-2xl font-bold mb-1">Xin chào, <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?>!</h2>
        <?php if ($pendingTasks > 0): ?>
            <p class="text-slate-300 text-sm w-2/3">
                <?php if ($isManager): ?>
                    Bạn có <?= $pendingTasks ?> phiếu xuất từ nhân viên đang chờ phê duyệt.
                <?php else: ?>
                    Bạn có <?= $pendingTasks ?> lệnh sản xuất cần kiểm tra và thực hiện.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <button class="absolute top-6 right-6 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg
     font-medium shadow-md transition flex items-center gap-2 z-10" onclick="window.location.href='production_orders.php'">
        <i class="fa-solid fa-plus"></i> Tạo Lệnh Sản Xuất
    </button>
    <!-- Background Decoration -->
    <div class="absolute -right-10 -top-20 opacity-20"><i class="fa-solid fa-cubes text-9xl"></i></div>
</div>

<!-- 4 Thống kê -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <!-- Card 1 -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-blue-300 hover:-translate-y-1"
    onclick="window.location.href='inventory.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">NGUYÊN VẬT LIỆU</div>
            <i class="fa-solid fa-cubes text-blue-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= number_format($stats['materialKinds']) ?> <span class="text-sm font-normal text-slate-500">chủng loại</span></div>
            <div class="text-xs text-slate-400 mt-1"><?= number_format($stats['materialQty']) ?> tổng số lượng tồn kho</div>
        </div>
    </div>
    <!-- Card 2 -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-green-300 hover:-translate-y-1"
    onclick="window.location.href='inventory.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">THÀNH PHẨM LƯU KHO</div>
            <i class="fa-solid fa-box text-emerald-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= number_format($stats['productKinds']) ?> <span class="text-sm font-normal text-slate-500">sản phẩm</span></div>
            <div class="text-xs text-slate-400 mt-1"><?= number_format($stats['productQty']) ?> tổng số lượng tồn kho</div>
        </div>
    </div>
    <!-- Card 3 (Nổi bật) -->
    <div class="bg-amber-50 p-5 rounded-xl border border-amber-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-yellow-300 hover:-translate-y-1"
    onclick="window.location.href='export_materials.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-amber-700">XUẤT NVL CHỜ DUYỆT</div>
            <i class="fa-regular fa-clock text-amber-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-amber-700"><?= number_format($stats['pendingExports']) ?> <span class="text-sm font-normal">phiếu yêu cầu</span></div>
            <div class="text-xs text-amber-600 mt-1"><?= $isManager ? 'Nhân viên đang đợi duyệt cấp vật tư' : 'Phiếu của bạn đang chờ xử lý' ?></div>
        </div>
    </div>
    <!-- Card 4 -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-purple-300 hover:-translate-y-1"
    onclick="window.location.href='production_orders.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">LỆNH SẢN XUẤT</div>
            <i class="fa-regular fa-clipboard text-purple-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= number_format($stats['orders']) ?> <span class="text-sm font-normal text-slate-500">lệnh</span></div>
            <div class="text-xs text-slate-400 mt-1">Tổng số lệnh sản xuất trong hệ thống</div>
        </div>
    </div>
</div>

<!-- Khối Danh sách Phiếu chờ duyệt & Lệnh sản xuất -->
<div class="grid grid-cols-2 gap-6 mb-6">
    
    <!-- CỘT TRÁI: Phiếu Chờ Phê Duyệt -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col gap-4">
        <div class="flex justify-between items-center mb-1">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-regular fa-clock text-amber-500"></i> Phiếu Chờ Phê Duyệt (<?= count($pendingExports) ?>)
            </h3>
            <span class="text-sm text-slate-400"><?= $isManager ? 'Cần bạn phê duyệt' : 'Đang chờ xử lý' ?></span>
        </div>

        <?php if (!$pendingExports): ?>
            <div class="rounded-lg border border-slate-200 p-4 text-sm text-slate-500">Không có phiếu đang chờ xử lý.</div>
        <?php else: ?>
            <?php foreach ($pendingExports as $export): ?>
                <?php $isMaterialExport = $export['loai'] === 'NVL'; ?>
                <div class="border <?= $isMaterialExport ? 'border-amber-200' : 'border-blue-200' ?> rounded-lg p-4 flex justify-between items-center bg-white shadow-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="font-bold <?= $isMaterialExport ? 'text-amber-700' : 'text-blue-700' ?>"><?= htmlspecialchars($export['maPX'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="<?= $isMaterialExport ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' ?> text-[11px] font-medium px-2 py-0.5 rounded">Xuất <?= $isMaterialExport ? 'nguyên vật liệu' : 'thành phẩm' ?></span>
                        </div>
                        <div class="text-sm text-slate-700 mb-1">Số dòng chi tiết: <?= (int) $export['soDong'] ?></div>
                        <div class="text-[11px] text-slate-400">Ngày tạo: <?= htmlspecialchars(dashboardDate((string) $export['ngayXuat']), ENT_QUOTES, 'UTF-8') ?> &bull; Người tạo: <?= htmlspecialchars($export['hoTen'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <a href="<?= $isMaterialExport ? 'export_materials.php' : 'export_products.php' ?>?maPX=<?= urlencode((string) $export['maPX']) ?>"
                    class="<?= $isMaterialExport ? 'bg-[#e27a13] hover:bg-[#c96a0e]' : 'bg-blue-600 hover:bg-blue-700' ?> text-white text-sm font-medium px-4 py-2 rounded-lg transition shrink-0 shadow-sm">
                        <?= $isManager ? 'Duyệt ngay' : 'Xem phiếu' ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- CỘT PHẢI: Lệnh Sản Xuất Hiện Có -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col gap-4">
        <div class="flex justify-between items-center mb-1">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-regular fa-clipboard text-indigo-500"></i> Lệnh Sản Xuất Hiện Có (<?= count($productionOrders) ?>)
            </h3>
            <a href="production_orders.php" class="text-sm text-indigo-600 hover:underline">Xem tất cả &rarr;</a>
        </div>

        <?php if (!$productionOrders): ?>
            <div class="rounded-lg border border-slate-200 p-4 text-sm text-slate-500">Chưa có lệnh sản xuất.</div>
        <?php else: ?>
            <?php foreach ($productionOrders as $order): ?>
                <div class="border border-slate-200 rounded-lg p-4 flex justify-between items-center bg-white shadow-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($order['maYC'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="bg-indigo-100 text-indigo-700 text-[11px] font-medium px-2 py-0.5 rounded">Đã tạo</span>
                        </div>
                        <div class="text-sm text-slate-700 mb-1"><?= htmlspecialchars($order['sanPham'] ?: 'Chưa có sản phẩm chi tiết', ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-[11px] text-slate-400">Ngày tạo: <?= htmlspecialchars(dashboardDate((string) $order['ngayYC']), ENT_QUOTES, 'UTF-8') ?> &bull; Người yêu cầu: <?= htmlspecialchars($order['hoTen'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition shrink-0 border border-slate-200"
                    onclick="window.location.href='production_orders.php'">
                        Kiểm tra NVL
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>