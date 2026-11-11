<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền từ chối phiếu xuất NVL.']);
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

$exportCode = strtoupper(trim((string) ($_POST['maPX'] ?? '')));
$reason = trim((string) ($_POST['lyDoTuChoi'] ?? ''));

if (!preg_match('/^PXN[0-9]+$/', $exportCode)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mã phiếu xuất NVL không hợp lệ.']);
    exit;
}
if ($reason === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do từ chối.']);
    exit;
}
if (function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') > 200 : strlen($reason) > 200) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Lý do từ chối không được dài quá 200 ký tự.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'SELECT maPX, trangThai, ghiChu
         FROM PHIEUXUATNVL
         WHERE maPX = :maPX
         FOR UPDATE'
    );
    $statement->execute(['maPX' => $exportCode]);
    $export = $statement->fetch();

    if (!$export) {
        throw new RuntimeException('Không tìm thấy phiếu xuất NVL.');
    }
    if ((int) $export['trangThai'] !== 0) {
        throw new RuntimeException('Phiếu xuất này đã được xử lý trước đó.');
    }

    $note = trim((string) ($export['ghiChu'] ?? ''));
    $rejectionNote = 'Từ chối: ' . $reason;
    $newNote = trim($note . ($note !== '' ? ' | ' : '') . $rejectionNote);
    if (function_exists('mb_substr')) {
        $newNote = mb_substr($newNote, 0, 255, 'UTF-8');
    } else {
        $newNote = substr($newNote, 0, 255);
    }

    $update = $pdo->prepare(
        'UPDATE PHIEUXUATNVL
         SET trangThai = 2, maQL = :maQL, ngayDuyet = NOW(), ghiChu = :ghiChu
         WHERE maPX = :maPX AND trangThai = 0'
    );
    $update->execute([
        'maQL' => $_SESSION['current_user'],
        'ghiChu' => $newNote,
        'maPX' => $exportCode,
    ]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Phiếu xuất đã được xử lý bởi người dùng khác.');
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Đã từ chối phiếu xuất NVL.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code($exception instanceof RuntimeException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Không thể từ chối phiếu xuất NVL. Vui lòng thử lại.',
    ]);
}
