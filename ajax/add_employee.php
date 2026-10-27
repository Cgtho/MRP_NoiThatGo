<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || !in_array((int) $_SESSION['role'], [0, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm nhân viên.']);
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

$hoTen = trim((string) ($_POST['hoTen'] ?? ''));
$vaiTro = (string) ($_POST['vaiTro'] ?? '');
$sdt = trim((string) ($_POST['sdt'] ?? ''));
$diaChi = trim((string) ($_POST['diaChi'] ?? ''));

if ($hoTen === '' || mb_strlen($hoTen) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Họ tên phải từ 1 đến 50 ký tự.']);
    exit;
}

if (!in_array($vaiTro, ['0', '1', '2'], true)
    || ((int) $_SESSION['role'] === 0 && $vaiTro !== '1')
    || ($vaiTro === '2' && (int) $_SESSION['role'] !== 2)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền tạo tài khoản với vai trò này.']);
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

try {
    $prefix = ['0' => 'QL', '1' => 'NV', '2' => 'AD'][$vaiTro];
    $pdo->beginTransaction();

    $lastCode = $pdo->prepare(
        'SELECT maNV
         FROM NHANVIEN
         WHERE maNV LIKE :prefix
         ORDER BY CAST(SUBSTRING(maNV, 3) AS UNSIGNED) DESC
         LIMIT 1
         FOR UPDATE'
    );
    $lastCode->execute(['prefix' => $prefix . '%']);
    $lastCodeValue = (string) ($lastCode->fetchColumn() ?: '');
    $lastNumber = preg_match('/^' . $prefix . '([0-9]{3})$/', $lastCodeValue, $matches)
        ? (int) $matches[1]
        : 0;

    if ($lastNumber >= 999) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Đã đạt giới hạn số mã tài khoản cho vai trò này.']);
        exit;
    }

    $maNV = $prefix . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare(
        'INSERT INTO NHANVIEN (maNV, hoTen, vaiTro, trangThai, matKhau, sdt, diaChi)
         VALUES (:maNV, :hoTen, :vaiTro, :trangThai, :matKhau, :sdt, :diaChi)'
    );
    $stmt->execute([
        'maNV' => $maNV,
        'hoTen' => $hoTen,
        'vaiTro' => (int) $vaiTro,
        'trangThai' => 1,
        'matKhau' => password_hash('123456', PASSWORD_DEFAULT),
        'sdt' => $sdt,
        'diaChi' => $diaChi,
    ]);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => "Đã thêm tài khoản {$maNV}.", 'maNV' => $maNV]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Không thể tạo mã tài khoản do bị trùng. Vui lòng thử lại.']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu nhân viên.']);
}