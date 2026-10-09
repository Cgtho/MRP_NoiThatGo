<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

$isManager = (int) ($_SESSION['role'] ?? 1) === 0;
$currentUser = (string) ($_SESSION['current_user'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$statusFilter = in_array($statusFilter, ['all', 'pending', 'approved'], true) ? $statusFilter : 'all';
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
        'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL,
                nv.hoTen AS tenNV, ql.hoTen AS tenQL,
                COUNT(ct.maTP) AS soMatHang
         FROM PHIEUXUATTP p
         LEFT JOIN NHANVIEN nv ON nv.maNV = p.maNV
         LEFT JOIN NHANVIEN ql ON ql.maNV = p.maQL
         LEFT JOIN CHITIETPHIEUXUATTP ct ON ct.maPX = p.maPX
         WHERE ' . $visibilityColumn . ' = ' . $visibilityParam . '
         GROUP BY p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, nv.hoTen, ql.hoTen
         ORDER BY p.ngayXuat DESC, p.maPX DESC'
    );
    $listStatement->execute($isManager ? [] : ['maNV' => $currentUser]);
    $allExportRows = $listStatement->fetchAll();
    $exportRows = array_values(array_filter(
        $allExportRows,
        static function (array $row) use ($statusFilter): bool {
            return $statusFilter === 'all'
                || ($statusFilter === 'pending' && (int) $row['trangThai'] === 0)
                || ($statusFilter === 'approved' && (int) $row['trangThai'] === 1);
        }
    ));

    if ($selectedCode !== '') {
        $detailStatement = $pdo->prepare(
            'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL,
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
            'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL,
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

require_once '../includes/header.php';

$statusLabel = $selectedExport !== null && (int) $selectedExport['trangThai'] === 1
    ? 'Đã duyệt xuất bán (Đã trừ kho TP)'
    : 'Chờ quản lý duyệt';
$statusClass = $selectedExport !== null && (int) $selectedExport['trangThai'] === 1
    ? 'border-teal-200 bg-teal-50 text-teal-600'
    : 'border-amber-200 bg-amber-50 text-amber-700';
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

<div class="flex items-center gap-2 mb-4 border-b border-slate-200 pb-2">
    <?php
    $filters = [
        'all' => ['label' => 'Tất cả', 'count' => $allCount, 'icon' => ''],
        'pending' => ['label' => 'Chờ duyệt', 'count' => $pendingCount, 'icon' => 'fa-regular fa-clock'],
        'approved' => ['label' => 'Đã xuất bán', 'count' => $approvedCount, 'icon' => 'fa-regular fa-circle-check'],
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
</div>

<div class="flex gap-6 items-start">
    <div class="w-1/3 shrink-0 max-h-[520px] space-y-3 overflow-y-auto pr-2 scrollbar-custom">
        <?php if ($exportRows === []): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-sm text-slate-500">Không có phiếu xuất thành phẩm nào trong trạng thái này.</div>
        <?php else: ?>
            <?php foreach ($exportRows as $row): ?>
                <?php
                $isSelected = (string) $row['maPX'] === $selectedCode;
                $rowStatus = (int) $row['trangThai'] === 1;
                ?>
                <a href="?status=<?= $escape($statusFilter) ?>&maPX=<?= urlencode((string) $row['maPX']) ?>"
                   class="block bg-white <?= $isSelected ? 'border-2 border-teal-400' : 'border border-slate-200 hover:border-teal-300' ?> rounded-xl p-4 shadow-sm transition">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <span class="<?= $isSelected ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 border border-slate-200' ?> text-xs font-bold px-2 py-0.5 rounded"><?= $escape($row['maPX']) ?></span>
                            <span class="<?= $rowStatus ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700' ?> text-[10px] font-medium px-2 py-0.5 rounded"><?= $rowStatus ? 'Đã duyệt bán' : 'Chờ QL duyệt' ?></span>
                        </div>
                        <?php if (!$rowStatus && $isManager): ?><span class="bg-red-50 text-red-600 text-[10px] font-medium px-2 py-0.5 rounded border border-red-100">Cần duyệt</span><?php endif; ?>
                    </div>
                    <div class="text-sm text-slate-700 mb-1 font-medium"><?= (int) $row['soMatHang'] ?> mặt hàng thành phẩm</div>
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
                        <div><div class="text-[11px] text-slate-400">Quản lý phê duyệt:</div><div class="text-sm font-semibold text-slate-800"><?= $escape($selectedExport['tenQL'] ?: $selectedExport['maQL']) ?></div></div>
                    </div>
                </div>
                <span class="border <?= $statusClass ?> text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1 uppercase"><i class="<?= (int) $selectedExport['trangThai'] === 1 ? 'fa-regular fa-circle-check' : 'fa-regular fa-clock' ?>"></i> <?= $escape($statusLabel) ?></span>
            </div>
            <div class="mb-3"><h4 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH THÀNH PHẨM XUẤT BÁN</h4></div>
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold"><tr><th class="px-4 py-3 border-b border-slate-200">Mã TP</th><th class="px-4 py-3 border-b border-slate-200">Tên Thành Phẩm</th><th class="px-4 py-3 border-b border-slate-200">ĐVT</th><th class="px-4 py-3 text-center border-b border-slate-200">Số lượng xuất</th><th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th><th class="px-4 py-3 text-center border-b border-slate-200">Tình trạng kho</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($detailRows as $detail): ?>
                            <tr class="hover:bg-slate-50 transition"><td class="px-4 py-3 font-bold text-slate-800"><?= $escape($detail['maTP']) ?></td><td class="px-4 py-3 text-slate-700"><?= $escape($detail['tenTP']) ?></td><td class="px-4 py-3 text-slate-500"><?= $escape($detail['donViTinh']) ?></td><td class="px-4 py-3 text-center font-bold text-teal-600"><?= (int) $detail['soLuong'] ?></td><td class="px-4 py-3 text-right font-medium text-slate-700"><?= (int) $detail['tonKho'] ?> <?= $escape($detail['donViTinh']) ?></td><td class="px-4 py-3 text-center <?= (int) $selectedExport['trangThai'] === 1 ? 'text-teal-600' : 'text-amber-600' ?> font-medium"><?= (int) $selectedExport['trangThai'] === 1 ? 'Đã xuất bán' : 'Chờ duyệt' ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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

<?php require_once '../includes/footer.php'; ?>
<?php if (!$isManager): ?>
<script>
    const stockErrorToast = document.getElementById('stockErrorToast');
    const closeStockErrorToast = document.getElementById('closeStockErrorToast');
    if (stockErrorToast && closeStockErrorToast) {
        const hideStockErrorToast = () => stockErrorToast.remove();
        closeStockErrorToast.addEventListener('click', hideStockErrorToast);
        window.setTimeout(hideStockErrorToast, 10000);
    }

    const exportModal = document.getElementById('exportModal');
    const exportItems = document.getElementById('exportItems');
    const openExportModal = () => {
        exportModal.classList.remove('hidden');
        exportModal.classList.add('flex');
    };
    const closeExportModal = () => {
        exportModal.classList.add('hidden');
        exportModal.classList.remove('flex');
    };
    document.getElementById('openExportModal').addEventListener('click', openExportModal);
    document.getElementById('closeExportModal').addEventListener('click', closeExportModal);
    document.getElementById('cancelExportModal').addEventListener('click', closeExportModal);
    document.getElementById('addExportItem').addEventListener('click', () => {
        const item = exportItems.firstElementChild.cloneNode(true);
        item.querySelector('select').value = '';
        item.querySelector('input').value = '';
        item.querySelector('.remove-export-item').classList.remove('hidden');
        exportItems.appendChild(item);
    });
    exportItems.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-export-item')) {
            event.target.closest('.export-item').remove();
        }
    });
</script>
<?php endif; ?>
