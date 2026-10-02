<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa nhân viên.']);
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

$maNV = strtoupper(trim((string) ($_POST['maNV'] ?? '')));

if (!preg_match('/^[A-Z0-9]{2,10}$/', $maNV)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.']);
    exit;
}

if (strcasecmp($maNV, (string) $_SESSION['current_user']) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bạn không thể xóa tài khoản đang đăng nhập.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT maNV, hoTen, vaiTro FROM NHANVIEN WHERE maNV = :maNV LIMIT 1');
    $stmt->execute(['maNV' => $maNV]);
    $employee = $stmt->fetch();

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên cần xóa.']);
        exit;
    }

    // Không cho phép xóa quản lý kho cuối cùng của hệ thống.
    if ((int) $employee['vaiTro'] === 0) {
        $managerCount = (int) $pdo->query('SELECT COUNT(*) FROM NHANVIEN WHERE vaiTro = 0')->fetchColumn();
        if ($managerCount <= 1) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Không thể xóa: hệ thống cần ít nhất một quản lý kho.']);
            exit;
        }
    }

    $stmt = $pdo->prepare('DELETE FROM NHANVIEN WHERE maNV = :maNV');
    $stmt->execute(['maNV' => $maNV]);

    echo json_encode(['success' => true, 'message' => 'Đã xóa nhân viên.']);
} catch (PDOException $e) {
    // 23000 = vi phạm ràng buộc khóa ngoại (nhân viên đã có phiếu xuất / lệnh sản xuất).
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Không thể xóa: nhân viên đã có phiếu xuất hoặc lệnh sản xuất liên quan.']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể xóa nhân viên.']);
}