<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$isManager = (int) $_SESSION['role'] === 0;
$currentUser = (string) $_SESSION['current_user'];
$notifications = [];

try {
    if ($isManager) {
        $queries = [
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
                'sql' => 'SELECT maYC AS code, ngayYC AS createdAt
                         FROM YEUCAU
                         WHERE trangThai <> 2
                         ORDER BY ngayYC DESC, maYC DESC',
                'title' => 'Lệnh sản xuất cần xử lý',
                'message' => 'Bạn có lệnh sản xuất cần kiểm tra hoặc thực hiện.',
                'href' => '../views/production_orders.php?maYC=',
                'icon' => 'fa-clipboard-list',
                'color' => 'text-indigo-500',
            ],
        ];
    }

    foreach ($queries as $query) {
        $statement = $pdo->prepare($query['sql']);
        if (!$isManager && strpos($query['sql'], ':maNV') !== false) {
            $statement->execute(['maNV' => $currentUser]);
        } else {
            $statement->execute();
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
