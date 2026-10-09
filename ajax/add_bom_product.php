<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm thành phẩm.']);
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

$tenTP = trim((string) ($_POST['tenTP'] ?? ''));
$donViTinh = trim((string) ($_POST['donViTinh'] ?? ''));
$materials = $_POST['materials'] ?? [];

if ($tenTP === '' || mb_strlen($tenTP) > 100 || $donViTinh === '' || !is_array($materials) || count($materials) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đủ thông tin thành phẩm và ít nhất một vật tư.']);
    exit;
}

$normalizedMaterials = [];
$seenMaterials = [];
foreach ($materials as $material) {
    $maNVL = strtoupper(trim((string) ($material['maNVL'] ?? '')));
    $soLuong = filter_var($material['soLuong'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($maNVL === '' || $soLuong === false || $soLuong <= 0 || isset($seenMaterials[$maNVL])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Danh sách vật tư không hợp lệ hoặc bị trùng vật tư.']);
        exit;
    }
    $seenMaterials[$maNVL] = true;
    $normalizedMaterials[] = ['maNVL' => $maNVL, 'soLuong' => $soLuong];
}

try {
    $unitCheck = $pdo->prepare('SELECT maDVT FROM DONVITINH WHERE tenDVT = :tenDVT LIMIT 1');
    $unitCheck->execute(['tenDVT' => $donViTinh]);
    if (!$unitCheck->fetch()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Đơn vị tính không tồn tại.']);
        exit;
    }

    $materialCheck = $pdo->prepare('SELECT maNVL FROM NGUYENVATLIEU WHERE maNVL = :maNVL LIMIT 1');
    $insertBom = $pdo->prepare(
        'INSERT INTO CHITIETTHANHPHAM (maTP, maNVL, soLuong)
         VALUES (:maTP, :maNVL, :soLuong)'
    );

    $pdo->beginTransaction();
    $nextProductNumber = (int) $pdo->query(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(maTP, 3) AS UNSIGNED)), 0) + 1
         FROM THANHPHAM
         WHERE maTP REGEXP '^TP[0-9]+$'"
    )->fetchColumn();
    $maTP = 'TP' . str_pad((string) $nextProductNumber, 3, '0', STR_PAD_LEFT);
    $insertProduct = $pdo->prepare(
        'INSERT INTO THANHPHAM (maTP, tenTP, donViTinh, soLuong)
         VALUES (:maTP, :tenTP, :donViTinh, 0)'
    );
    $insertProduct->execute([
        'maTP' => $maTP,
        'tenTP' => $tenTP,
        'donViTinh' => $donViTinh,
    ]);

    foreach ($normalizedMaterials as $material) {
        $materialCheck->execute(['maNVL' => $material['maNVL']]);
        if (!$materialCheck->fetch()) {
            throw new InvalidArgumentException('Một nguyên vật liệu không tồn tại.');
        }
        $insertBom->execute([
            'maTP' => $maTP,
            'maNVL' => $material['maNVL'],
            'soLuong' => $material['soLuong'],
        ]);
    }
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Đã thêm thành phẩm và định mức.']);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu thành phẩm và định mức.']);
}
