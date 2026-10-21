<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/csrf.php';

$isManager = (int) ($_SESSION['role'] ?? 1) === 0;
if (!$isManager) {
    http_response_code(403);
    exit('Bạn không có quyền truy cập chức năng nhập kho nguyên vật liệu.');
}

$selectedCode = strtoupper(trim((string) ($_GET['maPN'] ?? '')));
$errorMessage = null;
$formError = null;
$materialRows = [];
$receiptRows = [];
$detailRows = [];
$selectedReceipt = null;

$stringLength = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
};

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_import' && !$isManager) {
    http_response_code(403);
    exit('Bạn không có quyền tạo phiếu nhập kho.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_import' && $isManager) {
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
        $formError = 'Dữ liệu phiếu nhập không hợp lệ.';
    }

    if ($formError === null) {
        try {
            $pdo->beginTransaction();
            $nextCode = (int) $pdo->query(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(maPN, 3) AS UNSIGNED)), 0) + 1
                 FROM PHIEUNHAPKHO WHERE maPN REGEXP '^PN[0-9]+$'"
            )->fetchColumn();
            $receiptCode = 'PN' . str_pad((string) $nextCode, 3, '0', STR_PAD_LEFT);

            $materialCheck = $pdo->prepare('SELECT maNVL FROM NGUYENVATLIEU WHERE maNVL = :maNVL AND trangThai = 1 LIMIT 1');
            foreach ($items as $materialCode => $quantity) {
                $materialCheck->execute(['maNVL' => $materialCode]);
                if (!$materialCheck->fetch()) {
                    throw new RuntimeException('Nguyên vật liệu ' . $materialCode . ' không tồn tại.');
                }
            }

            $insertReceipt = $pdo->prepare(
                'INSERT INTO PHIEUNHAPKHO (maPN, ngayNhap, ghiChu)
                 VALUES (:maPN, NOW(), :ghiChu)'
            );
            $insertReceipt->execute([
                'maPN' => $receiptCode,
                'ghiChu' => $note !== '' ? $note : null,
            ]);

            $insertDetail = $pdo->prepare(
                'INSERT INTO CHITIETPHIEUNHAP (maPN, maNVL, soLuong)
                 VALUES (:maPN, :maNVL, :soLuong)'
            );
            foreach ($items as $materialCode => $quantity) {
                $insertDetail->execute(['maPN' => $receiptCode, 'maNVL' => $materialCode, 'soLuong' => $quantity]);
            }
            $updateStock = $pdo->prepare(
                'UPDATE NGUYENVATLIEU SET soLuong = soLuong + :soLuong WHERE maNVL = :maNVL'
            );
            foreach ($items as $materialCode => $quantity) {
                $updateStock->execute(['soLuong' => $quantity, 'maNVL' => $materialCode]);
            }
            $pendingOrdersStatement = $pdo->query(
                'SELECT yc.maYC, bom.maNVL,
                        SUM(bom.soLuong * ct.soLuong) AS requiredAmount,
                        n.soLuong AS stockAmount
                 FROM YEUCAU yc
                 INNER JOIN CHITIETYEUCAU ct ON ct.maYC = yc.maYC
                 INNER JOIN CHITIETTHANHPHAM bom ON bom.maTP = ct.maTP
                 INNER JOIN NGUYENVATLIEU n ON n.maNVL = bom.maNVL
                 WHERE yc.trangThai = 0
                 GROUP BY yc.maYC, bom.maNVL, n.soLuong'
            );
            $pendingOrders = [];
            foreach ($pendingOrdersStatement->fetchAll() as $materialRequirement) {
                $orderCode = (string) $materialRequirement['maYC'];
                $pendingOrders[$orderCode] = $pendingOrders[$orderCode] ?? true;
                if ((float) $materialRequirement['requiredAmount'] > (float) $materialRequirement['stockAmount']) {
                    $pendingOrders[$orderCode] = false;
                }
            }
            $activateOrder = $pdo->prepare(
                'UPDATE YEUCAU SET trangThai = 1 WHERE maYC = :maYC AND trangThai = 0'
            );
            foreach ($pendingOrders as $orderCode => $hasEnoughMaterials) {
                if ($hasEnoughMaterials) {
                    $activateOrder->execute(['maYC' => $orderCode]);
                }
            }
            $markMaterialUsed = $pdo->prepare('UPDATE NGUYENVATLIEU SET isDeleted = 0 WHERE maNVL = :maNVL');
            foreach (array_keys($items) as $materialCode) {
                $markMaterialUsed->execute(['maNVL' => $materialCode]);
            }
            $pdo->commit();
            header('Location: import_materials.php?maPN=' . urlencode($receiptCode));
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $formError = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Không thể tạo phiếu nhập kho. Vui lòng thử lại.';
        }
    }
}

