<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản lý dữ liệu gốc.']);
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

$entity = (string) ($_POST['entity'] ?? '');
$code = trim((string) ($_POST['code'] ?? ''));
$action = (string) ($_POST['action'] ?? '');

$definitions = [
    'material' => [
        'table' => 'NGUYENVATLIEU',
        'codeColumn' => 'maNVL',
        'label' => 'nguyên vật liệu',
        'references' => [
            'CHITIETTHANHPHAM' => 'maNVL',
            'CHITIETPHIEUNHAP' => 'maNVL',
            'CHITIETPHIEUXUATNVL' => 'maNVL',
        ],
    ],
    'product' => [
        'table' => 'THANHPHAM',
        'codeColumn' => 'maTP',
        'label' => 'thành phẩm',
        'references' => [
            'CHITIETTHANHPHAM' => 'maTP',
            'CHITIETPHIEUXUATTP' => 'maTP',
            'CHITIETYEUCAU' => 'maTP',
        ],
    ],
];

if (!isset($definitions[$entity]) || $code === '' || !in_array($action, ['hide', 'show', 'delete'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu thao tác không hợp lệ.']);
    exit;
}

$definition = $definitions[$entity];
$table = $definition['table'];
$codeColumn = $definition['codeColumn'];

try {
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        "SELECT {$codeColumn}, isDeleted, trangThai FROM {$table} WHERE {$codeColumn} = :code FOR UPDATE"
    );
    $statement->execute(['code' => $code]);
    $record = $statement->fetch();

    if (!$record) {
        throw new RuntimeException('Không tìm thấy ' . $definition['label'] . '.');
    }

    if ($action === 'delete') {
        if ((int) $record['isDeleted'] !== 1) {
            throw new RuntimeException('Không thể xóa vì ' . $definition['label'] . ' đã từng được sử dụng.');
        }

        foreach ($definition['references'] as $referenceTable => $referenceColumn) {
            $referenceCheck = $pdo->prepare(
                "SELECT 1 FROM {$referenceTable} WHERE {$referenceColumn} = :code LIMIT 1"
            );
            $referenceCheck->execute(['code' => $code]);
            if ($referenceCheck->fetchColumn()) {
                throw new RuntimeException('Không thể xóa vì ' . $definition['label'] . ' đã có dữ liệu liên quan.');
            }
        }

        $delete = $pdo->prepare("DELETE FROM {$table} WHERE {$codeColumn} = :code AND isDeleted = 1");
        $delete->execute(['code' => $code]);
        $message = 'Đã xóa vĩnh viễn ' . $definition['label'] . '.';
    } else {
        $status = $action === 'show' ? 1 : 0;
        $update = $pdo->prepare(
            "UPDATE {$table} SET trangThai = :status WHERE {$codeColumn} = :code"
        );
        $update->execute(['status' => $status, 'code' => $code]);
        $message = $status === 1
            ? 'Đã hiển thị lại ' . $definition['label'] . '.'
            : 'Đã ẩn ' . $definition['label'] . '.';
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật dữ liệu gốc.']);
}
