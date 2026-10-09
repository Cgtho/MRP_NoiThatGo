<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

$isManager = (int) ($_SESSION['role'] ?? 1) === 0;
$currentUser = (string) ($_SESSION['current_user'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$statusFilter = in_array($statusFilter, ['all', 'pending', 'working', 'completed'], true) ? $statusFilter : 'all';
$selectedCode = trim((string) ($_GET['maYC'] ?? ''));
$pageError = null;
$formError = null;
$orders = [];
$selectedOrder = null;
$bomRows = [];
$productRows = [];
$nextOrderCode = 'YC001';
$successMessage = isset($_GET['request_created'])
    ? 'Đã lập đề nghị xuất nguyên vật liệu và gửi quản lý phê duyệt.'
    : null;

$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$statusNames = [0 => 'Chờ xử lý', 1 => 'Đang làm', 2 => 'Đã hoàn thành'];
$statusClasses = [
    0 => 'bg-amber-100 text-amber-700',
    1 => 'bg-blue-100 text-blue-700',
    2 => 'bg-emerald-100 text-emerald-700',
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_order' && $isManager) {
            $productCodes = $_POST['maTP'] ?? [];
            $quantities = $_POST['soLuong'] ?? [];
            $maNV = $currentUser;
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

            if ($items === [] || $maNV === '') {
                $formError = 'Vui lòng chọn ít nhất một thành phẩm và nhập số lượng hợp lệ.';
            } else {
                $pdo->beginTransaction();
                $check = $pdo->prepare(
                    'SELECT maTP FROM THANHPHAM WHERE maTP = :maTP'
                );
                $bomStatement = $pdo->prepare(
                    'SELECT bom.maNVL, bom.soLuong, n.soLuong AS tonKho
                     FROM CHITIETTHANHPHAM bom
                     INNER JOIN NGUYENVATLIEU n ON n.maNVL = bom.maNVL
                     WHERE bom.maTP = :maTP'
                );
                $requiredMaterials = [];
                foreach ($items as $maTP => $quantity) {
                    $check->execute(['maTP' => $maTP]);
                    if (!$check->fetch()) {
                        throw new RuntimeException('Thành phẩm hoặc nhân viên sản xuất không hợp lệ.');
                    }
                    $bomStatement->execute(['maTP' => $maTP]);
                    foreach ($bomStatement->fetchAll() as $bom) {
                        $requiredMaterials[$bom['maNVL']]['required'] =
                            ($requiredMaterials[$bom['maNVL']]['required'] ?? 0) + ((float) $bom['soLuong'] * $quantity);
                        $requiredMaterials[$bom['maNVL']]['stock'] = (float) $bom['tonKho'];
                    }
                }
                $hasEnoughMaterials = true;
                foreach ($requiredMaterials as $material) {
                    if ($material['required'] > $material['stock']) {
                        $hasEnoughMaterials = false;
                        break;
                    }
                }

                $next = (int) $pdo->query(
                    "SELECT COALESCE(MAX(CAST(SUBSTRING(maYC, 3) AS UNSIGNED)), 0) + 1
                     FROM YEUCAU WHERE maYC REGEXP '^YC[0-9]+$'"
                )->fetchColumn();
                $orderCode = 'YC' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
                $insert = $pdo->prepare(
                    'INSERT INTO YEUCAU (maYC, ngayYC, maNV, trangThai) VALUES (:maYC, NOW(), :maNV, :trangThai)'
                );
                $insert->execute([
                    'maYC' => $orderCode,
                    'maNV' => $maNV,
                    'trangThai' => $hasEnoughMaterials ? 1 : 0,
                ]);
                $detail = $pdo->prepare(
                    'INSERT INTO CHITIETYEUCAU (maYC, maTP, soLuong) VALUES (:maYC, :maTP, :soLuong)'
                );
                foreach ($items as $maTP => $quantity) {
                    $detail->execute(['maYC' => $orderCode, 'maTP' => $maTP, 'soLuong' => $quantity]);
                }
                $pdo->commit();
                header('Location: production_orders.php?status=' . ($hasEnoughMaterials ? 'working' : 'pending') . '&maYC=' . urlencode($orderCode));
                exit;
            }
        } elseif ($action === 'complete_order' && !$isManager) {
            $completeCode = trim((string) ($_POST['maYC'] ?? ''));
            $pdo->beginTransaction();
            $orderCheck = $pdo->prepare(
                'SELECT maYC FROM YEUCAU WHERE maYC = :maYC AND trangThai = 1 FOR UPDATE'
            );
            $orderCheck->execute(['maYC' => $completeCode]);
            if (!$orderCheck->fetch()) {
                throw new RuntimeException('Lệnh không tồn tại hoặc chưa đủ vật tư để hoàn thành.');
            }
            $completionDetails = $pdo->prepare(
                'SELECT maTP, soLuong FROM CHITIETYEUCAU WHERE maYC = :maYC'
            );
            $completionDetails->execute(['maYC' => $completeCode]);
            $requiredMaterialsStatement = $pdo->prepare(
                'SELECT bom.maNVL, SUM(bom.soLuong * ct.soLuong) AS soLuongCan, n.soLuong AS tonKho
                 FROM CHITIETYEUCAU ct
                 INNER JOIN CHITIETTHANHPHAM bom ON bom.maTP = ct.maTP
                 INNER JOIN NGUYENVATLIEU n ON n.maNVL = bom.maNVL
                 WHERE ct.maYC = :maYC
                 GROUP BY bom.maNVL, n.soLuong
                 FOR UPDATE'
            );
            $requiredMaterialsStatement->execute(['maYC' => $completeCode]);
            $requiredMaterials = $requiredMaterialsStatement->fetchAll();
            if ($requiredMaterials === []) {
                throw new RuntimeException('Lệnh chưa có định mức nguyên vật liệu để hoàn thành.');
            }

            foreach ($requiredMaterials as $material) {
                if ((int) $material['tonKho'] < (int) $material['soLuongCan']) {
                    throw new RuntimeException('Tồn kho nguyên vật liệu không đủ để hoàn thành lệnh.');
                }
            }

            $decreaseMaterialStock = $pdo->prepare(
                'UPDATE NGUYENVATLIEU
                 SET soLuong = soLuong - :soLuong
                 WHERE maNVL = :maNVL AND soLuong >= :soLuongKiemTra'
            );
            foreach ($requiredMaterials as $material) {
                $decreaseMaterialStock->execute([
                    'soLuong' => (int) $material['soLuongCan'],
                    'maNVL' => $material['maNVL'],
                    'soLuongKiemTra' => (int) $material['soLuongCan'],
                ]);
                if ($decreaseMaterialStock->rowCount() !== 1) {
                    throw new RuntimeException('Không thể cập nhật tồn kho nguyên vật liệu.');
                }
            }

            $increaseStock = $pdo->prepare('UPDATE THANHPHAM SET soLuong = soLuong + :soLuong WHERE maTP = :maTP');
            foreach ($completionDetails->fetchAll() as $completionDetail) {
                $increaseStock->execute([
                    'soLuong' => (int) $completionDetail['soLuong'],
                    'maTP' => $completionDetail['maTP'],
                ]);
            }
            $complete = $pdo->prepare('UPDATE YEUCAU SET trangThai = 2 WHERE maYC = :maYC');
            $complete->execute(['maYC' => $completeCode]);
            $pdo->commit();
            header('Location: production_orders.php?status=completed&maYC=' . urlencode($completeCode));
            exit;
        } elseif ($action === 'request_materials' && !$isManager) {
            $requestCode = trim((string) ($_POST['maYC'] ?? ''));
            if ($requestCode === '' || $currentUser === '') {
                throw new RuntimeException('Không xác định được lệnh sản xuất hoặc nhân viên yêu cầu.');
            }

            $pdo->beginTransaction();
            $orderStatement = $pdo->prepare(
                'SELECT maYC
                 FROM YEUCAU
                 WHERE maYC = :maYC AND trangThai = 0
                 FOR UPDATE'
            );
            $orderStatement->execute(['maYC' => $requestCode]);
            if (!$orderStatement->fetch()) {
                throw new RuntimeException('Lệnh không tồn tại hoặc không còn ở trạng thái thiếu vật tư.');
            }

            $missingMaterialsStatement = $pdo->prepare(
                'SELECT bom.maNVL,
                        CEIL(SUM(bom.soLuong * ct.soLuong) - n.soLuong) AS soLuongThieu
                 FROM CHITIETYEUCAU ct
                 INNER JOIN CHITIETTHANHPHAM bom ON bom.maTP = ct.maTP
                 INNER JOIN NGUYENVATLIEU n ON n.maNVL = bom.maNVL
                 WHERE ct.maYC = :maYC
                 GROUP BY bom.maNVL, n.soLuong
                 HAVING SUM(bom.soLuong * ct.soLuong) > n.soLuong
                 FOR UPDATE'
            );
            $missingMaterialsStatement->execute(['maYC' => $requestCode]);
            $missingMaterials = $missingMaterialsStatement->fetchAll();
            if ($missingMaterials === []) {
                throw new RuntimeException('Lệnh hiện không có nguyên vật liệu bị thiếu.');
            }

            $manager = $pdo->query(
                'SELECT maNV
                 FROM NHANVIEN
                 WHERE vaiTro = 0
                 ORDER BY maNV
                 LIMIT 1'
            )->fetchColumn();
            if (!$manager) {
                throw new RuntimeException('Chưa có quản lý để tiếp nhận đề nghị xuất nguyên vật liệu.');
            }

            $nextMaterialRequest = (int) $pdo->query(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(maPX, 4) AS UNSIGNED)), 0) + 1
                 FROM PHIEUXUATNVL
                 WHERE maPX REGEXP '^PXN[0-9]+$'"
            )->fetchColumn();
            $materialRequestCode = 'PXN' . str_pad((string) $nextMaterialRequest, 3, '0', STR_PAD_LEFT);

            $insertRequest = $pdo->prepare(
                'INSERT INTO PHIEUXUATNVL (maPX, ngayXuat, trangThai, maNV, maQL, loaiPhieu)
                 VALUES (:maPX, NOW(), 0, :maNV, :maQL, 0)'
            );
            $insertRequest->execute([
                'maPX' => $materialRequestCode,
                'maNV' => $currentUser,
                'maQL' => $manager,
            ]);

            $insertRequestDetail = $pdo->prepare(
                'INSERT INTO CHITIETPHIEUXUATNVL (maPX, maNVL, soLuong)
                 VALUES (:maPX, :maNVL, :soLuong)'
            );
            foreach ($missingMaterials as $material) {
                $insertRequestDetail->execute([
                    'maPX' => $materialRequestCode,
                    'maNVL' => $material['maNVL'],
                    'soLuong' => (int) $material['soLuongThieu'],
                ]);
            }

            $pdo->commit();
            header(
                'Location: production_orders.php?status=pending&maYC='
                . urlencode($requestCode) . '&request_created=1'
            );
            exit;
        }
    }

    if ($isManager) {
        $productRows = $pdo->query('SELECT maTP, tenTP, donViTinh FROM THANHPHAM ORDER BY maTP')->fetchAll();
        $nextOrderNumber = (int) $pdo->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(maYC, 3) AS UNSIGNED)), 0) + 1
             FROM YEUCAU WHERE maYC REGEXP '^YC[0-9]+$'"
        )->fetchColumn();
        $nextOrderCode = 'YC' . str_pad((string) $nextOrderNumber, 3, '0', STR_PAD_LEFT);
    }

    $allOrdersStatement = $pdo->query(
        'SELECT yc.maYC, yc.ngayYC, yc.maNV, yc.trangThai, nv.hoTen,
                GROUP_CONCAT(CONCAT(tp.tenTP, " (", ct.soLuong, " ", tp.donViTinh, ")") SEPARATOR ", ") AS sanPham
         FROM YEUCAU yc
         INNER JOIN NHANVIEN nv ON nv.maNV = yc.maNV
         LEFT JOIN CHITIETYEUCAU ct ON ct.maYC = yc.maYC
         LEFT JOIN THANHPHAM tp ON tp.maTP = ct.maTP
         GROUP BY yc.maYC, yc.ngayYC, yc.maNV, yc.trangThai, nv.hoTen
         ORDER BY yc.trangThai ASC, yc.maYC ASC'
    );
    $allOrders = $allOrdersStatement->fetchAll();

    // Lệnh chưa hoàn thành được chuyển sang đúng trạng thái theo tồn kho BOM hiện tại.
    foreach ($allOrders as &$order) {
        if ((int) $order['trangThai'] === 2) {
            continue;
        }
        $stockCheck = $pdo->prepare(
            'SELECT bom.maNVL, SUM(bom.soLuong * ct.soLuong) AS requiredAmount,
                    n.soLuong AS stockAmount
             FROM CHITIETYEUCAU ct
             INNER JOIN CHITIETTHANHPHAM bom ON bom.maTP = ct.maTP
             INNER JOIN NGUYENVATLIEU n ON n.maNVL = bom.maNVL
             WHERE ct.maYC = :maYC'
             . ' GROUP BY bom.maNVL, n.soLuong'
        );
        $stockCheck->execute(['maYC' => $order['maYC']]);
        $stockRows = $stockCheck->fetchAll();
        $hasEnough = $stockRows !== [];
        foreach ($stockRows as $stockRow) {
            if ((float) $stockRow['requiredAmount'] > (float) $stockRow['stockAmount']) {
                $hasEnough = false;
                break;
            }
        }
        $newStatus = $hasEnough ? 1 : 0;
        if ((int) $order['trangThai'] !== $newStatus) {
            $update = $pdo->prepare('UPDATE YEUCAU SET trangThai = :trangThai WHERE maYC = :maYC AND trangThai <> 2');
            $update->execute(['trangThai' => $newStatus, 'maYC' => $order['maYC']]);
            $order['trangThai'] = $newStatus;
        }
    }
    unset($order);
    usort($allOrders, static function (array $left, array $right): int {
        $statusCompare = (int) $left['trangThai'] <=> (int) $right['trangThai'];
        if ($statusCompare !== 0) {
            return $statusCompare;
        }
        return strnatcasecmp((string) $left['maYC'], (string) $right['maYC']);
    });

    $orders = array_values(array_filter($allOrders, static function (array $order) use ($statusFilter): bool {
        return $statusFilter === 'all'
            || ($statusFilter === 'pending' && (int) $order['trangThai'] === 0)
            || ($statusFilter === 'working' && (int) $order['trangThai'] === 1)
            || ($statusFilter === 'completed' && (int) $order['trangThai'] === 2);
    }));

    if ($selectedCode === '' && $orders !== []) {
        $selectedCode = (string) $orders[0]['maYC'];
    }
    if ($selectedCode !== '') {
        $detail = $pdo->prepare(
            'SELECT yc.maYC, yc.ngayYC, yc.maNV, yc.trangThai, nv.hoTen,
                    ct.maTP, ct.soLuong, tp.tenTP, tp.donViTinh
             FROM YEUCAU yc
             INNER JOIN NHANVIEN nv ON nv.maNV = yc.maNV
             INNER JOIN CHITIETYEUCAU ct ON ct.maYC = yc.maYC
             INNER JOIN THANHPHAM tp ON tp.maTP = ct.maTP
             WHERE yc.maYC = :maYC'
        );
        $detail->execute(['maYC' => $selectedCode]);
        $detailRows = $detail->fetchAll();
        $selectedOrder = $detailRows[0] ?? null;
        if ($selectedOrder) {
            $bom = $pdo->prepare(
                'SELECT bom.maNVL, n.tenNVL, dvt.tenDVT,
                        SUM(bom.soLuong) AS dinhMuc,
                        SUM(bom.soLuong * ct.soLuong) AS tongCan,
                        n.soLuong AS tonKho
                 FROM CHITIETYEUCAU ct
                 INNER JOIN CHITIETTHANHPHAM bom ON bom.maTP = ct.maTP
                 INNER JOIN NGUYENVATLIEU n ON n.maNVL = bom.maNVL
                 INNER JOIN DONVITINH dvt ON dvt.maDVT = n.maDVT
                 WHERE ct.maYC = :maYC
                 GROUP BY bom.maNVL, n.tenNVL, dvt.tenDVT, n.soLuong
                 ORDER BY bom.maNVL'
            );
            $bom->execute(['maYC' => $selectedCode]);
            $bomRows = $bom->fetchAll();
        }
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pageError = $exception instanceof RuntimeException ? $exception->getMessage() : 'Không thể tải dữ liệu lệnh sản xuất.';
}

