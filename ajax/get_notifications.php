<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.']);
    exit;
}

require_once __DIR__ . '/../config.php';

$isManager = (int) $_SESSION['role'] === 0;
$currentUser = (string) $_SESSION['current_user'];
$notifications = [];

try {
    if ($isManager) {
        $queries = [
            [
                'sql' => 'SELECT maPN AS code, ngayNhap AS createdAt
                         FROM PHIEUNHAPKHO
                         WHERE trangThai = 0
                         ORDER BY ngayNhap DESC, maPN DESC',
                'title' => 'Phiếu nhập kho chờ duyệt',
                'message' => 'Có phiếu nhập NVL cần xử lý.',
                'href' => '../views/import_materials.php?status=pending&maPN=',
                'icon' => 'fa-box-open',
                'color' => 'text-emerald-500',
            ],
            [
                'sql' => 'SELECT maPX AS code, ngayXuat AS createdAt
                         FROM PHIEUXUATNVL
                         WHERE trangThai = 0
                         ORDER BY ngayXuat DESC, maPX DESC',
                'title' => 'Phiếu xuất NVL chờ duyệt',
                'message' => 'Có phiếu xuất nguyên vật liệu cần xử lý.',
                'href' => '../views/export_materials.php?status=pending&maPX=',
                'icon' => 'fa-dolly',
                'color' => 'text-amber-500',
            ],
            [
                'sql' => 'SELECT maPX AS code, ngayXuat AS createdAt
                         FROM PHIEUXUATTP
                         WHERE trangThai = 0
                         ORDER BY ngayXuat DESC, maPX DESC',
                'title' => 'Phiếu xuất thành phẩm chờ duyệt',
                'message' => 'Có phiếu xuất thành phẩm cần xử lý.',
                'href' => '../views/export_products.php?status=pending&maPX=',
                'icon' => 'fa-truck-fast',
                'color' => 'text-sky-500',
            ],
        ];
    } else {
        $queries = [
            [
                'sql' => 'SELECT maPN AS code, ngayNhap AS createdAt
                         FROM PHIEUNHAPKHO
                         WHERE trangThai = 0 AND maNV = :maNV
                         ORDER BY ngayNhap DESC, maPN DESC',
                'title' => 'Phiếu nhập kho đang chờ duyệt',
                'message' => 'Phiếu nhập NVL của bạn đang được xử lý.',
                'href' => '../views/import_materials.php?status=pending&maPN=',
                'icon' => 'fa-box-open',
                'color' => 'text-emerald-500',
            ],
            [
                'sql' => 'SELECT maPX AS code, ngayXuat AS createdAt
                         FROM PHIEUXUATNVL
                         WHERE trangThai = 0 AND maNV = :maNV
                         ORDER BY ngayXuat DESC, maPX DESC',
                'title' => 'Phiếu xuất NVL đang chờ duyệt',
                'message' => 'Phiếu xuất nguyên vật liệu của bạn đang được xử lý.',
                'href' => '../views/export_materials.php?status=pending&maPX=',
                'icon' => 'fa-dolly',
                'color' => 'text-amber-500',
            ],
            [
                'sql' => 'SELECT maPX AS code, ngayXuat AS createdAt
                         FROM PHIEUXUATTP
                         WHERE trangThai = 0 AND maNV = :maNV
                         ORDER BY ngayXuat DESC, maPX DESC',
                'title' => 'Phiếu xuất thành phẩm đang chờ duyệt',
                'message' => 'Phiếu xuất thành phẩm của bạn đang được xử lý.',
                'href' => '../views/export_products.php?status=pending&maPX=',
                'icon' => 'fa-truck-fast',
                'color' => 'text-sky-500',
            ],
        ];
    }

    foreach ($queries as $query) {
        $statement = $pdo->prepare($query['sql']);
        if ($isManager) {
            $statement->execute();
        } else {
            $statement->execute(['maNV' => $currentUser]);
        }

        foreach ($statement->fetchAll() as $row) {
            $notifications[] = [
                'title' => $query['title'],
                'message' => $query['message'],
                'code' => $row['code'],
                'createdAt' => $row['createdAt'],
                'href' => $query['href'] . urlencode((string) $row['code']),
                'icon' => $query['icon'],
                'color' => $query['color'],
            ];
        }
    }

    usort($notifications, static fn (array $left, array $right): int => strcmp(
        (string) $right['createdAt'],
        (string) $left['createdAt']
    ));
    $notificationCount = count($notifications);
    $notifications = array_slice($notifications, 0, 10);

    echo json_encode([
        'success' => true,
        'count' => $notificationCount,
        'notifications' => $notifications,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể tải thông báo.']);
}
