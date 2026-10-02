<?php
require_once '../includes/header.php';
require_once '../config.php';
require_once '../includes/csrf.php';

// Chỉ quản lý kho (vaiTro = 0) được truy cập trang quản lý nhân viên.
if (!$isManager) {
    header('Location: dashboard.php');
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$requestedRole = $_GET['role'] ?? 'all';
$roleFilter = in_array($requestedRole, ['all', 'manager', 'staff'], true) ? $requestedRole : 'all';

$requestedStatus = $_GET['status'] ?? 'all';
$statusFilter = in_array($requestedStatus, ['all', 'active', 'leave', 'resigned'], true) ? $requestedStatus : 'all';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$employees = [];
$stats = ['total' => 0, 'managers' => 0, 'staff' => 0, 'active' => 0, 'onLeave' => 0, 'resigned' => 0];
$employeesError = null;
$filteredTotal = 0;
$totalPages = 1;

try {
    $statsRow = $pdo->query(
        'SELECT COUNT(*) AS total,
                COALESCE(SUM(vaiTro = 0), 0) AS managers,
                COALESCE(SUM(vaiTro = 1), 0) AS staff,
                COALESCE(SUM(trangThai = 2), 0) AS active,
                COALESCE(SUM(trangThai = 1), 0) AS onLeave,
                COALESCE(SUM(trangThai = 0), 0) AS resigned
         FROM NHANVIEN'
    )->fetch() ?: [];
    $stats = [
        'total' => (int) ($statsRow['total'] ?? 0),
        'managers' => (int) ($statsRow['managers'] ?? 0),
        'staff' => (int) ($statsRow['staff'] ?? 0),
        'active' => (int) ($statsRow['active'] ?? 0),
        'onLeave' => (int) ($statsRow['onLeave'] ?? 0),
        'resigned' => (int) ($statsRow['resigned'] ?? 0),
    ];

    $conditions = [];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(maNV LIKE :searchMa OR hoTen LIKE :searchTen)';
        $params['searchMa'] = "%{$search}%";
        $params['searchTen'] = "%{$search}%";
    }

    if ($roleFilter === 'manager') {
        $conditions[] = 'vaiTro = 0';
    } elseif ($roleFilter === 'staff') {
        $conditions[] = 'vaiTro = 1';
    }

    if ($statusFilter === 'active') {
        $conditions[] = 'trangThai = 2';
    } elseif ($statusFilter === 'leave') {
        $conditions[] = 'trangThai = 1';
    } elseif ($statusFilter === 'resigned') {
        $conditions[] = 'trangThai = 0';
    }

    $whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

    // Đếm tổng số bản ghi khớp bộ lọc để tính số trang.
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM NHANVIEN' . $whereSql);
    $countStmt->execute($params);
    $filteredTotal = (int) $countStmt->fetchColumn();

    $totalPages = $filteredTotal > 0 ? (int) ceil($filteredTotal / $perPage) : 1;
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $sql = 'SELECT maNV, hoTen, vaiTro, trangThai, sdt, diaChi FROM NHANVIEN' . $whereSql
        . ' ORDER BY vaiTro ASC, maNV ASC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $employeesError = 'Không thể tải danh sách nhân viên từ cơ sở dữ liệu.';
}

$currentUser = (string) ($_SESSION['current_user'] ?? '');

// Nhãn và màu hiển thị tương ứng với từng trạng thái nhân viên.
$statusMeta = [
    2 => ['label' => 'Hoạt động', 'icon' => 'fa-circle-check', 'class' => 'bg-emerald-50 text-emerald-600 border-emerald-200'],
    1 => ['label' => 'Xin nghỉ phép', 'icon' => 'fa-clock', 'class' => 'bg-amber-50 text-amber-600 border-amber-200'],
    0 => ['label' => 'Đã nghỉ việc', 'icon' => 'fa-circle-xmark', 'class' => 'bg-rose-50 text-rose-600 border-rose-200'],
];
$searchPlaceholder = 'Tìm theo mã hoặc tên nhân viên...';

