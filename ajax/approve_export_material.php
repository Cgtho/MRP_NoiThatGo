<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền duyệt phiếu xuất NVL.']);
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
if (!preg_match('/^PXN[0-9]+$/', $exportCode)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã phiếu xuất NVL không hợp lệ.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $exportStatement = $pdo->prepare(
        'SELECT maPX, trangThai FROM PHIEUXUATNVL WHERE maPX = :maPX FOR UPDATE'
    );
    $exportStatement->execute(['maPX' => $exportCode]);
    $export = $exportStatement->fetch();

    if (!$export) {
        throw new RuntimeException('Không tìm thấy phiếu xuất NVL.');
    }
    if ((int) $export['trangThai'] === 1) {
        throw new RuntimeException('Phiếu xuất này đã được duyệt trước đó.');
    }

    $detailStatement = $pdo->prepare(
        'SELECT ct.maNVL, ct.soLuong, nvl.soLuong AS tonKho
         FROM CHITIETPHIEUXUATNVL ct
         INNER JOIN NGUYENVATLIEU nvl ON nvl.maNVL = ct.maNVL
         WHERE ct.maPX = :maPX
         FOR UPDATE'
    );
    $detailStatement->execute(['maPX' => $exportCode]);
    $details = $detailStatement->fetchAll();

    if ($details === []) {
        throw new RuntimeException('Phiếu xuất chưa có nguyên vật liệu.');
    }

    foreach ($details as $detail) {
        $quantity = (int) $detail['soLuong'];
        if ($quantity <= 0) {
            throw new RuntimeException('Số lượng nguyên vật liệu không hợp lệ.');
        }
        if ((int) $detail['tonKho'] < $quantity) {
            throw new RuntimeException('Tồn kho không đủ cho nguyên vật liệu ' . $detail['maNVL'] . '.');
        }
    }

    $updateStock = $pdo->prepare(
        'UPDATE NGUYENVATLIEU SET soLuong = soLuong - :soLuong WHERE maNVL = :maNVL'
    );
    foreach ($details as $detail) {
        $updateStock->execute([
            'soLuong' => (int) $detail['soLuong'],
            'maNVL' => $detail['maNVL'],
        ]);
    }

    $updateExport = $pdo->prepare(
        'UPDATE PHIEUXUATNVL
         SET trangThai = 1, maQL = :maQL, ngayDuyet = NOW()
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
    echo json_encode(['success' => true, 'message' => 'Đã duyệt phiếu và trừ tồn kho.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code($exception instanceof RuntimeException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Không thể duyệt phiếu xuất NVL. Vui lòng thử lại.',
    ]);
}
