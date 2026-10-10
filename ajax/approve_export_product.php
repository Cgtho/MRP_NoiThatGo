<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền duyệt phiếu xuất thành phẩm.']);
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

$exportCode = strtoupper(trim((string) ($_POST['maPX'] ?? '')));
if (!preg_match('/^PXT[0-9]+$/', $exportCode)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã phiếu xuất thành phẩm không hợp lệ.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $exportStatement = $pdo->prepare(
        'SELECT maPX, trangThai
         FROM PHIEUXUATTP
         WHERE maPX = :maPX
         FOR UPDATE'
    );
    $exportStatement->execute(['maPX' => $exportCode]);
    $export = $exportStatement->fetch();

    if (!$export) {
        throw new RuntimeException('Không tìm thấy phiếu xuất thành phẩm.');
    }
    if ((int) $export['trangThai'] !== 0) {
        throw new RuntimeException('Phiếu xuất này đã được xử lý trước đó.');
    }

    $detailStatement = $pdo->prepare(
        'SELECT ct.maTP, ct.soLuong, tp.soLuong AS tonKho
         FROM CHITIETPHIEUXUATTP ct
         INNER JOIN THANHPHAM tp ON tp.maTP = ct.maTP
         WHERE ct.maPX = :maPX
         FOR UPDATE'
    );
    $detailStatement->execute(['maPX' => $exportCode]);
    $details = $detailStatement->fetchAll();

    if ($details === []) {
        throw new RuntimeException('Phiếu xuất chưa có thành phẩm.');
    }

    foreach ($details as $detail) {
        $quantity = (int) $detail['soLuong'];
        if ($quantity <= 0) {
            throw new RuntimeException('Số lượng thành phẩm không hợp lệ.');
        }
        if ((int) $detail['tonKho'] < $quantity) {
            throw new RuntimeException('Tồn kho không đủ cho thành phẩm ' . $detail['maTP'] . '.');
        }
    }

    $updateStock = $pdo->prepare(
        'UPDATE THANHPHAM SET soLuong = soLuong - :soLuong WHERE maTP = :maTP'
    );
    foreach ($details as $detail) {
        $updateStock->execute([
            'soLuong' => (int) $detail['soLuong'],
            'maTP' => $detail['maTP'],
        ]);
    }

    $updateExport = $pdo->prepare(
        'UPDATE PHIEUXUATTP
         SET trangThai = 1, maQL = :maQL, ngayDuyet = NOW(), lyDoTuChoi = NULL
         WHERE maPX = :maPX AND trangThai = 0'
    );
    $updateExport->execute([
        'maQL' => $_SESSION['current_user'],
        'maPX' => $exportCode,
    ]);

    if ($updateExport->rowCount() !== 1) {
        throw new RuntimeException('Phiếu xuất đã được xử lý bởi người dùng khác.');
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Đã duyệt phiếu và trừ tồn kho thành phẩm.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code($exception instanceof RuntimeException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Không thể duyệt phiếu xuất thành phẩm. Vui lòng thử lại.',
    ]);
}