// Giữ nguyên bộ lọc khi chuyển trang hoặc chuyển tab vai trò.
$filterParams = ['role' => $roleFilter];
if ($search !== '') {
    $filterParams['search'] = $search;
}
if ($statusFilter !== 'all') {
    $filterParams['status'] = $statusFilter;
}
$roleUrl = static function (string $role) use ($search, $statusFilter): string {
    $query = ['role' => $role];
    if ($search !== '') {
        $query['search'] = $search;
    }
    if ($statusFilter !== 'all') {
        $query['status'] = $statusFilter;
    }
    return 'employees.php?' . http_build_query($query);
};
$pageUrl = static function (int $targetPage) use ($filterParams): string {
    return 'employees.php?' . http_build_query($filterParams + ['page' => $targetPage]);
};

// Chuỗi truy vấn hiện tại dùng để tải lại trang sau khi thêm/sửa/xóa.
$currentQuery = (string) ($_SERVER['QUERY_STRING'] ?? '');

// Dải số trang hiển thị quanh trang hiện tại.
$startPage = max(1, $page - 2);
$endPage = min($totalPages, $page + 2);
?>
<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3">
        <div class="bg-sky-100 text-sky-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-users-gear"></i></div>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Quản Lý Nhân Viên (NHANVIEN)</h2>
            <p class="text-xs text-slate-500">Thêm, chỉnh sửa và phân quyền tài khoản kho. Vai trò: <span class="font-medium text-slate-700">Quản lý kho (0)</span> &bull; <span class="font-medium text-slate-700">Nhân viên kho (1)</span>.</p>
        </div>
    </div>
    <button id="openAddEmployeeModal" type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2">
        <i class="fa-solid fa-user-plus"></i> Thêm nhân viên
    </button>
</div>

<!-- Thống kê nhanh -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">TỔNG NHÂN SỰ</div>
            <i class="fa-solid fa-users text-blue-500"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= number_format($stats['total']) ?> <span class="text-sm font-normal text-slate-500">tài khoản</span></div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">QUẢN LÝ KHO</div>
            <i class="fa-solid fa-crown text-emerald-500"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= number_format($stats['managers']) ?> <span class="text-sm font-normal text-slate-500">người</span></div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">NHÂN VIÊN KHO</div>
            <i class="fa-solid fa-user text-slate-400"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= number_format($stats['staff']) ?> <span class="text-sm font-normal text-slate-500">người</span></div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">ĐANG HOẠT ĐỘNG</div>
            <i class="fa-solid fa-circle-check text-emerald-500"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= number_format($stats['active']) ?> <span class="text-sm font-normal text-slate-500">người</span></div>
    </div>
</div>

<!-- Tabs lọc vai trò + Tìm kiếm -->
<div class="flex justify-between items-center gap-3 mb-6 border-b border-slate-200 pb-2">
    <div class="flex items-center gap-2">
        <a href="<?= htmlspecialchars($roleUrl('all'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $roleFilter === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
            <i class="fa-solid fa-list"></i> Tất cả (<?= number_format($stats['total']) ?>)
        </a>
        <a href="<?= htmlspecialchars($roleUrl('manager'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $roleFilter === 'manager' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
            <i class="fa-solid fa-crown"></i> Quản lý kho (<?= number_format($stats['managers']) ?>)
        </a>
        <a href="<?= htmlspecialchars($roleUrl('staff'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $roleFilter === 'staff' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
            <i class="fa-solid fa-user"></i> Nhân viên kho (<?= number_format($stats['staff']) ?>)
        </a>
    </div>
    <form id="employeeSearch" method="get" class="flex items-center gap-2">
        <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter, ENT_QUOTES, 'UTF-8') ?>">
        <select name="status" onchange="this.form.submit()" aria-label="Lọc theo trạng thái" class="text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500 shadow-sm bg-white">
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="leave" <?= $statusFilter === 'leave' ? 'selected' : '' ?>>Xin nghỉ phép</option>
            <option value="resigned" <?= $statusFilter === 'resigned' ? 'selected' : '' ?>>Đã nghỉ việc</option>
        </select>
        <div class="relative w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-sm"></i>
            <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>" class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 shadow-sm">
        </div>
    </form>
