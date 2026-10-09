<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';
require_once '../includes/csrf.php';

$isManager = (int) ($_SESSION['role'] ?? 1) === 0;
$currentUser = (string) ($_SESSION['current_user'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$statusFilter = in_array($statusFilter, ['all', 'pending', 'approved'], true) ? $statusFilter : 'all';
$selectedCode = strtoupper(trim((string) ($_GET['maPX'] ?? '')));
$errorMessage = null;
$formError = null;
$materialRows = [];
$exportRows = [];
$detailRows = [];
$selectedExport = null;

$stringLength = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
};
$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_export') {
    csrf_require_json();
    $materialCodes = $_POST['maNVL'] ?? [];
    $quantities = $_POST['soLuong'] ?? [];
    $items = [];
    $note = trim((string) ($_POST['ghiChu'] ?? ''));

    if ($stringLength($note) > 255) {
        $formError = 'Ghi chú không được dài quá 255 ký tự.';
    } elseif (is_array($materialCodes) && is_array($quantities)) {
        foreach ($materialCodes as $index => $materialCode) {
            $materialCode = strtoupper(trim((string) $materialCode));
            $quantity = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_INT);
            if ($materialCode !== '' && $quantity !== false && $quantity > 0) {
                $items[$materialCode] = ($items[$materialCode] ?? 0) + $quantity;
            }
        }
        if ($items === []) {
            $formError = 'Vui lòng chọn ít nhất một nguyên vật liệu và nhập số lượng lớn hơn 0.';
        }
    } else {
        $formError = 'Dữ liệu phiếu xuất không hợp lệ.';
    }

    if ($formError === null) {
        try {
            $pdo->beginTransaction();
            $nextCode = (int) $pdo->query(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(maPX, 4) AS UNSIGNED)), 0) + 1
                 FROM PHIEUXUATNVL WHERE maPX REGEXP '^PXN[0-9]+$'"
            )->fetchColumn();
            $exportCode = 'PXN' . str_pad((string) $nextCode, 3, '0', STR_PAD_LEFT);

            $materialCheck = $pdo->prepare('SELECT maNVL FROM NGUYENVATLIEU WHERE maNVL = :maNVL LIMIT 1');
            foreach ($items as $materialCode => $quantity) {
                $materialCheck->execute(['maNVL' => $materialCode]);
                if (!$materialCheck->fetch()) {
                    throw new RuntimeException('Nguyên vật liệu ' . $materialCode . ' không tồn tại.');
                }
            }

            $insertExport = $pdo->prepare(
                'INSERT INTO PHIEUXUATNVL (maPX, ngayXuat, trangThai, maNV, maQL, ghiChu, loaiPhieu)
                 VALUES (:maPX, NOW(), 0, :maNV, NULL, :ghiChu, 0)'
            );
            $insertExport->execute([
                'maPX' => $exportCode,
                'maNV' => $currentUser,
                'ghiChu' => $note !== '' ? $note : null,
            ]);

            $insertDetail = $pdo->prepare(
                'INSERT INTO CHITIETPHIEUXUATNVL (maPX, maNVL, soLuong)
                 VALUES (:maPX, :maNVL, :soLuong)'
            );
            foreach ($items as $materialCode => $quantity) {
                $insertDetail->execute(['maPX' => $exportCode, 'maNVL' => $materialCode, 'soLuong' => $quantity]);
            }
            $pdo->commit();
            header('Location: export_materials.php?status=pending&maPX=' . urlencode($exportCode));
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $formError = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Không thể tạo phiếu xuất NVL. Vui lòng thử lại.';
        }
    }
}

