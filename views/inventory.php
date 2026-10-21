<?php
require_once '../includes/header.php';
require_once '../config/db.php';
require_once '../includes/csrf.php';

$canManageMasterData = (int) ($_SESSION['role'] ?? 1) === 0;
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? 'active');
$statusFilter = $canManageMasterData ? $statusFilter : 'active';
$statusFilter = in_array($statusFilter, ['active', 'hidden', 'all'], true) ? $statusFilter : 'active';
$statusCondition = $statusFilter === 'active' ? ' = 1' : ($statusFilter === 'hidden' ? ' = 0' : ' IN (0, 1)');
$requestedView = $_GET['view'] ?? 'materials';
$view = $requestedView === 'materials' ? 'warehouse_materials' : $requestedView;
$view = in_array($view, ['warehouse_materials', 'workshop_materials', 'products', 'units'], true) ? $view : 'warehouse_materials';
$materialTable = $view === 'workshop_materials' ? 'NGUYENVATLIEUXUONG' : 'NGUYENVATLIEU';
$isMaterialView = in_array($view, ['warehouse_materials', 'workshop_materials'], true);
$materialTitle = $view === 'workshop_materials' ? 'Tồn kho nguyên vật liệu xưởng hiện tại' : 'Tồn kho nguyên vật liệu kho hiện tại';
$materials = [];
$products = [];
$units = [];
$inventoryError = null;

try {
    if ($view === 'products') {
        $sql = 'SELECT maTP, tenTP, donViTinh, soLuong, isDeleted, trangThai FROM THANHPHAM WHERE trangThai' . $statusCondition;
        if ($search !== '') {
            $sql .= ' AND tenTP LIKE :search';
        }
        $sql .= ' ORDER BY tenTP ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($search !== '' ? ['search' => "%{$search}%"] : []);
        $products = $stmt->fetchAll();
    } elseif ($view === 'units') {
        $sql = 'SELECT dvt.tenDVT, COUNT(nvl.maNVL) AS soLuongNVL
                FROM DONVITINH dvt
                LEFT JOIN NGUYENVATLIEU nvl ON nvl.maDVT = dvt.maDVT AND nvl.trangThai' . $statusCondition;
        if ($search !== '') {
            $sql .= ' WHERE dvt.tenDVT LIKE :search';
        }
        $sql .= ' GROUP BY dvt.maDVT, dvt.tenDVT ORDER BY dvt.tenDVT ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($search !== '' ? ['search' => "%{$search}%"] : []);
        $units = $stmt->fetchAll();
    } else {
        if ($view === 'workshop_materials') {
            $sql = 'SELECT master.maNVL, master.tenNVL, dvt.tenDVT,
                           COALESCE(workshop.soLuong, 0) AS soLuong,
                           master.isDeleted, master.trangThai
                    FROM NGUYENVATLIEU master
                    LEFT JOIN NGUYENVATLIEUXUONG workshop ON workshop.maNVL = master.maNVL
                    INNER JOIN DONVITINH dvt ON dvt.maDVT = master.maDVT';
            $sql .= ' WHERE master.trangThai' . $statusCondition;
        } else {
            $sql = 'SELECT nvl.maNVL, nvl.tenNVL, dvt.tenDVT, nvl.soLuong,
                           nvl.isDeleted, nvl.trangThai
                    FROM NGUYENVATLIEU nvl
                    INNER JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
                    WHERE nvl.trangThai' . $statusCondition;
        }

        if ($search !== '') {
            $sql .= $view === 'workshop_materials'
                ? ' AND master.tenNVL LIKE :search'
                : ' AND nvl.tenNVL LIKE :search';
        }

        $sql .= $view === 'workshop_materials' ? ' ORDER BY master.tenNVL ASC' : ' ORDER BY nvl.tenNVL ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($search !== '' ? ['search' => "%{$search}%"] : []);
        $materials = $stmt->fetchAll();
        $units = $pdo->query('SELECT maDVT, tenDVT FROM DONVITINH ORDER BY tenDVT ASC')->fetchAll();
    }
} catch (PDOException $e) {
    $inventoryError = 'Không thể tải dữ liệu tồn kho từ cơ sở dữ liệu.';
}
?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-amber-100 text-amber-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-cubes-stacked"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Tra Cứu Danh Mục Kho & Dữ Liệu Gốc</h2>
            </div>
        </div>
    </div>
    <form id="inventorySearch" method="get" class="relative w-64">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-sm"></i>
        <input type="hidden" name="view" value="<?= htmlspecialchars($view, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8') ?>">
        <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= $view === 'products' ? 'Tìm kiếm tên thành phẩm...' : ($view === 'units' ? 'Tìm kiếm đơn vị tính...' : 'Tìm kiếm tên vật liệu...') ?>" class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 shadow-sm" autocomplete="off">
    </form>
    <?php if ($canManageMasterData && $view !== 'units'): ?>
        <select id="masterStatusFilter" class="border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-600" aria-label="Lọc trạng thái">
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Đang hiển thị</option>
            <option value="hidden" <?= $statusFilter === 'hidden' ? 'selected' : '' ?>>Đang ẩn</option>
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
        </select>
    <?php endif; ?>