try {
    $materialRows = $pdo->query(
        'SELECT nvl.maNVL, nvl.tenNVL, dvt.tenDVT, nvl.soLuong
         FROM NGUYENVATLIEU nvl INNER JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
         WHERE nvl.trangThai = 1
         ORDER BY nvl.maNVL ASC'
    )->fetchAll();

    $listStatement = $pdo->prepare(
        'SELECT p.maPN, p.ngayNhap, p.ghiChu, COUNT(ct.maNVL) AS soMatHang,
                COALESCE(SUM(ct.soLuong), 0) AS tongSoLuong
         FROM PHIEUNHAPKHO p
         LEFT JOIN CHITIETPHIEUNHAP ct ON ct.maPN = p.maPN
         GROUP BY p.maPN, p.ngayNhap, p.ghiChu
         ORDER BY p.ngayNhap DESC, p.maPN DESC'
    );
    $listStatement->execute([]);
    $allReceiptRows = $listStatement->fetchAll();
    $receiptRows = $allReceiptRows;

    if ($selectedCode !== '') {
        $detailStatement = $pdo->prepare(
            'SELECT p.maPN, p.ngayNhap, p.ghiChu,
                    ct.maNVL, ct.soLuong, nvl.tenNVL, dvt.tenDVT, nvl.soLuong AS tonKho
             FROM PHIEUNHAPKHO p
             INNER JOIN CHITIETPHIEUNHAP ct ON ct.maPN = p.maPN
             INNER JOIN NGUYENVATLIEU nvl ON nvl.maNVL = ct.maNVL
             INNER JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
             WHERE p.maPN = :maPN
             ORDER BY ct.maNVL ASC'
        );
        $detailStatement->execute(['maPN' => $selectedCode]);
        $detailRows = $detailStatement->fetchAll();
        $selectedReceipt = $detailRows[0] ?? null;
    }

    if ($selectedReceipt === null && $receiptRows !== []) {
        header('Location: import_materials.php?maPN=' . urlencode((string) $receiptRows[0]['maPN']));
        exit;
    }
} catch (PDOException $exception) {
    $errorMessage = 'Không thể tải dữ liệu phiếu nhập kho từ cơ sở dữ liệu.';
}

$allCount = count($allReceiptRows ?? []);
require_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3"><div class="bg-blue-100 text-blue-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-box-open"></i></div><div><h2 class="text-xl font-bold text-slate-800">Nhập Kho Nguyên Vật Liệu</h2><p class="text-xs text-slate-500">Quản lý lập phiếu và cập nhật tồn kho.</p></div></div>
    <?php if ($isManager): ?><button id="btn-open-import" type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2"><i class="fa-solid fa-plus"></i> Tạo Phiếu Nhập Kho</button><?php endif; ?>
</div>
<?php if ($formError !== null): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($formError) ?></div><?php endif; ?>
<?php if ($errorMessage !== null): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($errorMessage) ?></div><?php endif; ?>

