<?php
declare(strict_types=1);

/**
 * API thống kê MRP (Material Requirement Planning).
 *
 * Tính nhu cầu nguyên vật liệu cho các lệnh sản xuất đang hoạt động
 * (YEUCAU.trangThai IN (0,1)) theo công thức:
 *   Nhu cầu NVL = SUM( CHITIETYEUCAU.soLuong * CHITIETTHANHPHAM.soLuong )
 *
 * Trả về JSON gồm: KPI tổng quan, dữ liệu biểu đồ top 10, và bảng cân đối vật tư.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['current_user'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config.php';

try {
    // 1. Nhu cầu vật tư theo từng mã NVL cho các lệnh sản xuất đang hoạt động.
    $demandStatement = $pdo->query(
        'SELECT cttp.maNVL AS maNVL, SUM(ctyc.soLuong * cttp.soLuong) AS nhuCau
         FROM YEUCAU yc
         INNER JOIN CHITIETYEUCAU ctyc ON ctyc.maYC = yc.maYC
         INNER JOIN CHITIETTHANHPHAM cttp ON cttp.maTP = ctyc.maTP
         WHERE yc.trangThai IN (0, 1)
         GROUP BY cttp.maNVL'
    );
    $demandMap = [];
    foreach ($demandStatement->fetchAll() as $row) {
        $demandMap[(string) $row['maNVL']] = (int) $row['nhuCau'];
    }

    // 2. Toàn bộ nguyên vật liệu: tồn kho thực tế + nhu cầu tương ứng.
    $materialStatement = $pdo->query(
        'SELECT nvl.maNVL, nvl.tenNVL, dvt.tenDVT, nvl.soLuong AS tonKho
         FROM NGUYENVATLIEU nvl
         INNER JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
         ORDER BY nvl.maNVL ASC'
    );

    $materials = [];
    $totalRequirement = 0;
    $shortageKinds = 0;
    foreach ($materialStatement->fetchAll() as $row) {
        $maNvl = (string) $row['maNVL'];
        $tonKho = (int) $row['tonKho'];
        $nhuCau = $demandMap[$maNvl] ?? 0;
        $thieuHut = $nhuCau - $tonKho;
        if ($thieuHut < 0) {
            $thieuHut = 0;
        }

        $totalRequirement += $nhuCau;
        if ($thieuHut > 0) {
            $shortageKinds++;
        }

        $materials[] = [
            'maNVL' => $maNvl,
            'tenNVL' => (string) $row['tenNVL'],
            'tenDVT' => (string) $row['tenDVT'],
            'tonKho' => $tonKho,
            'nhuCau' => $nhuCau,
            'thieuHut' => $thieuHut,
            'trangThai' => $thieuHut > 0 ? 'thieu' : 'du',
        ];
    }

    // Sắp xếp theo nhu cầu giảm dần (vật tư dùng nhiều nhất lên đầu), rồi tới mã.
    usort($materials, static function (array $a, array $b): int {
        if ($a['nhuCau'] === $b['nhuCau']) {
            return strcmp($a['maNVL'], $b['maNVL']);
        }
        return $b['nhuCau'] <=> $a['nhuCau'];
    });

    // 3. Top 10 vật tư dùng nhiều nhất cho biểu đồ cột.
    $top10 = array_slice($materials, 0, 10);
    $chart = [
        'labels' => array_map(static fn (array $m): string => $m['maNVL'], $top10),
        'demand' => array_map(static fn (array $m): int => $m['nhuCau'], $top10),
        'stock' => array_map(static fn (array $m): int => $m['tonKho'], $top10),
    ];

    // 4. Đếm số lệnh sản xuất theo trạng thái cho biểu đồ tròn.
    $orderStatement = $pdo->query(
        'SELECT trangThai, COUNT(*) AS soLuong FROM YEUCAU GROUP BY trangThai'
    );
    $orders = ['pending' => 0, 'inProgress' => 0, 'completed' => 0];
    foreach ($orderStatement->fetchAll() as $row) {
        switch ((int) $row['trangThai']) {
            case 0:
                $orders['pending'] = (int) $row['soLuong'];
                break;
            case 1:
                $orders['inProgress'] = (int) $row['soLuong'];
                break;
            case 2:
                $orders['completed'] = (int) $row['soLuong'];
                break;
        }
    }

    echo json_encode([
        'success' => true,
        'kpis' => [
            'totalRequirement' => $totalRequirement,
            'shortageKinds' => $shortageKinds,
            'orders' => $orders,
        ],
        'chart' => $chart,
        'materials' => $materials,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể tải dữ liệu thống kê MRP.'], JSON_UNESCAPED_UNICODE);
}
