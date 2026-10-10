<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm nguyên vật liệu.']);
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

$materialName = trim((string) ($_POST['tenNVL'] ?? ''));
$unitId = filter_var($_POST['maDVT'] ?? null, FILTER_VALIDATE_INT);

if ($materialName === '' || mb_strlen($materialName) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Tên nguyên vật liệu phải từ 1 đến 50 ký tự.']);
    exit;
}
if ($unitId === false || $unitId < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn đơn vị tính.']);
    exit;
}

try {
    $nextMaterialNumber = (int) $pdo->query(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(maNVL, 4) AS UNSIGNED)), 0) + 1
         FROM NGUYENVATLIEU
         WHERE maNVL REGEXP '^NVL[0-9]+$'"
    )->fetchColumn();
    $materialCode = 'NVL' . str_pad((string) $nextMaterialNumber, 3, '0', STR_PAD_LEFT);

    $checkUnit = $pdo->prepare('SELECT maDVT FROM DONVITINH WHERE maDVT = :maDVT LIMIT 1');
    $checkUnit->execute(['maDVT' => $unitId]);
    if (!$checkUnit->fetch()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Đơn vị tính không tồn tại.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO NGUYENVATLIEU (maNVL, tenNVL, maDVT, soLuong)
         VALUES (:maNVL, :tenNVL, :maDVT, 0)'
    );
    $stmt->execute(['maNVL' => $materialCode, 'tenNVL' => $materialName, 'maDVT' => $unitId]);
    echo json_encode(['success' => true, 'message' => 'Đã thêm nguyên vật liệu ' . $materialCode . '.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu nguyên vật liệu.']);
}
