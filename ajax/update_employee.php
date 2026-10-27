<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || !in_array((int) $_SESSION['role'], [0, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa nhân viên.']);
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
$hoTen = trim((string) ($_POST['hoTen'] ?? ''));
$vaiTro = (string) ($_POST['vaiTro'] ?? '');
$trangThai = (string) ($_POST['trangThai'] ?? '1');
$sdt = trim((string) ($_POST['sdt'] ?? ''));
$diaChi = trim((string) ($_POST['diaChi'] ?? ''));
$matKhau = (string) ($_POST['matKhau'] ?? '');

if (!preg_match('/^[A-Z0-9]{2,10}$/', $maNV)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.']);
    exit;
}

if ($hoTen === '' || mb_strlen($hoTen) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Họ tên phải từ 1 đến 50 ký tự.']);
    exit;
}

if (!in_array($vaiTro, ['0', '1', '2'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vai trò không hợp lệ.']);
    exit;
}

if (!in_array($trangThai, ['0', '1'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
    exit;
}

if ($sdt !== '' && !preg_match('/^[0-9]{9,12}$/', $sdt)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Số điện thoại phải gồm 9 đến 12 chữ số.']);
    exit;
}

if ($diaChi !== '' && mb_strlen($diaChi) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Địa chỉ phải từ 1 đến 50 ký tự.']);
    exit;
}

if ($matKhau !== '' && mb_strlen($matKhau) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT maNV, vaiTro FROM NHANVIEN WHERE maNV = :maNV LIMIT 1');
    $stmt->execute(['maNV' => $maNV]);
    $employee = $stmt->fetch();

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên cần chỉnh sửa.']);
        exit;
    }

    $currentUser = (string) ($_SESSION['current_user'] ?? '');
    $isSelf = strcasecmp($maNV, $currentUser) === 0;

    $currentRole = (int) $_SESSION['role'];

    if ((int) $vaiTro !== (int) $employee['vaiTro']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Không thể thay đổi vai trò tài khoản sau khi tạo.']);
        exit;
    }

    // Chỉ admin được quản lý tài khoản admin; quản lý kho không được sửa quản lý khác.
    if ((int) $employee['vaiTro'] === 2 && $currentRole !== 2 && !$isSelf) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không thể chỉnh sửa tài khoản quản trị hệ thống.']);
        exit;
    }
    if ((int) $employee['vaiTro'] === 0 && !$isSelf && $currentRole !== 2) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không thể chỉnh sửa thông tin của quản lý kho khác.']);
        exit;
    }

    // Không cho phép tự khóa tài khoản của chính mình.
    if ($isSelf && $trangThai === '0') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Bạn không thể tự khóa tài khoản của chính mình.']);
        exit;
    }

    if ($matKhau !== '') {
        $stmt = $pdo->prepare(
            'UPDATE NHANVIEN
             SET hoTen = :hoTen, vaiTro = :vaiTro, trangThai = :trangThai, sdt = :sdt, diaChi = :diaChi, matKhau = :matKhau
             WHERE maNV = :maNV'
        );
        $stmt->execute([
            'hoTen' => $hoTen,
            'vaiTro' => (int) $vaiTro,
            'trangThai' => (int) $trangThai,
            'sdt' => $sdt !== '' ? $sdt : null,
            'diaChi' => $diaChi !== '' ? $diaChi : null,
            'matKhau' => password_hash($matKhau, PASSWORD_DEFAULT),
            'maNV' => $maNV,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE NHANVIEN
             SET hoTen = :hoTen, vaiTro = :vaiTro, trangThai = :trangThai, sdt = :sdt, diaChi = :diaChi
             WHERE maNV = :maNV'
        );
        $stmt->execute([
            'hoTen' => $hoTen,
            'vaiTro' => (int) $vaiTro,
            'trangThai' => (int) $trangThai,
            'sdt' => $sdt !== '' ? $sdt : null,
            'diaChi' => $diaChi !== '' ? $diaChi : null,
            'maNV' => $maNV,
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Đã cập nhật thông tin nhân viên.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật nhân viên.']);
}