$counts = [
    'all' => count($allOrders ?? []),
    'pending' => count(array_filter($allOrders ?? [], static fn (array $o): bool => (int) $o['trangThai'] === 0)),
    'working' => count(array_filter($allOrders ?? [], static fn (array $o): bool => (int) $o['trangThai'] === 1)),
    'completed' => count(array_filter($allOrders ?? [], static fn (array $o): bool => (int) $o['trangThai'] === 2)),
];

require_once '../includes/header.php';
?>
<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3">
        <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg text-xl"><i class="fa-regular fa-clipboard"></i></div>
        <div><h2 class="text-xl font-bold text-slate-800">Lệnh Sản Xuất & Kiểm Tra Định Mức Vật Tư</h2><p class="text-xs text-slate-500">Theo dõi yêu cầu sản xuất và đối chiếu định mức với tồn kho nguyên vật liệu.</p></div>
    </div>
    <?php if ($isManager): ?>
        <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm flex items-center gap-2" id="btn-open-add-order"><i class="fa-solid fa-plus"></i> Khởi Tạo Lệnh Sản Xuất Mới</button>
    <?php endif; ?>
</div>

<?php if ($pageError !== null): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($pageError) ?></div><?php endif; ?>
<?php if ($formError !== null): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= $escape($formError) ?></div><?php endif; ?>
<?php if ($successMessage !== null): ?><div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status"><?= $escape($successMessage) ?></div><?php endif; ?>