try {
    $materialRows = $pdo->query(
        'SELECT nvl.maNVL, nvl.tenNVL, dvt.tenDVT, nvl.soLuong
         FROM NGUYENVATLIEU nvl INNER JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
         ORDER BY nvl.maNVL ASC'
    )->fetchAll();

    $listStatement = $pdo->query(
        'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, p.ngayDuyet, p.ghiChu,
                nv.hoTen AS tenNV, ql.hoTen AS tenQL,
                COUNT(ct.maNVL) AS soMatHang, COALESCE(SUM(ct.soLuong), 0) AS tongSoLuong
         FROM PHIEUXUATNVL p
         LEFT JOIN NHANVIEN nv ON nv.maNV = p.maNV
         LEFT JOIN NHANVIEN ql ON ql.maNV = p.maQL
         LEFT JOIN CHITIETPHIEUXUATNVL ct ON ct.maPX = p.maPX
         GROUP BY p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, p.ngayDuyet, p.ghiChu, nv.hoTen, ql.hoTen
         ORDER BY p.ngayXuat DESC, p.maPX DESC'
    );
    $allExportRows = $listStatement->fetchAll();
    $exportRows = array_values(array_filter($allExportRows, static function (array $row) use ($statusFilter): bool {
        return $statusFilter === 'all'
            || ($statusFilter === 'pending' && (int) $row['trangThai'] === 0)
            || ($statusFilter === 'approved' && (int) $row['trangThai'] === 1);
    }));

    if ($selectedCode !== '') {
        $detailStatement = $pdo->prepare(
            'SELECT p.maPX, p.ngayXuat, p.trangThai, p.maNV, p.maQL, p.ngayDuyet, p.ghiChu,
                    nv.hoTen AS tenNV, ql.hoTen AS tenQL,
                    ct.maNVL, ct.soLuong, nvl.tenNVL, dvt.tenDVT, nvl.soLuong AS tonKho
             FROM PHIEUXUATNVL p
             LEFT JOIN NHANVIEN nv ON nv.maNV = p.maNV
             LEFT JOIN NHANVIEN ql ON ql.maNV = p.maQL
             INNER JOIN CHITIETPHIEUXUATNVL ct ON ct.maPX = p.maPX
             INNER JOIN NGUYENVATLIEU nvl ON nvl.maNVL = ct.maNVL
             INNER JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
             WHERE p.maPX = :maPX
             ORDER BY ct.maNVL ASC'
        );
        $detailStatement->execute(['maPX' => $selectedCode]);
        $detailRows = $detailStatement->fetchAll();
        $selectedExport = $detailRows[0] ?? null;
    }

    if ($selectedExport === null && $exportRows !== []) {
        header('Location: export_materials.php?status=' . urlencode($statusFilter) . '&maPX=' . urlencode((string) $exportRows[0]['maPX']));
        exit;
    }
} catch (PDOException $exception) {
    $errorMessage = 'Không thể tải dữ liệu phiếu xuất NVL từ cơ sở dữ liệu.';
}

$allCount = count($allExportRows ?? []);
$pendingCount = count(array_filter($allExportRows ?? [], static fn (array $row): bool => (int) $row['trangThai'] === 0));
$approvedCount = count(array_filter($allExportRows ?? [], static fn (array $row): bool => (int) $row['trangThai'] === 1));
require_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3"><div class="bg-orange-100 text-orange-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-dolly"></i></div><div><h2 class="text-xl font-bold text-slate-800">Xuất Kho Nguyên Vật Liệu</h2><p class="text-xs text-slate-500">Nhân viên hoặc quản lý lập phiếu, quản lý duyệt trước khi trừ tồn kho.</p></div></div>
    <button id="btn-open-export" type="button" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2"><i class="fa-solid fa-plus"></i> Tạo Phiếu Xuất NVL</button>
</div>
<?php if ($formError !== null): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($formError) ?></div><?php endif; ?>
<?php if ($errorMessage !== null): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($errorMessage) ?></div><?php endif; ?>