</div>
<!-- Bảng danh sách nhân viên -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-end mb-4">
        <h3 class="font-bold text-slate-800">Danh sách tài khoản kho</h3>
        <span class="text-xs text-blue-500 font-medium">BẢNG NHANVIEN</span>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                <tr>
                    <th class="px-4 py-3 border-b border-slate-200 w-32">Mã NV</th>
                    <th class="px-4 py-3 border-b border-slate-200">Họ Tên</th>
                    <th class="px-4 py-3 border-b border-slate-200 w-40 text-center">Vai Trò</th>
                    <th class="px-4 py-3 border-b border-slate-200 w-40 text-center">Trạng thái</th>
                    <th class="px-4 py-3 border-b border-slate-200 w-40">Số Điện Thoại</th>
                    <th class="px-4 py-3 border-b border-slate-200">Địa Chỉ</th>
                    <th class="px-4 py-3 border-b border-slate-200 w-32 text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php if ($employeesError): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-red-600"><?= htmlspecialchars($employeesError, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php elseif (!$employees): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Không tìm thấy nhân viên phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                        <?php
                            $empRole = (int) $employee['vaiTro'];
                            $empStatus = (int) ($employee['trangThai'] ?? 2);
                            $isSelf = strcasecmp($employee['maNV'], $currentUser) === 0;
                            // Quản lý kho không được chỉnh sửa thông tin của quản lý kho khác.
                            $isOtherManager = $empRole === 0 && !$isSelf;
                        ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-bold text-slate-800">
                                <?= htmlspecialchars($employee['maNV'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($isSelf): ?>
                                    <span class="ml-1 bg-blue-50 text-blue-600 border border-blue-200 text-[10px] px-1.5 py-0.5 rounded">Bạn</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($employee['hoTen'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($empRole === 0): ?>
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-crown mr-1"></i> Quản lý kho</span>
                                <?php else: ?>
                                    <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-user mr-1"></i> Nhân viên kho</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php $empStatusMeta = $statusMeta[$empStatus] ?? $statusMeta[2]; ?>
                                <span class="<?= $empStatusMeta['class'] ?> border text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid <?= $empStatusMeta['icon'] ?> mr-1"></i> <?= htmlspecialchars($empStatusMeta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($employee['sdt'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($employee['diaChi'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" class="edit-employee p-1.5 text-blue-600 hover:bg-blue-50 rounded transition disabled:opacity-40 disabled:cursor-not-allowed" title="<?= $isOtherManager ? 'Không thể chỉnh sửa quản lý kho khác' : 'Chỉnh sửa' ?>" <?= $isOtherManager ? 'disabled' : '' ?>
                                        data-manv="<?= htmlspecialchars($employee['maNV'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-hotenn="<?= htmlspecialchars($employee['hoTen'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-vaitro="<?= $empRole ?>"
                                        data-trangthai="<?= $empStatus ?>"
                                        data-sdt="<?= htmlspecialchars($employee['sdt'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-diachi="<?= htmlspecialchars($employee['diaChi'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-self="<?= $isSelf ? '1' : '0' ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="delete-employee p-1.5 text-rose-600 hover:bg-rose-50 rounded transition disabled:opacity-40 disabled:cursor-not-allowed" title="Xóa" <?= $isSelf ? 'disabled' : '' ?>
                                        data-manv="<?= htmlspecialchars($employee['maNV'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-hotenn="<?= htmlspecialchars($employee['hoTen'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($employeesError === null && $filteredTotal > 0): ?>
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3 mt-4">
            <p class="text-xs text-slate-500">
                Hiển thị
                <span class="font-semibold text-slate-700"><?= number_format(($page - 1) * $perPage + 1) ?>–<?= number_format(min($page * $perPage, $filteredTotal)) ?></span>
                trên <span class="font-semibold text-slate-700"><?= number_format($filteredTotal) ?></span> nhân viên
            </p>
            <?php if ($totalPages > 1): ?>
                <nav class="flex items-center gap-1" aria-label="Phân trang danh sách nhân viên">
                    <a href="<?= htmlspecialchars($pageUrl(max(1, $page - 1)), ENT_QUOTES, 'UTF-8') ?>"
                       class="px-3 py-1.5 text-xs rounded-lg border <?= $page <= 1 ? 'border-slate-200 text-slate-300 pointer-events-none' : 'border-slate-300 text-slate-600 hover:bg-slate-50' ?> transition"
                       aria-label="Trang trước">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>

                    <?php if ($startPage > 1): ?>
                        <a href="<?= htmlspecialchars($pageUrl(1), ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition font-medium">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-2 text-xs text-slate-400">…</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <a href="<?= htmlspecialchars($pageUrl($p), ENT_QUOTES, 'UTF-8') ?>"
                           class="px-3 py-1.5 text-xs rounded-lg border font-medium transition <?= $p === $page ? 'bg-blue-600 border-blue-600 text-white shadow-sm' : 'border-slate-300 text-slate-600 hover:bg-slate-50' ?>"
                           <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="px-2 text-xs text-slate-400">…</span>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($pageUrl($totalPages), ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition font-medium"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <a href="<?= htmlspecialchars($pageUrl(min($totalPages, $page + 1)), ENT_QUOTES, 'UTF-8') ?>"
                       class="px-3 py-1.5 text-xs rounded-lg border <?= $page >= $totalPages ? 'border-slate-200 text-slate-300 pointer-events-none' : 'border-slate-300 text-slate-600 hover:bg-slate-50' ?> transition"
                       aria-label="Trang sau">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<!-- Modal Thêm nhân viên -->
<div id="addEmployeeModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 items-center justify-center px-4">
    <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="addEmployeeTitle">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 id="addEmployeeTitle" class="font-bold text-slate-800"><i class="fa-solid fa-user-plus text-blue-600 mr-2"></i>Thêm nhân viên</h3>
            <button id="closeAddEmployeeModal" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addEmployeeForm" action="../ajax/add_employee.php" method="post" class="p-5">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="addMaNV" class="mb-1.5 block text-sm font-medium text-slate-700">Mã nhân viên <span class="text-rose-500">*</span></label>
                    <input id="addMaNV" name="maNV" type="text" maxlength="10" required placeholder="Ví dụ: NV003" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="addVaiTro" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò <span class="text-rose-500">*</span></label>
                    <select id="addVaiTro" name="vaiTro" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <option value="1">Nhân viên kho</option>
                        <option value="0">Quản lý kho</option>
                    </select>
                </div>
                <div>
                    <label for="addTrangThai" class="mb-1.5 block text-sm font-medium text-slate-700">Trạng thái <span class="text-rose-500">*</span></label>
                    <select id="addTrangThai" name="trangThai" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <option value="2">Hoạt động</option>
                        <option value="1">Xin nghỉ phép</option>
                        <option value="0">Đã nghỉ việc</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label for="addHoTen" class="mb-1.5 block text-sm font-medium text-slate-700">Họ tên <span class="text-rose-500">*</span></label>
                    <input id="addHoTen" name="hoTen" type="text" maxlength="50" required placeholder="Ví dụ: Nguyễn Văn An" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="addSdt" class="mb-1.5 block text-sm font-medium text-slate-700">Số điện thoại <span class="text-rose-500">*</span></label>
                    <input id="addSdt" name="sdt" type="tel" maxlength="12" required placeholder="0901000001" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="addMatKhau" class="mb-1.5 block text-sm font-medium text-slate-700">Mật khẩu <span class="text-rose-500">*</span></label>
                    <input id="addMatKhau" name="matKhau" type="password" minlength="6" required placeholder="Tối thiểu 6 ký tự" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div class="col-span-2">
                    <label for="addDiaChi" class="mb-1.5 block text-sm font-medium text-slate-700">Địa chỉ <span class="text-rose-500">*</span></label>
                    <input id="addDiaChi" name="diaChi" type="text" maxlength="50" required placeholder="Ví dụ: Quận 1, TP.HCM" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
            </div>
            <p id="addEmployeeMessage" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button id="cancelAddEmployee" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Lưu nhân viên</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Sửa nhân viên -->
<div id="editEmployeeModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 items-center justify-center px-4">
    <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="editEmployeeTitle">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 id="editEmployeeTitle" class="font-bold text-slate-800"><i class="fa-solid fa-pen-to-square text-blue-600 mr-2"></i>Chỉnh sửa nhân viên</h3>
            <button id="closeEditEmployeeModal" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editEmployeeForm" action="../ajax/update_employee.php" method="post" class="p-5">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="editMaNV" class="mb-1.5 block text-sm font-medium text-slate-700">Mã nhân viên</label>
                    <input id="editMaNV" name="maNV" type="text" readonly class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500 outline-none">
                </div>
                <div>
                    <label for="editVaiTro" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò <span class="text-rose-500">*</span></label>
                    <select id="editVaiTro" name="vaiTro" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <option value="1">Nhân viên kho</option>
                        <option value="0">Quản lý kho</option>
                    </select>
                    <p id="editVaiTroNote" class="mt-1 hidden text-[11px] text-amber-600">Bạn không thể tự hạ cấp vai trò của chính mình.</p>
                </div>
                <div>
                    <label for="editTrangThai" class="mb-1.5 block text-sm font-medium text-slate-700">Trạng thái <span class="text-rose-500">*</span></label>
                    <select id="editTrangThai" name="trangThai" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <option value="2">Hoạt động</option>
                        <option value="1">Xin nghỉ phép</option>
                        <option value="0">Đã nghỉ việc</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label for="editHoTen" class="mb-1.5 block text-sm font-medium text-slate-700">Họ tên <span class="text-rose-500">*</span></label>
                    <input id="editHoTen" name="hoTen" type="text" maxlength="50" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="editSdt" class="mb-1.5 block text-sm font-medium text-slate-700">Số điện thoại <span class="text-rose-500">*</span></label>
                    <input id="editSdt" name="sdt" type="tel" maxlength="12" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="editMatKhau" class="mb-1.5 block text-sm font-medium text-slate-700">Mật khẩu mới</label>
                    <input id="editMatKhau" name="matKhau" type="password" minlength="6" placeholder="Bỏ trống nếu giữ nguyên" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div class="col-span-2">
                    <label for="editDiaChi" class="mb-1.5 block text-sm font-medium text-slate-700">Địa chỉ <span class="text-rose-500">*</span></label>
                    <input id="editDiaChi" name="diaChi" type="text" maxlength="50" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
            </div>
            <p id="editEmployeeMessage" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button id="cancelEditEmployee" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Xác nhận xóa -->
<div id="deleteEmployeeModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 items-center justify-center px-4">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="deleteEmployeeTitle">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 id="deleteEmployeeTitle" class="font-bold text-slate-800"><i class="fa-solid fa-triangle-exclamation text-rose-500 mr-2"></i>Xóa nhân viên</h3>
            <button id="closeDeleteEmployeeModal" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="deleteEmployeeForm" action="../ajax/delete_employee.php" method="post" class="p-5">
            <?= csrf_field() ?>
            <input id="deleteMaNV" name="maNV" type="hidden">
            <p class="text-sm text-slate-600">Bạn có chắc chắn muốn xóa nhân viên <strong id="deleteEmployeeName" class="text-slate-800"></strong>? Hành động này không thể hoàn tác.</p>
            <p id="deleteEmployeeMessage" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button id="cancelDeleteEmployee" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Xóa nhân viên</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        const addMessage = document.getElementById('addEmployeeMessage');
        const editMessage = document.getElementById('editEmployeeMessage');
        const deleteMessage = document.getElementById('deleteEmployeeMessage');

        // Tải lại trang nhưng giữ nguyên bộ lọc và trang hiện tại.
        const currentQuery = <?= json_encode($currentQuery, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const reloadUrl = 'employees.php' + (currentQuery ? '?' + currentQuery : '');

        const openModal = (modal) => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
        const closeModal = (modal) => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };
        const setMessage = (el, text, ok) => {
            el.textContent = text;
            el.className = ok
                ? 'mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-600'
                : 'mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
        };
        const clearMessage = (el) => {
            el.className = 'mt-4 hidden rounded-lg px-3 py-2 text-sm';
            el.textContent = '';
        };
        const submitForm = async (form, messageEl) => {
            clearMessage(messageEl);
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    setMessage(messageEl, result.message || 'Thao tác không thành công.', false);
                    return false;
                }
                return true;
            } catch (error) {
                setMessage(messageEl, 'Không thể kết nối máy chủ.', false);
                return false;
            }
        };

        // ---- Thêm nhân viên ----
        const addModal = document.getElementById('addEmployeeModal');
        const addForm = document.getElementById('addEmployeeForm');

        document.getElementById('openAddEmployeeModal').addEventListener('click', () => {
            addForm.reset();
            clearMessage(addMessage);
            openModal(addModal);
            document.getElementById('addMaNV').focus();
        });
        document.getElementById('closeAddEmployeeModal').addEventListener('click', () => closeModal(addModal));
        document.getElementById('cancelAddEmployee').addEventListener('click', () => closeModal(addModal));

        addForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (await submitForm(addForm, addMessage)) {
                window.location.href = reloadUrl;
            }
        });

        // ---- Sửa nhân viên ----
        const editModal = document.getElementById('editEmployeeModal');
        const editForm = document.getElementById('editEmployeeForm');

        const editVaiTro = document.getElementById('editVaiTro');
        const editVaiTroStaffOption = editVaiTro.querySelector('option[value="1"]');
        const editVaiTroNote = document.getElementById('editVaiTroNote');

        document.querySelectorAll('.edit-employee').forEach((button) => {
            button.addEventListener('click', () => {
                const isSelf = button.dataset.self === '1';
                document.getElementById('editMaNV').value = button.dataset.manv || '';
                document.getElementById('editHoTen').value = button.dataset.hotenn || '';
                document.getElementById('editTrangThai').value = button.dataset.trangthai || '2';
                document.getElementById('editSdt').value = button.dataset.sdt || '';
                document.getElementById('editDiaChi').value = button.dataset.diachi || '';
                document.getElementById('editMatKhau').value = '';

                // Không cho phép tự hạ cấp vai trò: khóa lựa chọn "Nhân viên kho" khi sửa chính mình.
                if (isSelf) {
                    editVaiTro.value = '0';
                    editVaiTroStaffOption.disabled = true;
                    editVaiTroNote.classList.remove('hidden');
                } else {
                    editVaiTro.value = button.dataset.vaitro || '1';
                    editVaiTroStaffOption.disabled = false;
                    editVaiTroNote.classList.add('hidden');
                }

                clearMessage(editMessage);
                openModal(editModal);
                document.getElementById('editHoTen').focus();
            });
        });
        document.getElementById('closeEditEmployeeModal').addEventListener('click', () => closeModal(editModal));
        document.getElementById('cancelEditEmployee').addEventListener('click', () => closeModal(editModal));

        editForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (await submitForm(editForm, editMessage)) {
                window.location.href = reloadUrl;
            }
        });

        // ---- Xóa nhân viên ----
        const deleteModal = document.getElementById('deleteEmployeeModal');
        const deleteForm = document.getElementById('deleteEmployeeForm');

        document.querySelectorAll('.delete-employee').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('deleteMaNV').value = button.dataset.manv || '';
                document.getElementById('deleteEmployeeName').textContent =
                    (button.dataset.hotenn || '') + ' (' + (button.dataset.manv || '') + ')';
                clearMessage(deleteMessage);
                openModal(deleteModal);
            });
        });
        document.getElementById('closeDeleteEmployeeModal').addEventListener('click', () => closeModal(deleteModal));
        document.getElementById('cancelDeleteEmployee').addEventListener('click', () => closeModal(deleteModal));

        deleteForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (await submitForm(deleteForm, deleteMessage)) {
                window.location.href = reloadUrl;
            }
        });
    })();
</script>
<?php require_once '../includes/footer.php'; ?>