<div class="flex items-center gap-2 mb-4 overflow-x-auto border-b border-slate-200 pb-2 scrollbar-custom">
    <?php $filters = ['all' => ['Tất cả', ''], 'pending' => ['Chờ xử lý', 'fa-regular fa-clock'], 'working' => ['Đang làm', 'fa-solid fa-gears'], 'completed' => ['Đã hoàn thành', 'fa-regular fa-circle-check']]; ?>
    <?php foreach ($filters as $key => [$label, $icon]): ?>
        <a href="?status=<?= $key ?>" class="<?= $statusFilter === $key ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' ?> shrink-0 whitespace-nowrap text-[13px] font-medium px-4 py-1.5 rounded-full flex items-center gap-2">
            <?php if ($icon): ?><i class="<?= $icon ?>"></i><?php endif; ?><?= $escape($label) ?> (<?= $counts[$key] ?>)
        </a>
    <?php endforeach; ?>
</div>

<div class="flex gap-6 items-start">
    <div class="w-1/3 shrink-0">
        <div class="flex justify-between items-end mb-3 px-1"><h3 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH LỆNH SẢN XUẤT (<?= count($orders) ?>)</h3><span class="text-xs text-blue-500 font-medium">BẢNG YEUCAU</span></div>
        <div class="max-h-[520px] space-y-3 overflow-y-auto pr-2 scrollbar-custom">
            <?php if (!$orders): ?><div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500">Không có lệnh ở trạng thái này.</div><?php endif; ?>
            <?php foreach ($orders as $order): ?>
                <?php $orderStatus = (int) $order['trangThai']; ?>
                <a href="?status=<?= $statusFilter ?>&maYC=<?= urlencode($order['maYC']) ?>" class="block <?= $selectedCode === $order['maYC'] ? 'border-2 border-blue-500 bg-blue-50' : 'border border-slate-200 bg-white hover:border-blue-300' ?> rounded-xl p-4 shadow-sm transition">
                    <div class="flex justify-between items-start mb-2"><span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded"><?= $escape($order['maYC']) ?></span><span class="<?= $statusClasses[$orderStatus] ?> text-[10px] font-medium px-2 py-0.5 rounded"><?= $statusNames[$orderStatus] ?></span></div>
                    <div class="text-sm font-semibold text-slate-800 mb-1"><?= $escape($order['sanPham'] ?: 'Chưa có sản phẩm') ?></div>
                    <div class="text-[11px] text-slate-500">Ngày tạo: <?= $escape($order['ngayYC']) ?> &bull; Nhân viên: <?= $escape($order['hoTen']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6 flex flex-col">
        <?php if (!$selectedOrder): ?>
            <div class="py-12 text-center text-sm text-slate-500">Chọn một lệnh để xem chi tiết.</div>
        <?php else: ?>
            <?php $currentStatus = (int) $selectedOrder['trangThai']; ?>
            <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
                <div><div class="flex items-center gap-3 mb-2"><span class="bg-blue-100 text-blue-800 font-bold text-xs px-2 py-1 rounded">Mã Lệnh: <?= $escape($selectedOrder['maYC']) ?></span><span class="text-slate-400 text-[11px]">Ngày tạo: <?= $escape($selectedOrder['ngayYC']) ?></span></div><h3 class="text-lg font-bold text-slate-800">Nhiệm vụ: <?= $escape($selectedOrder['tenTP']) ?> - <?= (int) $selectedOrder['soLuong'] ?> <?= $escape($selectedOrder['donViTinh']) ?></h3><p class="text-slate-500 text-[11px]">Quản lý yêu cầu: <?= $escape($selectedOrder['hoTen']) ?></p></div>
                <span class="<?= $statusClasses[$currentStatus] ?> text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1 uppercase"><i class="fa-regular fa-circle-check"></i><?= $statusNames[$currentStatus] ?></span>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-5 flex justify-between items-center">
                <div><h4 class="font-bold text-slate-800 text-xs mb-1">Hành động tiếp theo</h4><p class="text-[10px] text-slate-500"><?= $currentStatus === 0 ? 'Kho đang thiếu vật tư, nhân viên cần chờ được cấp bổ sung.' : ($currentStatus === 1 ? 'Đã đủ vật tư, nhân viên có thể thực hiện sản xuất và xác nhận hoàn thành.' : 'Lệnh đã hoàn thành và đã cập nhật kho thành phẩm.') ?></p></div>
                <div class="flex gap-2">
                    <?php if ($currentStatus === 0 && !$isManager): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="request_materials">
                            <input type="hidden" name="maYC" value="<?= $escape($selectedOrder['maYC']) ?>">
                            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white text-xs font-medium px-4 py-2 rounded-lg"><i class="fa-regular fa-paper-plane"></i> Lập đề nghị xuất NVL</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($currentStatus === 1 && !$isManager): ?>
                        <form method="post"><input type="hidden" name="action" value="complete_order"><input type="hidden" name="maYC" value="<?= $escape($selectedOrder['maYC']) ?>"><button class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium px-4 py-2 rounded-lg"><i class="fa-solid fa-check-circle"></i> Xác nhận hoàn thành</button></form>
                    <?php elseif ($currentStatus === 0): ?>
                        <span class="rounded-lg bg-amber-100 px-3 py-2 text-xs font-medium text-amber-700">Chờ đủ vật tư</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex justify-between items-center mb-3"><h4 class="font-bold text-blue-800 text-xs flex items-center gap-2"><i class="fa-solid fa-calculator text-blue-500"></i> ĐỐI CHIẾU BOM VÀ KHO NVL</h4><span class="bg-<?= $currentStatus === 0 ? 'amber' : 'emerald' ?>-50 text-<?= $currentStatus === 0 ? 'amber' : 'emerald' ?>-600 text-[11px] font-medium px-2.5 py-1 rounded-full border border-<?= $currentStatus === 0 ? 'amber' : 'emerald' ?>-200"><?= $currentStatus === 0 ? 'Kho chưa đủ vật tư' : 'Kho đủ vật tư' ?></span></div>
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold"><tr><th class="px-4 py-3 border-b">Mã NVL</th><th class="px-4 py-3 border-b">Tên NVL</th><th class="px-4 py-3 text-center border-b">Tổng định mức</th><th class="px-4 py-3 text-center border-b">Tổng cần</th><th class="px-4 py-3 text-right border-b">Tồn kho</th><th class="px-4 py-3 text-center border-b">Tình trạng</th></tr></thead><tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($bomRows as $bom): $enough = (int) $bom['tonKho'] >= (int) $bom['tongCan']; ?>
                        <tr><td class="px-4 py-3 font-bold"><?= $escape($bom['maNVL']) ?></td><td class="px-4 py-3"><?= $escape($bom['tenNVL']) ?></td><td class="px-4 py-3 text-center"><?= (int) $bom['dinhMuc'] ?> <?= $escape($bom['tenDVT']) ?></td><td class="px-4 py-3 text-center font-bold text-blue-600"><?= (int) $bom['tongCan'] ?> <?= $escape($bom['tenDVT']) ?></td><td class="px-4 py-3 text-right"><?= (int) $bom['tonKho'] ?> <?= $escape($bom['tenDVT']) ?></td><td class="px-4 py-3 text-center <?= $enough ? 'text-emerald-600' : 'text-rose-600' ?> font-medium"><?= $enough ? 'Đủ vật tư' : 'Thiếu vật tư' ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isManager): ?>