<div class="flex items-center gap-2 mb-4 border-b border-slate-200 pb-2">
    <?php $filters = ['all' => ['Tất cả', $allCount, ''], 'pending' => ['Chưa duyệt', $pendingCount, 'fa-regular fa-clock'], 'approved' => ['Đã duyệt', $approvedCount, 'fa-regular fa-circle-check']]; foreach ($filters as $filterKey => [$label, $count, $icon]): ?><a href="?status=<?= $filterKey ?>" class="<?= $statusFilter === $filterKey ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-1.5 rounded-full flex items-center gap-2 transition"><?php if ($icon): ?><i class="<?= $icon ?>"></i><?php endif; ?><?= $escape($label) ?> (<?= $count ?>)</a><?php endforeach; ?>
</div>

<div class="flex gap-6 items-start"><div class="w-1/3 shrink-0 space-y-3">
    <?php if ($exportRows === []): ?><div class="bg-white border border-slate-200 rounded-xl p-5 text-sm text-slate-500">Không có phiếu xuất NVL trong trạng thái này.</div><?php endif; ?>
    <?php foreach ($exportRows as $row): $rowApproved = (int) $row['trangThai'] === 1; ?><a href="?status=<?= $statusFilter ?>&maPX=<?= urlencode((string) $row['maPX']) ?>" class="block bg-white <?= (string) $row['maPX'] === $selectedCode ? 'border-2 border-orange-400' : 'border border-slate-200 hover:border-orange-300' ?> rounded-xl p-4 shadow-sm transition"><div class="flex justify-between items-start mb-2"><div class="flex items-center gap-2"><span class="bg-slate-100 text-slate-700 text-xs font-bold px-2 py-0.5 rounded"><?= $escape($row['maPX']) ?></span><span class="<?= $rowApproved ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?> text-[10px] font-medium px-2 py-0.5 rounded"><?= $rowApproved ? 'Đã duyệt, đã trừ kho' : 'Chưa duyệt' ?></span></div><?php if (!$rowApproved && $isManager): ?><span class="bg-red-50 text-red-600 text-[10px] font-medium px-2 py-0.5 rounded border border-red-100">Cần duyệt</span><?php endif; ?></div><div class="text-sm text-slate-700 mb-1 font-medium"><?= (int) $row['soMatHang'] ?> mặt hàng, tổng <?= (int) $row['tongSoLuong'] ?></div><div class="text-[11px] text-slate-400"><?= $escape($row['ngayXuat']) ?> &bull; Lập bởi: <?= $escape($row['tenNV'] ?: $row['maNV']) ?></div></a><?php endforeach; ?>
</div>