</div>

<!-- Tabs Điều Hướng -->
<div class="flex items-center gap-2 mb-6 border-b border-slate-200 pb-2">
    <a href="inventory.php?view=warehouse_materials&amp;status=<?= urlencode($statusFilter) ?>" class="<?= $isMaterialView ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
        <i class="fa-solid fa-cubes"></i> Nguyên vật liệu
    </a>
    <a href="inventory.php?view=products&amp;status=<?= urlencode($statusFilter) ?>" class="<?= $view === 'products' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
        <i class="fa-solid fa-box"></i> Thành Phẩm
    </a>
    <a href="inventory.php?view=units&amp;status=<?= urlencode($statusFilter) ?>" class="<?= $view === 'units' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
        <i class="fa-solid fa-tags"></i> Đơn Vị Tính
    </a>
</div>

<?php if ($isMaterialView): ?>
    <div class="flex items-center gap-2 mb-6 border-b border-slate-200 pb-2">
        <a href="inventory.php?view=warehouse_materials&amp;status=<?= urlencode($statusFilter) ?>" class="<?= $view === 'warehouse_materials' ? 'bg-blue-100 text-blue-700 border-blue-200' : 'text-slate-500 border-slate-200 hover:bg-slate-50 hover:text-slate-800' ?> border text-xs font-medium px-4 py-2 rounded-lg transition" aria-current="<?= $view === 'warehouse_materials' ? 'page' : 'false' ?>">
            <i class="fa-solid fa-warehouse mr-1"></i> Nguyên vật liệu kho
        </a>
        <a href="inventory.php?view=workshop_materials&amp;status=<?= urlencode($statusFilter) ?>" class="<?= $view === 'workshop_materials' ? 'bg-blue-100 text-blue-700 border-blue-200' : 'text-slate-500 border-slate-200 hover:bg-slate-50 hover:text-slate-800' ?> border text-xs font-medium px-4 py-2 rounded-lg transition" aria-current="<?= $view === 'workshop_materials' ? 'page' : 'false' ?>">
            <i class="fa-solid fa-industry mr-1"></i> Nguyên vật liệu xưởng
        </a>
        <?php if ($canManageMasterData): ?>
            <button id="openAddMaterialModal" type="button" class="ml-auto bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Thêm nguyên vật liệu
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Bảng Master Data -->
<div class="flex min-h-0 flex-1 flex-col bg-white border border-slate-200 rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-end mb-4">
        <h3 class="font-bold text-slate-800"><?= $view === 'products' ? 'Tồn kho thành phẩm hiện tại' : ($view === 'units' ? 'Danh sách đơn vị tính' : 'Tồn kho nguyên vật liệu hiện tại') ?></h3>
        <?php if ($view === 'units' && $canManageMasterData): ?>
            <button id="openAddUnitModal" type="button" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Thêm đơn vị tính
            </button>
        <?php endif; ?>
    </div>
    
    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="sticky top-0 z-10 bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                <?php if ($view === 'units'): ?>
                    <tr>
                        <th class="px-4 py-3 border-b border-slate-200">Tên Đơn Vị Tính</th>
                        <th class="px-4 py-3 border-b border-slate-200 w-48 text-right">Số Nguyên Vật Liệu Sử Dụng</th>
                        <th class="px-4 py-3 border-b border-slate-200 w-48 text-center">Trạng Thái</th>
                    </tr>
                <?php else: ?>
                    <tr>
                        <th class="px-4 py-3 border-b border-slate-200"><?= $view === 'products' ? 'Tên Thành Phẩm' : 'Tên Nguyên Vật Liệu' ?></th>
                        <th class="px-4 py-3 border-b border-slate-200 w-48 text-center">Đơn Vị Tính</th>
                        <th class="px-4 py-3 border-b border-slate-200 w-48 text-right">Số Lượng Tồn Kho</th>
                        <th class="px-4 py-3 border-b border-slate-200 w-40 text-center">Cảnh Báo Dự Trữ</th>
                        <?php if ($canManageMasterData): ?>
                            <th class="px-4 py-3 border-b border-slate-200 w-56 text-center">Thao tác</th>
                        <?php endif; ?>
                    </tr>
                <?php endif; ?>
            </thead>
            <tbody id="inventoryRows" class="divide-y divide-slate-100 text-xs">
                <tr id="inventoryFilterEmptyRow" class="hidden"><td colspan="<?= $view === 'units' ? '3' : ($canManageMasterData ? '5' : '4') ?>" class="px-4 py-8 text-center text-slate-500">Không tìm thấy kết quả phù hợp.</td></tr>
                <?php if ($inventoryError): ?>
                    <tr><td colspan="<?= $view === 'units' ? '3' : ($canManageMasterData ? '5' : '4') ?>" class="px-4 py-8 text-center text-red-600"><?= htmlspecialchars($inventoryError, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php elseif (($view === 'products' && !$products) || ($view === 'units' && !$units) || ($isMaterialView && !$materials)): ?>
                    <tr id="inventoryEmptyRow"><td colspan="<?= $view === 'units' ? '3' : ($canManageMasterData ? '5' : '4') ?>" class="px-4 py-8 text-center text-slate-500">Không tìm thấy <?= $view === 'products' ? 'thành phẩm' : ($view === 'units' ? 'đơn vị tính' : 'nguyên vật liệu') ?>.</td></tr>
                <?php elseif ($view === 'units'): ?>
                    <?php foreach ($units as $unit): ?>
                        <tr data-inventory-row class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($unit['tenDVT'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm"><?= number_format((int) $unit['soLuongNVL']) ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ((int) $unit['soLuongNVL'] > 0): ?>
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium">Đang sử dụng</span>
                                <?php else: ?>
                                    <span class="bg-slate-100 text-slate-500 border border-slate-200 text-[10px] px-2 py-1 rounded-full font-medium">Chưa sử dụng</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php elseif ($view === 'products'): ?>
                    <?php foreach ($products as $product): ?>
                        <?php $isLowStock = (int) $product['soLuong'] < 10; ?>
                        <tr data-inventory-row class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($product['tenTP'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-500 text-center"><?= htmlspecialchars($product['donViTinh'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm"><?= number_format((int) $product['soLuong']) ?> <?= htmlspecialchars($product['donViTinh'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($isLowStock): ?>
                                    <span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Cần sản xuất thêm</span>
                                <?php else: ?>
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium">Tồn kho an toàn</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManageMasterData): ?>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button" data-master-action="<?= (int) $product['trangThai'] === 1 ? 'hide' : 'show' ?>" data-master-entity="product" data-master-code="<?= htmlspecialchars($product['maTP'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-md px-2 py-1 text-[11px] <?= (int) $product['trangThai'] === 1 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?>">
                                            <i class="fa-solid <?= (int) $product['trangThai'] === 1 ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            <?= (int) $product['trangThai'] === 1 ? 'Ẩn' : 'Hiện' ?>
                                        </button>
                                        <?php if ((int) $product['isDeleted'] === 1): ?>
                                            <button type="button" data-master-action="delete" data-master-entity="product" data-master-code="<?= htmlspecialchars($product['maTP'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-md bg-rose-50 px-2 py-1 text-[11px] text-rose-700">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($materials as $material): ?>
                        <?php $isLowStock = (int) $material['soLuong'] < 100; ?>
                        <tr data-inventory-row class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($material['tenNVL'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-500 text-center"><?= htmlspecialchars($material['tenDVT'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm"><?= number_format((int) $material['soLuong']) ?> <?= htmlspecialchars($material['tenDVT'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($isLowStock): ?>
                                    <span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Cần nhập thêm</span>
                                <?php else: ?>
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium">Tồn kho an toàn</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManageMasterData): ?>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button" data-master-action="<?= (int) $material['trangThai'] === 1 ? 'hide' : 'show' ?>" data-master-entity="material" data-master-code="<?= htmlspecialchars($material['maNVL'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-md px-2 py-1 text-[11px] <?= (int) $material['trangThai'] === 1 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?>">
                                            <i class="fa-solid <?= (int) $material['trangThai'] === 1 ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            <?= (int) $material['trangThai'] === 1 ? 'Ẩn' : 'Hiện' ?>
                                        </button>
                                        <?php if ((int) $material['isDeleted'] === 1): ?>
                                            <button type="button" data-master-action="delete" data-master-entity="material" data-master-code="<?= htmlspecialchars($material['maNVL'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-md bg-rose-50 px-2 py-1 text-[11px] text-rose-700">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($isMaterialView && $canManageMasterData): ?>
    <div id="addMaterialModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 items-center justify-center px-4">
        <div class="bg-white rounded-xl w-full max-w-md shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="addMaterialTitle">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 id="addMaterialTitle" class="font-bold text-slate-800">Thêm nguyên vật liệu</h3>
                <button id="closeAddMaterialModal" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="addMaterialForm" action="../ajax/add_material.php" method="post" class="p-5">
                <?= csrf_field() ?>
                <label for="materialName" class="mb-2 block text-sm font-medium text-slate-700">Tên nguyên vật liệu</label>
                <input id="materialName" name="tenNVL" type="text" maxlength="50" required placeholder="Ví dụ: Gỗ MDF 18mm" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500">
                <label for="materialUnit" class="mb-2 mt-4 block text-sm font-medium text-slate-700">Đơn vị tính</label>
                <select id="materialUnit" name="maDVT" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500">
                    <option value="">-- Chọn đơn vị --</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= (int) $unit['maDVT'] ?>"><?= htmlspecialchars($unit['tenDVT'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <p id="addMaterialMessage" class="mt-3 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button id="cancelAddMaterial" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Hủy</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Lưu nguyên vật liệu</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const addMaterialModal = document.getElementById('addMaterialModal');
        const addMaterialForm = document.getElementById('addMaterialForm');
        const addMaterialMessage = document.getElementById('addMaterialMessage');
        const closeAddMaterial = () => {
            addMaterialModal.classList.add('hidden');
            addMaterialModal.classList.remove('flex');
        };

        document.getElementById('openAddMaterialModal').addEventListener('click', () => {
            addMaterialModal.classList.remove('hidden');
            addMaterialModal.classList.add('flex');
            document.getElementById('materialName').focus();
        });
        document.getElementById('closeAddMaterialModal').addEventListener('click', closeAddMaterial);
        document.getElementById('cancelAddMaterial').addEventListener('click', closeAddMaterial);

        addMaterialForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            addMaterialMessage.className = 'mt-3 hidden rounded-lg px-3 py-2 text-sm';
            try {
                const response = await fetch(addMaterialForm.action, {
                    method: 'POST',
                    body: new FormData(addMaterialForm),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    addMaterialMessage.textContent = result.message || 'Không thể thêm nguyên vật liệu.';
                    addMaterialMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
                    return;
                }
                window.location.href = 'inventory.php?view=warehouse_materials';
            } catch (error) {
                addMaterialMessage.textContent = 'Không thể kết nối máy chủ.';
                addMaterialMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
            }
        });
    </script>
<?php elseif ($view === 'units' && $canManageMasterData): ?>
    <div id="addUnitModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 items-center justify-center px-4">
        <div class="bg-white rounded-xl w-full max-w-md shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="addUnitTitle">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 id="addUnitTitle" class="font-bold text-slate-800">Thêm đơn vị tính</h3>
                <button id="closeAddUnitModal" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="addUnitForm" action="../ajax/add_unit.php" method="post" class="p-5">
                <label for="unitName" class="mb-2 block text-sm font-medium text-slate-700">Tên đơn vị tính</label>
                <input id="unitName" name="tenDVT" type="text" maxlength="10" required placeholder="Ví dụ: Cái" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <p id="addUnitMessage" class="mt-3 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button id="cancelAddUnit" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Hủy</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Lưu đơn vị</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const addUnitModal = document.getElementById('addUnitModal');
        const addUnitForm = document.getElementById('addUnitForm');
        const addUnitMessage = document.getElementById('addUnitMessage');
        const closeAddUnit = () => addUnitModal.classList.add('hidden');

        document.getElementById('openAddUnitModal').addEventListener('click', () => {
            addUnitModal.classList.remove('hidden');
            addUnitModal.classList.add('flex');
            document.getElementById('unitName').focus();
        });
        document.getElementById('closeAddUnitModal').addEventListener('click', closeAddUnit);
        document.getElementById('cancelAddUnit').addEventListener('click', closeAddUnit);

        addUnitForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            addUnitMessage.className = 'mt-3 hidden rounded-lg px-3 py-2 text-sm';

            try {
                const response = await fetch(addUnitForm.action, {
                    method: 'POST',
                    body: new FormData(addUnitForm),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    addUnitMessage.textContent = result.message || 'Không thể thêm đơn vị tính.';
                    addUnitMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
                    return;
                }

                window.location.href = 'inventory.php?view=units';
            } catch (error) {
                addUnitMessage.textContent = 'Không thể kết nối máy chủ.';
                addUnitMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
            }
        });
    </script>
<?php endif; ?>

<script>
    const inventorySearchInput = document.querySelector('#inventorySearch input[name="search"]');
    const masterStatusFilter = document.getElementById('masterStatusFilter');
    const inventoryRows = [...document.querySelectorAll('[data-inventory-row]')];
    const inventoryEmptyRow = document.getElementById('inventoryEmptyRow');
    const inventoryFilterEmptyRow = document.getElementById('inventoryFilterEmptyRow');

    inventorySearchInput?.addEventListener('input', () => {
        const searchValue = inventorySearchInput.value.trim().toLocaleLowerCase();
        let visibleRows = 0;

        inventoryRows.forEach((row) => {
            const matches = row.textContent.toLocaleLowerCase().includes(searchValue);
            row.classList.toggle('hidden', !matches);
            visibleRows += matches ? 1 : 0;
        });

        if (inventoryEmptyRow) {
            inventoryEmptyRow.classList.toggle('hidden', visibleRows > 0 || searchValue === '');
        }
        inventoryFilterEmptyRow?.classList.toggle('hidden', visibleRows > 0 || searchValue === '');
    });

    masterStatusFilter?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('status', masterStatusFilter.value);
        url.searchParams.delete('search');
        window.location.href = url.toString();
    });

    document.querySelectorAll('[data-master-action]').forEach((button) => {
        button.addEventListener('click', async () => {
            const action = button.dataset.masterAction;
            const entity = button.dataset.masterEntity;
            const code = button.dataset.masterCode;
            const isDelete = action === 'delete';
            const prompt = isDelete
                ? `Xóa vĩnh viễn ${code}? Thao tác này không thể hoàn tác.`
                : `${action === 'hide' ? 'Ẩn' : 'Hiển thị lại'} ${code}?`;

            if (!window.confirm(prompt)) {
                return;
            }

            button.disabled = true;
            try {
                const body = new FormData();
                body.append('csrf_token', <?= json_encode(csrf_token()) ?>);
                body.append('entity', entity);
                body.append('code', code);
                body.append('action', action);
                const response = await fetch('../ajax/manage_master_data.php', {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Không thể cập nhật dữ liệu.');
                }
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Không thể kết nối máy chủ.');
                button.disabled = false;
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>