<div class="flex gap-6 items-start">
    <div class="w-1/3 shrink-0 space-y-3">
        <?php if ($receiptRows === []): ?><div class="bg-white border border-slate-200 rounded-xl p-5 text-sm text-slate-500">Chưa có phiếu nhập.</div><?php endif; ?>
        <?php foreach ($receiptRows as $row): ?><a href="?maPN=<?= urlencode((string) $row['maPN']) ?>" class="block bg-white <?= (string) $row['maPN'] === $selectedCode ? 'border-2 border-blue-400' : 'border border-slate-200 hover:border-blue-300' ?> rounded-xl p-4 shadow-sm transition"><div class="flex justify-between items-start mb-2"><span class="bg-slate-100 text-slate-700 text-xs font-bold px-2 py-0.5 rounded"><?= $escape($row['maPN']) ?></span></div><div class="text-sm text-slate-700 mb-1 font-medium"><?= (int) $row['soMatHang'] ?> mặt hàng, tổng <?= (int) $row['tongSoLuong'] ?></div><div class="text-[11px] text-slate-400"><?= $escape($row['ngayNhap']) ?></div></a><?php endforeach; ?>
    </div>

    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <?php if ($selectedReceipt === null): ?><div class="py-12 text-center text-sm text-slate-500">Chọn một phiếu để xem chi tiết.</div><?php else: ?>
            <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4"><div><div class="flex items-center gap-3 mb-3"><span class="bg-blue-50 text-blue-700 font-bold text-xs px-2 py-1 rounded border border-blue-100">MÃ PHIẾU: <?= $escape($selectedReceipt['maPN']) ?></span><span class="text-slate-400 text-xs"><i class="fa-regular fa-calendar"></i> <?= $escape($selectedReceipt['ngayNhap']) ?></span></div><div class="text-sm text-slate-600">Ghi chú: <span class="font-medium text-slate-800"><?= $escape($selectedReceipt['ghiChu'] ?: 'Không có') ?></span></div></div><span class="border border-emerald-200 bg-emerald-50 text-emerald-600 text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1"><i class="fa-regular fa-circle-check"></i> Đã cập nhật tồn kho</span></div>
            <div class="mb-3"><h4 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH VẬT TƯ NHẬP</h4></div><div class="overflow-x-auto rounded-lg border border-slate-200"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold"><tr><th class="px-4 py-3 border-b border-slate-200">Mã NVL</th><th class="px-4 py-3 border-b border-slate-200">Tên nguyên vật liệu</th><th class="px-4 py-3 border-b border-slate-200">ĐVT</th><th class="px-4 py-3 text-center border-b border-slate-200">Số lượng nhập</th><th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th></tr></thead><tbody class="divide-y divide-slate-100 text-xs"><?php foreach ($detailRows as $detail): ?><tr class="hover:bg-slate-50 transition"><td class="px-4 py-3 font-bold text-slate-800"><?= $escape($detail['maNVL']) ?></td><td class="px-4 py-3 text-slate-700"><?= $escape($detail['tenNVL']) ?></td><td class="px-4 py-3 text-slate-500"><?= $escape($detail['tenDVT']) ?></td><td class="px-4 py-3 text-center font-bold text-blue-600">+<?= (int) $detail['soLuong'] ?></td><td class="px-4 py-3 text-right font-medium text-slate-700"><?= (int) $detail['tonKho'] ?> <?= $escape($detail['tenDVT']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<div id="modal-add-import" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4" role="dialog" aria-modal="true" aria-labelledby="modal-add-import-title"><div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl"><div class="mb-5 flex items-center justify-between"><h3 id="modal-add-import-title" class="text-lg font-bold text-slate-800">Tạo phiếu nhập kho</h3><button type="button" class="close-import-modal text-2xl text-slate-400 hover:text-slate-700" aria-label="Đóng">&times;</button></div><form id="import-form" method="post"><input type="hidden" name="action" value="create_import"><input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>"><label class="block text-xs font-medium text-slate-700">Ghi chú / nhà cung cấp<input name="ghiChu" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ví dụ: Nhà cung cấp Gỗ Việt"></label><div id="importItems" class="mt-4 space-y-3"><div class="import-item flex items-end gap-2"><label class="min-w-0 flex-1 text-xs font-medium text-slate-700">Nguyên vật liệu<select name="maNVL[]" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">-- Chọn nguyên vật liệu --</option><?php foreach ($materialRows as $material): ?><option value="<?= $escape($material['maNVL']) ?>"><?= $escape($material['tenNVL']) ?> (<?= $escape($material['maNVL']) ?>) - Tồn: <?= (int) $material['soLuong'] ?> <?= $escape($material['tenDVT']) ?></option><?php endforeach; ?></select></label><label class="w-28 text-xs font-medium text-slate-700">Số lượng<input type="number" name="soLuong[]" min="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label><button type="button" class="remove-import-item hidden pb-2 text-lg text-rose-500" aria-label="Xóa vật tư">&times;</button></div></div><button type="button" id="addImportItem" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800"><i class="fa-solid fa-plus"></i> Thêm vật tư</button><div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" class="close-import-modal rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Hủy</button><button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Tạo phiếu</button></div></form></div></div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/import_materials.js"></script>
