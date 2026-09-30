<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm đơn vị tính.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

require_once __DIR__ . '/../config.php';

$unitName = trim((string) ($_POST['tenDVT'] ?? ''));
if ($unitName === '' || mb_strlen($unitName) > 10) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Tên đơn vị tính phải từ 1 đến 10 ký tự.']);
    exit;
}

try {
    $check = $pdo->prepare('SELECT maDVT FROM DONVITINH WHERE tenDVT = :tenDVT LIMIT 1');
    $check->execute(['tenDVT' => $unitName]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Đơn vị tính này đã tồn tại.']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO DONVITINH (tenDVT) VALUES (:tenDVT)');
    $stmt->execute(['tenDVT' => $unitName]);
    echo json_encode(['success' => true, 'message' => 'Đã thêm đơn vị tính.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu đơn vị tính.']);
}