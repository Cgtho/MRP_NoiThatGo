<?php 
require_once '../config/db.php';
require_once '../includes/header.php'; 

// Lấy danh sách thành phẩm
$stmt = $pdo->query("SELECT * FROM THANHPHAM");
$thanhPhams = $stmt->fetchAll();
?>

<div class="row">
    <!-- Cột trái: Danh sách sản phẩm -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white fw-bold">
                Danh sách Thành Phẩm
            </div>
            <ul class="list-group list-group-flush" id="productList">
                <?php foreach($thanhPhams as $tp): ?>
                    <a href="#" class="list-group-item list-group-item-action product-item" data-matp="<?= $tp['maTP'] ?>">
                        <?= $tp['maTP'] ?> - <?= $tp['tenTP'] ?>
                    </a>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Cột phải: Chi tiết BOM & Mô phỏng -->
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-success text-white fw-bold">
                Mô phỏng sản xuất & Kiểm tra tồn kho NVL
            </div>
            <div class="card-body" id="bomSimulationArea" style="display:none;">
                <h5 id="selectedProductName" class="text-primary"></h5>
                
                <div class="row align-items-center mb-3">
                    <div class="col-auto">
                        <label class="col-form-label"><b>Số lượng TP cần làm:</b></label>
                    </div>
                    <div class="col-auto">
                        <input type="number" id="simQty" class="form-control" value="1" min="1">
                    </div>
                </div>

                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Mã NVL</th>
                            <th>Tên Vật Tư</th>
                            <th>Định mức (1 TP)</th>
                            <th>Tổng cần dùng</th>
                            <th>Tồn kho hiện tại</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="bomTableBody">
                        <!-- Đổ dữ liệu từ AJAX vào đây -->
                    </tbody>
                </table>
                <button class="btn btn-warning" id="btnCreateRequest" style="display:none;">Tạo phiếu yêu cầu xuất kho NVL</button>
            </div>
            <div class="card-body text-center text-muted" id="bomEmptyState">
                Vui lòng chọn một Thành phẩm bên trái để xem định mức.
            </div>
        </div>
    </div>
</div>

<!-- Import JS cụ thể cho màn hình BOM -->
<script src="../assets/js/bom.js"></script>

<!-- Nút đóng container của header -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>