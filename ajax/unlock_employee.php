<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || !in_array((int) $_SESSION['role'], [0, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền mở khóa nhân viên.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

csrf_require_json();

$maNV = strtoupper(trim((string) ($_POST['maNV'] ?? '')));

if (!preg_match('/^[A-Z0-9]{2,10}$/', $maNV)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT vaiTro, trangThai FROM NHANVIEN WHERE maNV = :maNV LIMIT 1');
    $stmt->execute(['maNV' => $maNV]);
    $employee = $stmt->fetch();

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên cần mở khóa.']);
        exit;
    }

    if ((int) $_SESSION['role'] === 0 && (int) $employee['vaiTro'] === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Quản lý kho không thể mở khóa quản lý kho khác.']);
        exit;
    }

    if ((int) $employee['trangThai'] === 1) {
        echo json_encode(['success' => true, 'message' => 'Tài khoản đang hoạt động.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE NHANVIEN SET trangThai = 1 WHERE maNV = :maNV');
    $stmt->execute(['maNV' => $maNV]);

    echo json_encode(['success' => true, 'message' => 'Đã mở khóa tài khoản nhân viên.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể mở khóa tài khoản nhân viên.']);
}
