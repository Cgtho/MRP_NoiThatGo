<?php 
require_once '../config/db.php';
require_once '../includes/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <h4 class="alert-heading">👋 Xin chào, <?php echo $_SESSION['user_name']; ?>!</h4>
            <p>Chào mừng bạn đến với Hệ thống Quản lý Kho & Sản xuất Nội thất.</p>
            <hr>
            <p class="mb-0">Vai trò hiện tại của bạn là: <strong><?php echo ($_SESSION['role'] == 0 ? 'Quản lý' : 'Nhân viên'); ?></strong>. Bạn có thể sử dụng Menu ở trên để điều hướng tới chức năng <strong>Định Mức BOM</strong> đã được xây dựng trước đó.</p>
        </div>
    </div>
</div>

<?php 
// require_once '../includes/footer.php'; // Nếu bạn có file footer
?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>