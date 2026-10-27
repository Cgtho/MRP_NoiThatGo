<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.']);
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

$currentPassword = (string) ($_POST['currentPassword'] ?? '');
$newPassword = (string) ($_POST['newPassword'] ?? '');
$confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin.']);
    exit;
}

if (mb_strlen($newPassword) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mật khẩu mới không khớp.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT matKhau FROM NHANVIEN WHERE maNV = :maNV AND trangThai = 1 LIMIT 1');
    $stmt->execute(['maNV' => $_SESSION['current_user']]);
    $employee = $stmt->fetch();

    if (!$employee || !password_verify($currentPassword, $employee['matKhau'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE NHANVIEN SET matKhau = :matKhau WHERE maNV = :maNV');
    $stmt->execute([
        'matKhau' => password_hash($newPassword, PASSWORD_DEFAULT),
        'maNV' => $_SESSION['current_user'],
    ]);

    echo json_encode(['success' => true, 'message' => 'Đã đổi mật khẩu thành công.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể đổi mật khẩu lúc này.']);
}