<div id="modal-add-order" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 px-4" style="display:none;">
    <div class="w-full max-w-[600px] rounded-xl bg-white shadow-2xl">
        <form method="post">
            <input type="hidden" name="action" value="create_order">
            <div class="flex justify-between items-center p-5 border-b"><h3 class="font-bold text-slate-800 text-lg">Tạo Lệnh Sản Xuất Mới</h3><button type="button" class="close-modal text-slate-400"><i class="fa-solid fa-xmark text-lg"></i></button></div>
            <div class="p-6 space-y-4">
                <label class="block text-[11px] font-medium text-slate-600">Mã yêu cầu (tự động)<input type="text" value="<?= $escape($nextOrderCode) ?>" readonly class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500"></label>
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-[11px] font-medium text-slate-600">Thành phẩm và số lượng cần sản xuất</label>
                        <button type="button" id="add-order-item" class="text-xs font-medium text-blue-600 hover:text-blue-700"><i class="fa-solid fa-plus"></i> Thêm thành phẩm</button>
                    </div>
                    <div id="order-items" class="space-y-3">
                        <div class="order-item flex items-end gap-2">
                            <label class="min-w-0 flex-1 text-[11px] font-medium text-slate-600">Thành phẩm
                                <select name="maTP[]" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <option value="">-- Chọn thành phẩm --</option>
                                    <?php foreach ($productRows as $product): ?>
                                        <option value="<?= $escape($product['maTP']) ?>"><?= $escape($product['tenTP']) ?> (<?= $escape($product['maTP']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="w-28 text-[11px] font-medium text-slate-600">Số lượng
                                <input type="number" name="soLuong[]" min="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            </label>
                            <button type="button" class="remove-order-item hidden pb-2 text-lg text-rose-500" aria-label="Xóa sản phẩm">&times;</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-5 border-t flex justify-end gap-3 bg-slate-50 rounded-b-xl"><button type="button" class="close-modal text-sm text-slate-500">Hủy</button><button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">Phát Lệnh Tới Nhân Viên</button></div>
        </form>
    </div>
</div>
<script src="../assets/js/production.js"></script>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
