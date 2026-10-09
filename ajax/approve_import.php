<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền duyệt phiếu nhập kho.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';

csrf_require_json();

$receiptCode = strtoupper(trim((string) ($_POST['maPN'] ?? '')));
if (!preg_match('/^PN[0-9]+$/', $receiptCode)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã phiếu nhập không hợp lệ.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $receiptStatement = $pdo->prepare(
        'SELECT maPN, trangThai
         FROM PHIEUNHAPKHO
         WHERE maPN = :maPN
         FOR UPDATE'
    );
    $receiptStatement->execute(['maPN' => $receiptCode]);
    $receipt = $receiptStatement->fetch();

    if (!$receipt) {
        throw new RuntimeException('Không tìm thấy phiếu nhập kho.');
    }

    if ((int) $receipt['trangThai'] === 1) {
        throw new RuntimeException('Phiếu nhập này đã được duyệt trước đó.');
    }

    $detailStatement = $pdo->prepare(
        'SELECT ct.maNVL, ct.soLuong
         FROM CHITIETPHIEUNHAP ct
         INNER JOIN NGUYENVATLIEU nvl ON nvl.maNVL = ct.maNVL
         WHERE ct.maPN = :maPN
         FOR UPDATE'
    );
    $detailStatement->execute(['maPN' => $receiptCode]);
    $details = $detailStatement->fetchAll();

    if ($details === []) {
        throw new RuntimeException('Phiếu nhập chưa có nguyên vật liệu.');
    }

    $updateStock = $pdo->prepare(
        'UPDATE NGUYENVATLIEU
         SET soLuong = soLuong + :soLuong
         WHERE maNVL = :maNVL'
    );
    foreach ($details as $detail) {
        $quantity = (int) $detail['soLuong'];
        if ($quantity <= 0) {
            throw new RuntimeException('Số lượng nguyên vật liệu không hợp lệ.');
        }

        $updateStock->execute([
            'soLuong' => $quantity,
            'maNVL' => $detail['maNVL'],
        ]);
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
        if (!isset($pendingOrders[$orderCode])) {
            $pendingOrders[$orderCode] = true;
        }
        if ((float) $materialRequirement['requiredAmount'] > (float) $materialRequirement['stockAmount']) {
            $pendingOrders[$orderCode] = false;
        }
    }

    $activateOrder = $pdo->prepare(
        'UPDATE YEUCAU
         SET trangThai = 1
         WHERE maYC = :maYC AND trangThai = 0'
    );
    foreach ($pendingOrders as $orderCode => $hasEnoughMaterials) {
        if ($hasEnoughMaterials) {
            $activateOrder->execute(['maYC' => $orderCode]);
        }
    }

    $updateReceipt = $pdo->prepare(
        'UPDATE PHIEUNHAPKHO
         SET trangThai = 1, maQL = :maQL, ngayDuyet = NOW()
         WHERE maPN = :maPN AND trangThai = 0'
    );
    $updateReceipt->execute([
        'maQL' => $_SESSION['current_user'],
        'maPN' => $receiptCode,
    ]);

    if ($updateReceipt->rowCount() !== 1) {
        throw new RuntimeException('Phiếu nhập đã được xử lý bởi người dùng khác.');
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Đã duyệt phiếu và cộng tồn kho.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code($exception instanceof RuntimeException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Không thể duyệt phiếu nhập kho. Vui lòng thử lại.',
    ]);
}
