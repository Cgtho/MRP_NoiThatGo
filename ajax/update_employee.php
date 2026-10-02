<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa nhân viên.']);
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
    echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.']);
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

    // Không cho phép quản lý kho chỉnh sửa thông tin của quản lý kho khác.
    if ((int) $employee['vaiTro'] === 0 && !$isSelf) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không thể chỉnh sửa thông tin của quản lý kho khác.']);
        exit;
    }

    // Không cho phép tự hạ cấp vai trò của chính mình.
    if ($isSelf && $vaiTro !== '0') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Bạn không thể tự hạ cấp vai trò của chính mình.']);
        exit;
    }

    // Không cho phép hạ quyền quản lý kho cuối cùng của hệ thống.
    if ((int) $employee['vaiTro'] === 0 && $vaiTro === '1') {
        $managerCount = (int) $pdo->query('SELECT COUNT(*) FROM NHANVIEN WHERE vaiTro = 0')->fetchColumn();
        if ($managerCount <= 1) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Không thể chuyển vai trò: hệ thống cần ít nhất một quản lý kho.']);
            exit;
        }
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
            'sdt' => $sdt,
            'diaChi' => $diaChi,
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
            'sdt' => $sdt,
            'diaChi' => $diaChi,
            'maNV' => $maNV,
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Đã cập nhật thông tin nhân viên.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật nhân viên.']);
}