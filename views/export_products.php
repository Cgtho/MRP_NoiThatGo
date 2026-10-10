<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';
require_once '../includes/csrf.php';

$isManager = (int) ($_SESSION['role'] ?? 1) === 0;
$currentUser = (string) ($_SESSION['current_user'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$statusFilter = in_array($statusFilter, ['all', 'pending', 'approved', 'rejected'], true) ? $statusFilter : 'all';
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$selectedCode = trim((string) ($_GET['maPX'] ?? ''));
$errorMessage = null;
$exportRows = [];
$detailRows = [];
$selectedExport = null;
$productRows = [];
$formError = null;
$stockError = false;

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$visibilityColumn = $isManager ? '1' : 'p.maNV';
$visibilityParam = $isManager ? '1' : ':maNV';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isManager && ($_POST['action'] ?? '') === 'create_export') {
    $productCodes = $_POST['maTP'] ?? [];
    $quantities = $_POST['soLuong'] ?? [];
    $items = [];

    if (is_array($productCodes) && is_array($quantities)) {
        foreach ($productCodes as $index => $productCode) {
            $productCode = trim((string) $productCode);
            $quantity = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_INT);
            if ($productCode !== '' && $quantity !== false && $quantity > 0) {
                $items[$productCode] = ($items[$productCode] ?? 0) + $quantity;
            }
        }
    }

    if ($items === []) {
        $formError = 'Vui lòng chọn ít nhất một thành phẩm và nhập số lượng lớn hơn 0.';
    } else {
        try {
            $pdo->beginTransaction();
            $productStatement = $pdo->prepare(
                'SELECT maTP, soLuong FROM THANHPHAM WHERE maTP = :maTP FOR UPDATE'
            );
            foreach ($items as $productCode => $quantity) {
                $productStatement->execute(['maTP' => $productCode]);
                $product = $productStatement->fetch();
                if ($product === false) {
                    throw new RuntimeException('Sản phẩm không tồn tại.');
                }
                if ((int) $product['soLuong'] < $quantity) {
                    $stockError = true;
                    throw new RuntimeException('Số lượng xuất vượt quá tồn kho của sản phẩm ' . $productCode . '.');
                }
            }

            $nextCode = (int) $pdo->query(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(maPX, 4) AS UNSIGNED)), 0) + 1
                 FROM PHIEUXUATTP
                 WHERE maPX REGEXP '^PXT[0-9]+$'"
            )->fetchColumn();
            $exportCode = 'PXT' . str_pad((string) $nextCode, 3, '0', STR_PAD_LEFT);

            $insertExport = $pdo->prepare(
                'INSERT INTO PHIEUXUATTP (maPX, ngayXuat, trangThai, maNV, maQL, loaiPhieu)
                 VALUES (:maPX, NOW(), 0, :maNV, :maQL, 1)'
            );
            $insertExport->execute([
                'maPX' => $exportCode,
                'maNV' => $currentUser,
                'maQL' => null,
            ]);

            $insertDetail = $pdo->prepare(
                'INSERT INTO CHITIETPHIEUXUATTP (maPX, maTP, soLuong)
                 VALUES (:maPX, :maTP, :soLuong)'
            );
            foreach ($items as $productCode => $quantity) {
                $insertDetail->execute([
                    'maPX' => $exportCode,
                    'maTP' => $productCode,
                    'soLuong' => $quantity,
                ]);
            }
            $pdo->commit();
            header('Location: export_products.php?status=pending&maPX=' . urlencode($exportCode));
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $formError = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Không thể tạo phiếu xuất thành phẩm. Vui lòng thử lại.';
        }
    }
}

