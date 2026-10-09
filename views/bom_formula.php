<?php
require_once '../includes/header.php';
require_once '../config.php';
require_once '../includes/csrf.php';

$isManager = (int) ($_SESSION['role'] ?? 1) === 0;
$products = [];
$materials = [];
$units = [];
$productError = null;
$nextProductCode = 'TP001';

try {
    $stmt = $pdo->query(
        'SELECT tp.maTP, tp.tenTP, tp.donViTinh, tp.soLuong,
                COUNT(ct.maNVL) AS soLuongVatTu
         FROM THANHPHAM tp
         LEFT JOIN CHITIETTHANHPHAM ct ON ct.maTP = tp.maTP
         GROUP BY tp.maTP, tp.tenTP, tp.donViTinh, tp.soLuong
         ORDER BY tp.maTP ASC'
    );
    $products = $stmt->fetchAll();
    $materials = $pdo->query(
        'SELECT nvl.maNVL, nvl.tenNVL, nvl.soLuong, dvt.tenDVT
         FROM NGUYENVATLIEU nvl
         LEFT JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
         ORDER BY nvl.tenNVL ASC'
    )->fetchAll();
    $units = $pdo->query('SELECT maDVT, tenDVT FROM DONVITINH ORDER BY tenDVT ASC')->fetchAll();
    $nextProductNumber = (int) $pdo->query(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(maTP, 3) AS UNSIGNED)), 0) + 1
         FROM THANHPHAM
         WHERE maTP REGEXP '^TP[0-9]+$'"
    )->fetchColumn();
    $nextProductCode = 'TP' . str_pad((string) $nextProductNumber, 3, '0', STR_PAD_LEFT);
} catch (PDOException $e) {
    $productError = 'Không thể tải danh sách thành phẩm từ cơ sở dữ liệu.';
}
?>

<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3">
        <div class="bg-blue-100 text-blue-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Định Mức Cấu Thành Sản Phẩm (BOM)</h2>
            <p class="text-xs text-slate-500">Chọn một thành phẩm để xem chi tiết định mức nguyên vật liệu.</p>
        </div>
    </div>
    <?php if ($isManager): ?>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2" id="btn-open-add-product" type="button">
            <i class="fa-solid fa-plus"></i> Thêm Sản Phẩm & Định Mức Mới
        </button>
    <?php endif; ?>
</div>

