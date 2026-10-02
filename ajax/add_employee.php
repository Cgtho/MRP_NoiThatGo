<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm nhân viên.']);
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
$hoTen = trim((string) ($_POST['hoTen'] ?? ''));
$vaiTro = (string) ($_POST['vaiTro'] ?? '');
$trangThai = (string) ($_POST['trangThai'] ?? '2');
$sdt = trim((string) ($_POST['sdt'] ?? ''));
$diaChi = trim((string) ($_POST['diaChi'] ?? ''));
$matKhau = (string) ($_POST['matKhau'] ?? '');

if (!preg_match('/^[A-Z0-9]{2,10}$/', $maNV)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã nhân viên gồm 2 đến 10 ký tự chữ hoặc số.']);
    exit;
}

if ($hoTen === '' || mb_strlen($hoTen) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Họ tên phải từ 1 đến 50 ký tự.']);
    exit;
}

if (!in_array($vaiTro, ['0', '1'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vai trò không hợp lệ.']);
    exit;
}

if (!in_array($trangThai, ['0', '1', '2'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
    exit;
}

if (!preg_match('/^[0-9]{9,12}$/', $sdt)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Số điện thoại phải gồm 9 đến 12 chữ số.']);
    exit;
}

if ($diaChi === '' || mb_strlen($diaChi) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Địa chỉ phải từ 1 đến 50 ký tự.']);
    exit;
}

if (mb_strlen($matKhau) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
    exit;
}

try {
    $check = $pdo->prepare('SELECT maNV FROM NHANVIEN WHERE maNV = :maNV LIMIT 1');
    $check->execute(['maNV' => $maNV]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Mã nhân viên này đã tồn tại.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO NHANVIEN (maNV, hoTen, vaiTro, trangThai, matKhau, sdt, diaChi)
         VALUES (:maNV, :hoTen, :vaiTro, :trangThai, :matKhau, :sdt, :diaChi)'
    );
    $stmt->execute([
        'maNV' => $maNV,
        'hoTen' => $hoTen,
        'vaiTro' => (int) $vaiTro,
        'trangThai' => (int) $trangThai,
        'matKhau' => password_hash($matKhau, PASSWORD_DEFAULT),
        'sdt' => $sdt,
        'diaChi' => $diaChi,
    ]);

    echo json_encode(['success' => true, 'message' => 'Đã thêm nhân viên.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu nhân viên.']);
}