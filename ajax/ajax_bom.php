<?php
session_start();

if (!isset($_SESSION['current_user'], $_SESSION['user_name'], $_SESSION['role'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tiếp tục.']);
    exit;
}

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (isset($_GET['action']) && $_GET['action'] == 'get_bom') {
    $maTP = $_GET['maTP'] ?? '';
    
    if (empty($maTP)) {
        echo json_encode(["status" => "error", "message" => "Thiếu mã thành phẩm"]);
        exit;
    }

    try {
        // Query lấy chi tiết BOM và số lượng tồn kho của NVL đó
        $sql = "SELECT 
                    ct.maNVL, 
                    nvl.tenNVL,
                    dvt.tenDVT AS donViTinh,
                    ct.soLuong AS dinhMuc, 
                    nvl.soLuong AS tonKho
                FROM CHITIETTHANHPHAM ct
                JOIN NGUYENVATLIEU nvl ON ct.maNVL = nvl.maNVL
                LEFT JOIN DONVITINH dvt ON nvl.maDVT = dvt.maDVT
                WHERE ct.maTP = :maTP";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['maTP' => $maTP]);
        $bomData = $stmt->fetchAll();

        echo json_encode(["status" => "success", "data" => $bomData]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>