<?php if ($productError): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        <?= htmlspecialchars($productError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="flex gap-6 items-start">
    <div class="w-1/3 shrink-0">
        <div class="flex justify-between items-end mb-3 px-1">
            <h3 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH THÀNH PHẨM (<?= count($products) ?>)</h3>
        </div>
        <!-- Ô tìm kiếm sản phẩm -->
        <div class="mb-3">
            <input type="text" id="productSearch" placeholder="Tìm kiếm theo mã hoặc tên..." class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-blue-500 shadow-sm">
        </div>
        <div class="max-h-[520px] space-y-3 overflow-y-auto pr-2 scrollbar-custom" id="productList">
            <?php if (!$products): ?>
                <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500">Chưa có thành phẩm.</div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <button type="button" class="product-item w-full bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer text-left hover:border-blue-300 transition" data-matp="<?= htmlspecialchars($product['maTP'], ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars($product['tenTP'], ENT_QUOTES, 'UTF-8') ?>" data-unit="<?= htmlspecialchars($product['donViTinh'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="flex justify-between items-start mb-1">
                            <span class="flex items-center gap-2">
                                <span class="product-code bg-slate-200 text-slate-600 text-xs font-bold px-2 py-0.5 rounded"><?= htmlspecialchars($product['maTP'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="font-bold text-slate-800 text-base"><?= htmlspecialchars($product['tenTP'], ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                            <span class="text-emerald-500 font-medium text-sm"><?= htmlspecialchars($product['soLuong'], ENT_QUOTES, 'UTF-8') ?> tồn kho</span>
                        </span>
                        <span class="flex justify-between items-center text-xs text-slate-500">
                            <span>Đơn vị tính: <?= htmlspecialchars($product['donViTinh'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= (int) $product['soLuongVatTu'] ?> loại vật tư</span>
                        </span>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <div id="bomEmptyState" class="py-16 text-center text-slate-400">
            <i class="fa-solid fa-hand-pointer text-3xl mb-3"></i>
            <p>Chọn một thành phẩm để xem chi tiết định mức.</p>
        </div>
        <div id="bomSimulationArea" class="hidden">
            <div class="border-b border-slate-100 pb-4 mb-4">
                <span id="selectedProductCode" class="inline-block rounded bg-blue-100 px-3 py-1 text-sm font-bold text-blue-700 shadow-sm"></span>
                <div class="mt-3 w-full rounded-lg border border-slate-200 bg-slate-50 p-4 shadow-sm">
                    <h3 id="selectedProductName" class="text-xl font-bold text-slate-800 uppercase tracking-wide"></h3>
                </div>
                <p class="mt-2 text-slate-500 text-xs">Công thức định mức cấu thành cho 1 sản phẩm.</p>
            </div>
            <div class="flex justify-between items-end mb-3">
                <h4 class="font-bold text-slate-700 text-xs uppercase">CHI TIẾT ĐỊNH MỨC VẬT TƯ</h4>
                <span class="text-[11px] text-slate-400">Dữ liệu gốc tính toán nhu cầu sản xuất</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-slate-200 mb-6">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3">Mã NVL</th>
                            <th class="px-4 py-3">Tên Nguyên Vật Liệu</th>
                            <th class="px-4 py-3">Đơn vị tính</th>
                            <th class="px-4 py-3">Định mức</th>
                            <th class="px-4 py-3">Tồn kho thực tế</th>
                            <th class="px-4 py-3">Khả năng đáp ứng</th>
                        </tr>
                    </thead>
                    <tbody id="bomTableBody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($isManager): ?>
    <div id="modal-add-product" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center">
        <div class="bg-white rounded-xl w-full max-w-[600px] shadow-2xl p-6 max-h-[90vh] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="addProductTitle">
            <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                <h3 id="addProductTitle" class="font-bold text-slate-800 text-lg">Khai Báo Thành Phẩm & Định Mức Mới</h3>
                <button class="close-modal text-slate-400 hover:text-slate-600" type="button" aria-label="Đóng"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form id="addProductForm" action="../ajax/add_bom_product.php" method="post" class="pt-5">
                <?= csrf_field() ?>
                <div>
                    <label for="productCode" class="mb-1 block text-sm font-medium text-slate-700">Mã thành phẩm (tự động)</label>
                    <input id="productCode" type="text" value="<?= htmlspecialchars($nextProductCode, ENT_QUOTES, 'UTF-8') ?>" readonly class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500">
                </div>
                <div class="mt-4 grid grid-cols-[minmax(0,2fr)_minmax(150px,1fr)] gap-4">
                    <div>
                        <label for="productName" class="mb-1 block text-sm font-medium text-slate-700">Tên thành phẩm</label>
                        <input id="productName" name="tenTP" type="text" maxlength="100" required placeholder="Ví dụ: Bàn máy tính gaming chữ K" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label for="productUnit" class="mb-1 block text-sm font-medium text-slate-700">Đơn vị tính</label>
                        <select id="productUnit" name="donViTinh" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500">
                            <option value="">-- Chọn đơn vị --</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?= htmlspecialchars($unit['tenDVT'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($unit['tenDVT'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-sm font-medium text-slate-700">Định mức vật tư</label>
                        <button id="addMaterialRow" type="button" class="text-sm font-medium text-blue-600 hover:text-blue-700">+ Thêm dòng vật tư</button>
                    </div>
                    <div id="materialRows" class="space-y-3">
                        <div class="material-row flex items-center gap-3">
                            <select name="materials[0][maNVL]" required class="material-select min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500">
                                <option value="">-- Chọn nguyên vật liệu --</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= htmlspecialchars($material['maNVL'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($material['tenNVL'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($material['maNVL'], ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input name="materials[0][soLuong]" type="number" min="0.0001" step="any" required value="1" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-center text-sm outline-none focus:border-blue-500">
                            <button type="button" class="remove-material-row hidden text-slate-400 hover:text-red-600" aria-label="Xóa dòng"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <p id="addProductMessage" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button class="close-modal rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" type="button">Hủy bỏ</button>
                    <button id="saveProductButton" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" type="submit">Lưu Thành Phẩm & Định Mức</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script src="../assets/js/bom.js"></script>
<?php require_once '../includes/footer.php'; ?>