<div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
    <?php if ($selectedExport === null): ?><div class="py-12 text-center text-sm text-slate-500">Chọn một phiếu để xem chi tiết.</div><?php else: $approved = (int) $selectedExport['trangThai'] === 1; ?>
        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4"><div><div class="flex items-center gap-3 mb-3"><span class="bg-orange-50 text-orange-700 font-bold text-xs px-2 py-1 rounded border border-orange-100">MÃ PHIẾU: <?= $escape($selectedExport['maPX']) ?></span><span class="text-slate-400 text-xs"><i class="fa-regular fa-calendar"></i> <?= $escape($selectedExport['ngayXuat']) ?></span></div><div class="grid grid-cols-2 gap-4 mb-2"><div><div class="text-[11px] text-slate-400">Nhân viên lập:</div><div class="text-sm font-semibold text-slate-800"><?= $escape($selectedExport['tenNV'] ?: $selectedExport['maNV']) ?></div></div><div><div class="text-[11px] text-slate-400">Quản lý duyệt:</div><div class="text-sm font-semibold text-slate-800"><?= $escape($selectedExport['tenQL'] ?: ($approved ? $selectedExport['maQL'] : 'Chưa duyệt')) ?></div></div></div><div class="text-sm text-slate-600">Ghi chú / mục đích: <span class="font-medium text-slate-800"><?= $escape($selectedExport['ghiChu'] ?: 'Không có') ?></span></div></div><span class="border <?= $approved ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-amber-200 bg-amber-50 text-amber-700' ?> text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1"><i class="<?= $approved ? 'fa-regular fa-circle-check' : 'fa-regular fa-clock' ?>"></i> <?= $approved ? 'Đã duyệt, đã trừ kho' : 'Chờ quản lý duyệt' ?></span></div>
        <div class="mb-3"><h4 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH VẬT TƯ XUẤT</h4></div><div class="overflow-x-auto rounded-lg border border-slate-200"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold"><tr><th class="px-4 py-3 border-b border-slate-200">Mã NVL</th><th class="px-4 py-3 border-b border-slate-200">Tên nguyên vật liệu</th><th class="px-4 py-3 border-b border-slate-200">ĐVT</th><th class="px-4 py-3 text-center border-b border-slate-200">Số lượng xuất</th><th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th></tr></thead><tbody class="divide-y divide-slate-100 text-xs"><?php foreach ($detailRows as $detail): ?><tr class="hover:bg-slate-50 transition"><td class="px-4 py-3 font-bold text-slate-800"><?= $escape($detail['maNVL']) ?></td><td class="px-4 py-3 text-slate-700"><?= $escape($detail['tenNVL']) ?></td><td class="px-4 py-3 text-slate-500"><?= $escape($detail['tenDVT']) ?></td><td class="px-4 py-3 text-center font-bold text-orange-600">-<?= (int) $detail['soLuong'] ?></td><td class="px-4 py-3 text-right font-medium text-slate-700"><?= (int) $detail['tonKho'] ?> <?= $escape($detail['tenDVT']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php if ($isManager && !$approved): ?><form class="approve-export-form mt-5 flex justify-end" action="../ajax/approve_export_material.php" method="post"><input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>"><input type="hidden" name="maPX" value="<?= $escape($selectedExport['maPX']) ?>"><button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"><i class="fa-solid fa-check mr-1"></i> Duyệt và trừ tồn kho</button></form><?php endif; ?>
    <?php endif; ?>
</div></div>

<div id="modal-add-export" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4" role="dialog" aria-modal="true" aria-labelledby="modal-add-export-title"><div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl"><div class="mb-5 flex items-center justify-between"><h3 id="modal-add-export-title" class="text-lg font-bold text-slate-800">Tạo phiếu xuất nguyên vật liệu</h3><button type="button" class="close-export-modal text-2xl text-slate-400 hover:text-slate-700" aria-label="Đóng">&times;</button></div><form id="export-form" method="post"><input type="hidden" name="action" value="create_export"><input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>"><label class="block text-xs font-medium text-slate-700">Ghi chú / mục đích xuất<input name="ghiChu" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ví dụ: Cấp vật tư cho lệnh sản xuất"></label><div id="exportItems" class="mt-4 space-y-3"><div class="export-item flex items-end gap-2"><label class="min-w-0 flex-1 text-xs font-medium text-slate-700">Nguyên vật liệu<select name="maNVL[]" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">-- Chọn nguyên vật liệu --</option><?php foreach ($materialRows as $material): ?><option value="<?= $escape($material['maNVL']) ?>"><?= $escape($material['tenNVL']) ?> (<?= $escape($material['maNVL']) ?>) - Tồn: <?= (int) $material['soLuong'] ?> <?= $escape($material['tenDVT']) ?></option><?php endforeach; ?></select></label><label class="w-28 text-xs font-medium text-slate-700">Số lượng<input type="number" name="soLuong[]" min="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label><button type="button" class="remove-export-item hidden pb-2 text-lg text-rose-500" aria-label="Xóa vật tư">&times;</button></div></div><button type="button" id="addExportItem" class="mt-3 text-sm font-medium text-orange-600 hover:text-orange-800"><i class="fa-solid fa-plus"></i> Thêm vật tư</button><div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" class="close-export-modal rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Hủy</button><button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">Gửi quản lý duyệt</button></div></form></div></div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/export_materials.js"></script>
