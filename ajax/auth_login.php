<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

$rememberLogin = isset($_POST['rememberAccount']) && $_POST['rememberAccount'] === 'on';
$sessionLifetime = $rememberLogin ? 60 * 60 * 24 * 30 : 0;

if ($rememberLogin) {
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
}

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../config.php';

$employeeId = strtoupper(trim((string) ($_POST['employeeId'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($employeeId === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ mã nhân viên và mật khẩu.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT maNV, hoTen, vaiTro, trangThai, matKhau
         FROM NHANVIEN
         WHERE maNV = :maNV
         LIMIT 1'
    );
    $stmt->execute(['maNV' => $employeeId]);
    $employee = $stmt->fetch();

    if (!$employee || !password_verify($password, $employee['matKhau'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Mã nhân viên hoặc mật khẩu không đúng.']);
        exit;
    }

    if ((int) $employee['trangThai'] !== 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Tài khoản đã bị khóa hoặc không còn hoạt động.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['current_user'] = $employee['maNV'];
    $_SESSION['user_name'] = $employee['hoTen'];
    $_SESSION['role'] = (int) $employee['vaiTro'];

    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công.',
        'redirect' => './views/dashboard.php',
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể xác thực lúc này. Vui lòng thử lại sau.']);
}
