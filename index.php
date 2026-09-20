<?php
// Bắt đầu session để kiểm tra trạng thái đăng nhập
session_start();

/**
 * FILE: index.php
 * CHỨC NĂNG: Điều hướng người dùng khi truy cập vào thư mục gốc của dự án.
 */

// 1. Kiểm tra trạng thái đăng nhập (Giả lập)
// Trong thực tế, bạn sẽ có một trang login.php. Nếu người dùng chưa đăng nhập, 
// bạn sẽ uncomment đoạn code dưới đây:
/*
if (!isset($_SESSION['current_user'])) {
    header("Location: views/login.php");
    exit();
}
*/

// 2. Nếu đã đăng nhập (hoặc sử dụng user mặc định), chuyển hướng thẳng vào Dashboard
// Lệnh header() của PHP dùng để gửi HTTP header điều hướng trình duyệt
header("Location: views/dashboard.php");
exit(); // Luôn gọi exit() sau khi dùng header() để dừng việc thực thi các code bên dưới
?>