try {
    if (!$isManager) {
        $productRows = $pdo->query(
            'SELECT maTP, tenTP, donViTinh, soLuong FROM THANHPHAM ORDER BY maTP ASC'
        )->fetchAll();
    }
    $listStatement = $pdo->prepare(
        'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, p.ngayDuyet, p.lyDoTuChoi,
                nv.hoTen AS tenNV, ql.hoTen AS tenQL,
                COUNT(ct.maTP) AS soMatHang, COALESCE(SUM(ct.soLuong), 0) AS tongSoLuong
         FROM PHIEUXUATTP p
         LEFT JOIN NHANVIEN nv ON nv.maNV = p.maNV
         LEFT JOIN NHANVIEN ql ON ql.maNV = p.maQL
         LEFT JOIN CHITIETPHIEUXUATTP ct ON ct.maPX = p.maPX
         WHERE ' . $visibilityColumn . ' = ' . $visibilityParam . '
         GROUP BY p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, p.ngayDuyet, p.lyDoTuChoi, nv.hoTen, ql.hoTen
         ORDER BY p.ngayXuat DESC, p.maPX DESC'
    );
    $listStatement->execute($isManager ? [] : ['maNV' => $currentUser]);
    $allExportRows = $listStatement->fetchAll();

    if ($searchQuery !== '') {
        $needle = function_exists('mb_strtolower') ? mb_strtolower($searchQuery, 'UTF-8') : strtolower($searchQuery);
        $allExportRows = array_values(array_filter(
            $allExportRows,
            static function (array $row) use ($needle): bool {
                $haystack = (string) $row['maPX'] . ' ' . (string) ($row['tenNV'] ?? '') . ' ' . (string) ($row['maNV'] ?? '');
                $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
                return strpos($haystack, $needle) !== false;
            }
        ));
    }

    $exportRows = array_values(array_filter(
        $allExportRows,
        static function (array $row) use ($statusFilter): bool {
            return $statusFilter === 'all'
                || ($statusFilter === 'pending' && (int) $row['trangThai'] === 0)
                || ($statusFilter === 'approved' && (int) $row['trangThai'] === 1)
                || ($statusFilter === 'rejected' && (int) $row['trangThai'] === 2);
        }
    ));

    if ($selectedCode !== '') {
        $detailStatement = $pdo->prepare(
            'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, p.ngayDuyet, p.lyDoTuChoi,
                    nv.hoTen AS tenNV, ql.hoTen AS tenQL,
                    ct.maTP, ct.soLuong, tp.tenTP, tp.donViTinh, tp.soLuong AS tonKho
             FROM PHIEUXUATTP p
             LEFT JOIN NHANVIEN nv ON nv.maNV = p.maNV
             LEFT JOIN NHANVIEN ql ON ql.maNV = p.maQL
             INNER JOIN CHITIETPHIEUXUATTP ct ON ct.maPX = p.maPX
             INNER JOIN THANHPHAM tp ON tp.maTP = ct.maTP
             WHERE p.maPX = :maPX AND ' . $visibilityColumn . ' = ' . $visibilityParam . '
             ORDER BY ct.maTP ASC'
        );
        $detailStatement->execute($isManager ? ['maPX' => $selectedCode] : [
            'maPX' => $selectedCode,
            'maNV' => $currentUser,
        ]);
        $detailRows = $detailStatement->fetchAll();
        $selectedExport = $detailRows[0] ?? null;
    }

    if ($selectedExport === null && $exportRows !== []) {
        $selectedCode = (string) $exportRows[0]['maPX'];
        $detailStatement = $pdo->prepare(
            'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, p.ngayDuyet, p.lyDoTuChoi,
                    nv.hoTen AS tenNV, ql.hoTen AS tenQL,
                    ct.maTP, ct.soLuong, tp.tenTP, tp.donViTinh, tp.soLuong AS tonKho
             FROM PHIEUXUATTP p
             LEFT JOIN NHANVIEN nv ON nv.maNV = p.maNV
             LEFT JOIN NHANVIEN ql ON ql.maNV = p.maQL
             INNER JOIN CHITIETPHIEUXUATTP ct ON ct.maPX = p.maPX
             INNER JOIN THANHPHAM tp ON tp.maTP = ct.maTP
             WHERE p.maPX = :maPX AND ' . $visibilityColumn . ' = ' . $visibilityParam . '
             ORDER BY ct.maTP ASC'
        );
        $detailStatement->execute($isManager ? ['maPX' => $selectedCode] : [
            'maPX' => $selectedCode,
            'maNV' => $currentUser,
        ]);
        $detailRows = $detailStatement->fetchAll();
        $selectedExport = $detailRows[0] ?? null;
    }
} catch (PDOException $exception) {
    $errorMessage = 'Không thể tải dữ liệu phiếu xuất thành phẩm từ cơ sở dữ liệu.';
}

