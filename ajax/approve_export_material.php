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
        'SELECT maPX, trangThai, loaiPhieu
         FROM PHIEUXUATNVL
         WHERE maPX = :maPX
         FOR UPDATE'
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
        'SELECT ct.maNVL, ct.soLuong, nvl.tenNVL, nvl.maDVT, nvl.soLuong AS tonKho
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

    $decreaseWarehouseStock = $pdo->prepare(
        'UPDATE NGUYENVATLIEU
         SET soLuong = soLuong - :soLuong
         WHERE maNVL = :maNVL AND soLuong >= :soLuongKiemTra'
    );
    $workshopStock = $pdo->prepare(
        'SELECT maNVL FROM NGUYENVATLIEUXUONG WHERE maNVL = :maNVL FOR UPDATE'
    );
    $insertWorkshopStock = $pdo->prepare(
        'INSERT INTO NGUYENVATLIEUXUONG (maNVL, tenNVL, maDVT, soLuong)
         VALUES (:maNVL, :tenNVL, :maDVT, :soLuong)'
    );
    $increaseWorkshopStock = $pdo->prepare(
        'UPDATE NGUYENVATLIEUXUONG
         SET soLuong = soLuong + :soLuong
         WHERE maNVL = :maNVL'
    );

    foreach ($details as $detail) {
        $quantity = (int) $detail['soLuong'];
        $decreaseWarehouseStock->execute([
            'soLuong' => $quantity,
            'maNVL' => $detail['maNVL'],
            'soLuongKiemTra' => $quantity,
        ]);
        if ($decreaseWarehouseStock->rowCount() !== 1) {
            throw new RuntimeException('Không thể trừ tồn kho nguyên vật liệu ' . $detail['maNVL'] . '.');
        }

        $workshopStock->execute(['maNVL' => $detail['maNVL']]);
        if ($workshopStock->fetch()) {
            $increaseWorkshopStock->execute([
                'soLuong' => $quantity,
                'maNVL' => $detail['maNVL'],
            ]);
        } else {
            $insertWorkshopStock->execute([
                'maNVL' => $detail['maNVL'],
                'tenNVL' => $detail['tenNVL'],
                'maDVT' => $detail['maDVT'],
                'soLuong' => $quantity,
            ]);
        }
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
    echo json_encode(['success' => true, 'message' => 'Đã duyệt phiếu, trừ kho tổng và cộng kho xưởng.']);
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
