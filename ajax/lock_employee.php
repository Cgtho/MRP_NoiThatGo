<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || !in_array((int) $_SESSION['role'], [0, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền khóa nhân viên.']);
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

if (strcasecmp($maNV, (string) $_SESSION['current_user']) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bạn không thể tự khóa tài khoản đang đăng nhập.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT maNV, vaiTro, trangThai FROM NHANVIEN WHERE maNV = :maNV LIMIT 1');
    $stmt->execute(['maNV' => $maNV]);
    $employee = $stmt->fetch();

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên cần khóa.']);
        exit;
    }

    if ((int) $_SESSION['role'] === 0 && (int) $employee['vaiTro'] === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Quản lý kho không thể khóa quản lý kho khác.']);
        exit;
    }

    if ((int) $employee['trangThai'] === 0) {
        echo json_encode(['success' => true, 'message' => 'Tài khoản đã được khóa trước đó.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE NHANVIEN SET trangThai = 0 WHERE maNV = :maNV');
    $stmt->execute(['maNV' => $maNV]);

    echo json_encode(['success' => true, 'message' => 'Đã khóa tài khoản nhân viên.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể khóa tài khoản nhân viên.']);
}
