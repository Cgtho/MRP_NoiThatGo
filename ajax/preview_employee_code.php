<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['current_user'], $_SESSION['role']) || !in_array((int) $_SESSION['role'], [0, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$vaiTro = (string) ($_GET['vaiTro'] ?? '');
if (!in_array($vaiTro, ['0', '1', '2'], true)
    || ((int) $_SESSION['role'] === 0 && $vaiTro !== '1')) {
    http_response_code(422);
    echo json_encode(['success' => false]);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $prefix = ['0' => 'QL', '1' => 'NV', '2' => 'AD'][$vaiTro];
    $lastCode = $pdo->prepare(
        'SELECT maNV
         FROM NHANVIEN
         WHERE maNV LIKE :prefix
         ORDER BY CAST(SUBSTRING(maNV, 3) AS UNSIGNED) DESC
         LIMIT 1'
    );
    $lastCode->execute(['prefix' => $prefix . '%']);
    $lastCodeValue = (string) ($lastCode->fetchColumn() ?: '');
    $lastNumber = preg_match('/^' . $prefix . '([0-9]{3})$/', $lastCodeValue, $matches)
        ? (int) $matches[1]
        : 0;

    if ($lastNumber >= 999) {
        http_response_code(409);
        echo json_encode(['success' => false]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'maNV' => $prefix . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