$allCount = count($allExportRows ?? []);
$pendingCount = count(array_filter($allExportRows ?? [], static fn (array $row): bool => (int) $row['trangThai'] === 0));
$approvedCount = count(array_filter($allExportRows ?? [], static fn (array $row): bool => (int) $row['trangThai'] === 1));
$rejectedCount = count(array_filter($allExportRows ?? [], static fn (array $row): bool => (int) $row['trangThai'] === 2));

require_once '../includes/header.php';

$selectedStatus = $selectedExport !== null ? (int) $selectedExport['trangThai'] : 0;
$statusLabel = $selectedStatus === 1
    ? 'Đã duyệt xuất bán (Đã trừ kho TP)'
    : ($selectedStatus === 2 ? 'Đã từ chối' : 'Chờ quản lý duyệt');
$statusClass = $selectedStatus === 1
    ? 'border-teal-200 bg-teal-50 text-teal-600'
    : ($selectedStatus === 2 ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-amber-200 bg-amber-50 text-amber-700');
$statusIcon = $selectedStatus === 1
    ? 'fa-regular fa-circle-check'
    : ($selectedStatus === 2 ? 'fa-regular fa-circle-xmark' : 'fa-regular fa-clock');

// Kiểm tra tồn kho: chặn phê duyệt nếu có mặt hàng không đủ số lượng.
$stockShortage = false;
foreach ($detailRows as $detail) {
    if ((int) $detail['tonKho'] < (int) $detail['soLuong']) {
        $stockShortage = true;
        break;
    }
}
$canApprove = $isManager && $selectedExport !== null && $selectedStatus === 0 && !$stockShortage;
$canReject = $isManager && $selectedExport !== null && $selectedStatus === 0;
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-teal-100 text-teal-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-truck-fast"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Xuất Kho Thành Phẩm Để Bán</h2>
                <p class="text-xs text-slate-500">Khi có đơn hàng cần giao đi, nhân viên lập phiếu xuất thành phẩm gửi Quản lý duyệt.</p>
            </div>
        </div>
    </div>
    <?php if (!$isManager): ?>
        <button type="button" id="openExportModal" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Lập Phiếu Đề Nghị Xuất TP
        </button>
    <?php endif; ?>
</div>

<?php if ($formError !== null): ?>
    <?php if ($stockError): ?>
        <div id="stockErrorToast" class="fixed right-5 top-20 z-[60] flex w-full max-w-sm items-start gap-3 rounded-lg border border-red-200 bg-white p-4 text-sm text-red-700 shadow-lg" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-500"></i>
            <span class="flex-1"><?= $escape($formError) ?></span>
            <button type="button" id="closeStockErrorToast" class="text-lg leading-none text-slate-400 hover:text-slate-700" aria-label="Đóng thông báo">&times;</button>
        </div>
    <?php else: ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($formError) ?></div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($errorMessage) ?></div>
<?php endif; ?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4 border-b border-slate-200 pb-2">
    <?php
    $filters = [
        'all' => ['label' => 'Tất cả', 'count' => $allCount, 'icon' => ''],
        'pending' => ['label' => 'Chờ duyệt', 'count' => $pendingCount, 'icon' => 'fa-regular fa-clock'],
        'approved' => ['label' => 'Đã xuất bán', 'count' => $approvedCount, 'icon' => 'fa-regular fa-circle-check'],
        'rejected' => ['label' => 'Từ chối', 'count' => $rejectedCount, 'icon' => 'fa-regular fa-circle-xmark'],
    ];
    foreach ($filters as $filterKey => $filter):
        $active = $statusFilter === $filterKey;
        $url = '?status=' . $filterKey;
    ?>
        <a href="<?= $url ?>" class="<?= $active ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-1.5 rounded-full flex items-center gap-2 transition">
            <?php if ($filter['icon'] !== ''): ?><i class="<?= $filter['icon'] ?>"></i><?php endif; ?>
            <?= $escape($filter['label']) ?> (<?= (int) $filter['count'] ?>)
        </a>
    <?php endforeach; ?>

    <form method="get" class="flex items-center gap-2" role="search">
        <input type="hidden" name="status" value="<?= $escape($statusFilter) ?>">
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
            <input type="search" name="q" value="<?= $escape($searchQuery) ?>" placeholder="Tìm mã lệnh xuất / người yêu cầu"
                   class="w-64 rounded-lg border border-slate-300 py-1.5 pl-8 pr-3 text-[13px] focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" aria-label="Tìm kiếm">
        </div>
        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-[13px] font-medium text-white hover:bg-slate-700 transition">Tìm</button>
        <?php if ($searchQuery !== ''): ?>
            <a href="?status=<?= $escape($statusFilter) ?>" class="text-[13px] text-slate-500 hover:text-slate-800">Xóa lọc</a>
        <?php endif; ?>
    </form>
</div>

<div class="flex gap-6 items-start">
    <div class="w-1/3 shrink-0 max-h-[520px] space-y-3 overflow-y-auto pr-2 scrollbar-custom">
        <?php if ($exportRows === []): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-sm text-slate-500">Không có phiếu xuất thành phẩm nào trong trạng thái này.</div>
        <?php else: ?>
            <?php foreach ($exportRows as $row): ?>
                <?php
                $isSelected = (string) $row['maPX'] === $selectedCode;
                $rowStatus = (int) $row['trangThai'];
                $rowStatusClass = $rowStatus === 1
                    ? 'bg-teal-100 text-teal-700'
                    : ($rowStatus === 2 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
                $rowStatusLabel = $rowStatus === 1
                    ? 'Đã duyệt bán'
                    : ($rowStatus === 2 ? 'Đã từ chối' : 'Chờ QL duyệt');
                $rowUrl = '?status=' . urlencode($statusFilter) . '&maPX=' . urlencode((string) $row['maPX']);
                if ($searchQuery !== '') {
                    $rowUrl .= '&q=' . urlencode($searchQuery);
                }
                ?>
                <a href="<?= $rowUrl ?>"
                   class="block bg-white <?= $isSelected ? 'border-2 border-teal-400' : 'border border-slate-200 hover:border-teal-300' ?> rounded-xl p-4 shadow-sm transition">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <span class="<?= $isSelected ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 border border-slate-200' ?> text-xs font-bold px-2 py-0.5 rounded"><?= $escape($row['maPX']) ?></span>
                            <span class="<?= $rowStatusClass ?> text-[10px] font-medium px-2 py-0.5 rounded"><?= $escape($rowStatusLabel) ?></span>
                        </div>
                        <?php if ($rowStatus === 0 && $isManager): ?><span class="bg-red-50 text-red-600 text-[10px] font-medium px-2 py-0.5 rounded border border-red-100">Cần duyệt</span><?php endif; ?>
                    </div>
                    <div class="text-sm text-slate-700 mb-1 font-medium"><?= (int) $row['soMatHang'] ?> mặt hàng thành phẩm &bull; Tổng SL: <?= (int) $row['tongSoLuong'] ?></div>
                    <div class="text-[11px] text-slate-400"><?= $escape($row['ngayXuat']) ?> &bull; Lập bởi: <?= $escape($row['tenNV'] ?: $row['maNV']) ?></div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <?php if ($selectedExport === null): ?>
            <div class="py-12 text-center text-sm text-slate-500">Chọn một phiếu để xem chi tiết.</div>
        <?php else: ?>
            <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-teal-50 text-teal-700 font-bold text-xs px-2 py-1 rounded border border-teal-100">MÃ PHIẾU XUẤT TP: <?= $escape($selectedExport['maPX']) ?></span>
                        <span class="text-slate-400 text-xs flex items-center gap-1"><i class="fa-regular fa-calendar"></i> <?= $escape($selectedExport['ngayXuat']) ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-2">
                        <div><div class="text-[11px] text-slate-400">Nhân viên đề xuất:</div><div class="text-sm font-semibold text-slate-800"><?= $escape($selectedExport['tenNV'] ?: $selectedExport['maNV']) ?></div></div>
                        <div><div class="text-[11px] text-slate-400">Quản lý phê duyệt:</div><div class="text-sm font-semibold text-slate-800"><?= $escape($selectedExport['tenQL'] ?: ($selectedStatus === 0 ? 'Chưa duyệt' : $selectedExport['maQL'])) ?></div></div>
                    </div>
                    <?php if ($selectedStatus !== 0 && !empty($selectedExport['ngayDuyet'])): ?>
                        <div class="text-[11px] text-slate-400">Thời gian xử lý: <span class="font-medium text-slate-600"><?= $escape($selectedExport['ngayDuyet']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($selectedStatus === 2 && !empty($selectedExport['lyDoTuChoi'])): ?>
                        <div class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"><i class="fa-solid fa-circle-exclamation mr-1"></i>Lý do từ chối: <span class="font-medium"><?= $escape($selectedExport['lyDoTuChoi']) ?></span></div>
                    <?php endif; ?>
                </div>
                <span class="border <?= $statusClass ?> text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1 uppercase"><i class="<?= $statusIcon ?>"></i> <?= $escape($statusLabel) ?></span>
            </div>
            <?php if ($stockShortage && $selectedStatus === 0): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Tồn kho hiện tại không đủ để duyệt phiếu này. Vui lòng kiểm tra các mặt hàng được đánh dấu đỏ bên dưới.
                </div>
            <?php endif; ?>
            <div class="mb-3"><h4 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH THÀNH PHẨM XUẤT BÁN</h4></div>
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold"><tr><th class="px-4 py-3 border-b border-slate-200">Mã TP</th><th class="px-4 py-3 border-b border-slate-200">Tên Thành Phẩm</th><th class="px-4 py-3 border-b border-slate-200">ĐVT</th><th class="px-4 py-3 text-center border-b border-slate-200">Số lượng xuất</th><th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th><th class="px-4 py-3 text-center border-b border-slate-200">Tình trạng kho</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($detailRows as $detail): ?>
                            <?php $isShort = (int) $detail['tonKho'] < (int) $detail['soLuong']; ?>
                            <tr class="hover:bg-slate-50 transition"><td class="px-4 py-3 font-bold text-slate-800"><?= $escape($detail['maTP']) ?></td><td class="px-4 py-3 text-slate-700"><?= $escape($detail['tenTP']) ?></td><td class="px-4 py-3 text-slate-500"><?= $escape($detail['donViTinh']) ?></td><td class="px-4 py-3 text-center font-bold text-teal-600"><?= (int) $detail['soLuong'] ?></td><td class="px-4 py-3 text-right font-medium <?= $isShort && $selectedStatus === 0 ? 'text-red-600' : 'text-slate-700' ?>"><?= (int) $detail['tonKho'] ?> <?= $escape($detail['donViTinh']) ?><?= $isShort && $selectedStatus === 0 ? ' <i class="fa-solid fa-triangle-exclamation"></i>' : '' ?></td><td class="px-4 py-3 text-center <?= $selectedStatus === 1 ? 'text-teal-600' : ($selectedStatus === 2 ? 'text-rose-600' : 'text-amber-600') ?> font-medium"><?= $selectedStatus === 1 ? 'Đã xuất bán' : ($selectedStatus === 2 ? 'Đã từ chối' : 'Chờ duyệt') ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($selectedStatus === 0 && $isManager): ?>
                <div class="mt-5 flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" id="openRejectBtn" class="rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition">
                        <i class="fa-solid fa-ban mr-1"></i> Từ chối
                    </button>
                    <?php if ($stockShortage): ?>
                        <button type="button" disabled class="cursor-not-allowed rounded-lg bg-slate-300 px-4 py-2 text-sm font-medium text-white" title="Tồn kho không đủ để phê duyệt">
                            <i class="fa-solid fa-check mr-1"></i> Phê duyệt
                        </button>
                    <?php else: ?>
                        <form id="approveForm" action="../ajax/approve_export_product.php" method="post" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>">
                            <input type="hidden" name="maPX" value="<?= $escape($selectedExport['maPX']) ?>">
                            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition">
                                <i class="fa-solid fa-check mr-1"></i> Phê duyệt &amp; trừ kho
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isManager): ?>
<div id="exportModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4" role="dialog" aria-modal="true" aria-labelledby="exportModalTitle">
    <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between">
            <h3 id="exportModalTitle" class="text-lg font-bold text-slate-800">Lập Phiếu Đề Nghị Xuất Thành Phẩm Bán</h3>
            <button type="button" id="closeExportModal" class="text-2xl text-slate-400 hover:text-slate-700" aria-label="Đóng">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="create_export">
            <div id="exportItems" class="space-y-3">
                <div class="export-item flex items-end gap-2">
                    <label class="min-w-0 flex-1 text-xs font-medium text-slate-700">Sản phẩm xuất bán
                        <select name="maTP[]" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">-- Chọn thành phẩm --</option>
                            <?php foreach ($productRows as $product): ?>
                                <option value="<?= $escape($product['maTP']) ?>">
                                    <?= $escape($product['tenTP']) ?> (<?= $escape($product['maTP']) ?>) - Tồn: <?= (int) $product['soLuong'] ?> <?= $escape($product['donViTinh']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="w-28 text-xs font-medium text-slate-700">Số lượng
                        <input type="number" name="soLuong[]" min="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <button type="button" class="remove-export-item hidden pb-2 text-lg text-rose-500" aria-label="Xóa sản phẩm">&times;</button>
                </div>
            </div>
            <button type="button" id="addExportItem" class="mt-3 text-sm font-medium text-teal-600 hover:text-teal-800">
                <i class="fa-solid fa-plus"></i> Thêm sản phẩm
            </button>
            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" id="cancelExportModal" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Hủy</button>
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Gửi Lên Quản Lý Duyệt</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($isManager): ?>
<div id="confirmApproveModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/60 px-4" role="dialog" aria-modal="true" aria-labelledby="confirmApproveTitle">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center gap-3">
            <div class="bg-teal-100 text-teal-600 p-2 rounded-lg"><i class="fa-solid fa-circle-question text-lg"></i></div>
            <h3 id="confirmApproveTitle" class="text-lg font-bold text-slate-800">Xác nhận phê duyệt</h3>
        </div>
        <p class="text-sm text-slate-600">Bạn có chắc chắn muốn phê duyệt phiếu xuất <strong id="confirmApproveCode" class="text-slate-800"><?= $escape($selectedExport['maPX'] ?? '') ?></strong>? Hành động này sẽ trừ tồn kho thành phẩm tương ứng và không thể hoàn tác.</p>
        <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
            <button type="button" id="cancelConfirmApprove" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Hủy</button>
            <button type="button" id="confirmApproveBtn" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                <i class="fa-solid fa-check mr-1"></i> Xác nhận duyệt
            </button>
        </div>
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/60 px-4" role="dialog" aria-modal="true" aria-labelledby="rejectModalTitle">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-rose-100 text-rose-600 p-2 rounded-lg"><i class="fa-solid fa-ban text-lg"></i></div>
                <h3 id="rejectModalTitle" class="text-lg font-bold text-slate-800">Từ chối phiếu xuất</h3>
            </div>
            <button type="button" id="closeReject" class="text-2xl text-slate-400 hover:text-slate-700" aria-label="Đóng">&times;</button>
        </div>
        <form id="rejectForm" action="../ajax/reject_export_product.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>">
            <input type="hidden" name="maPX" value="<?= $escape($selectedExport['maPX'] ?? '') ?>">
            <label class="block text-xs font-medium text-slate-700" for="rejectReason">Lý do từ chối
                <textarea id="rejectReason" name="lyDoTuChoi" rows="3" maxlength="255" required
                          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"
                          placeholder="Nhập lý do từ chối phiếu xuất này..."></textarea>
            </label>
            <p id="rejectReasonError" class="mt-2 hidden text-xs text-rose-600">Vui lòng nhập lý do từ chối trước khi gửi.</p>
            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" id="cancelReject" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Hủy</button>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Gửi từ chối
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/export_products.js"></script>

