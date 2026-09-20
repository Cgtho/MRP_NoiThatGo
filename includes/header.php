<?php
session_start();
if(!isset($_SESSION['current_user'])) {
    $_SESSION['current_user'] = 'NV02'; 
    $_SESSION['role'] = 1;
    $_SESSION['user_name'] = 'Nguyễn Văn Thợ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Kho & Sản Xuất</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">🪵 HỆ THỐNG KHO & SẢN XUẤT</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="views/dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="views/bom_formula.php">Định Mức BOM</a></li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                Phiếu Xuất <span class="badge bg-danger" id="badge-pending-export">0</span>
            </a>
        </li>
      </ul>

      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-warning fw-bold" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
            👤 <?php echo $_SESSION['current_user'] . ' - ' . $_SESSION['user_name'] . ' (' . ($_SESSION['role'] == 0 ? 'Quản lý' : 'Nhân viên') . ')'; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item switch-role" href="#" data-nv="NV01" data-name="Trần Văn Quản Lý" data-role="0">Đổi sang: NV01 - Quản lý</a></li>
            <li><a class="dropdown-item switch-role" href="#" data-nv="NV02" data-name="Nguyễn Văn Thợ" data-role="1">Đổi sang: NV02 - Nhân viên</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid mt-3">