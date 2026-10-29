<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['current_user'], $_SESSION['role']) || (int) $_SESSION['role'] !== 2) {
    header('Location: ../views/dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

$search = trim((string) ($_GET['search'] ?? ''));
$requestedRole = $_GET['role'] ?? 'all';
$roleFilter = in_array($requestedRole, ['all', 'admin', 'manager', 'staff'], true) ? $requestedRole : 'all';

$requestedStatus = $_GET['status'] ?? 'all';
$statusFilter = in_array($requestedStatus, ['all', 'active', 'locked'], true) ? $requestedStatus : 'all';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$employees = [];
$stats = ['total' => 0, 'admins' => 0, 'managers' => 0, 'staff' => 0, 'active' => 0, 'locked' => 0];
$employeesError = null;
$filteredTotal = 0;
$totalPages = 1;

try {
    $statsRow = $pdo->query(
        'SELECT COUNT(*) AS total,
                COALESCE(SUM(vaiTro = 0), 0) AS managers,
                COALESCE(SUM(vaiTro = 1), 0) AS staff,
                COALESCE(SUM(vaiTro = 2), 0) AS admins,
                COALESCE(SUM(trangThai = 1), 0) AS active,
                COALESCE(SUM(trangThai = 0), 0) AS locked
         FROM NHANVIEN'
    )->fetch() ?: [];
    $stats = [
        'total' => (int) ($statsRow['total'] ?? 0),
        'managers' => (int) ($statsRow['managers'] ?? 0),
        'staff' => (int) ($statsRow['staff'] ?? 0),
        'admins' => (int) ($statsRow['admins'] ?? 0),
        'active' => (int) ($statsRow['active'] ?? 0),
        'locked' => (int) ($statsRow['locked'] ?? 0),
    ];

    $conditions = [];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(maNV LIKE :searchMa OR hoTen LIKE :searchTen)';
        $params['searchMa'] = "%{$search}%";
        $params['searchTen'] = "%{$search}%";
    }

    if ($roleFilter === 'admin') {
        $conditions[] = 'vaiTro = 2';
    } elseif ($roleFilter === 'manager') {
        $conditions[] = 'vaiTro = 0';
    } elseif ($roleFilter === 'staff') {
        $conditions[] = 'vaiTro = 1';
    }

    if ($statusFilter === 'active') {
        $conditions[] = 'trangThai = 1';
    } elseif ($statusFilter === 'locked') {
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
    1 => ['label' => 'Hoạt động', 'icon' => 'fa-circle-check', 'class' => 'bg-emerald-50 text-emerald-600 border-emerald-200'],
    0 => ['label' => 'Đã khóa', 'icon' => 'fa-lock', 'class' => 'bg-rose-50 text-rose-600 border-rose-200'],
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
            <h2 class="text-xl font-bold text-slate-800">Quản Lý Nhân Viên</h2>
            <p class="text-xs text-slate-500">Thêm, chỉnh sửa và phân quyền tài khoản.</p>
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
        <?php if ($isAdmin): ?>
            <a href="<?= htmlspecialchars($roleUrl('admin'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $roleFilter === 'admin' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
                <i class="fa-solid fa-user-shield"></i> Admin (<?= number_format($stats['admins']) ?>)
            </a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($roleUrl('manager'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $roleFilter === 'manager' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
            <i class="fa-solid fa-crown"></i> Quản lý kho (<?= number_format($stats['managers']) ?>)
        </a>
        <a href="<?= htmlspecialchars($roleUrl('staff'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $roleFilter === 'staff' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?> text-[13px] font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition">
            <i class="fa-solid fa-user"></i> Nhân viên kho (<?= number_format($stats['staff']) ?>)
        </a>
    </div>
    <form id="employeeSearch" method="get" class="flex items-center gap-2">
        <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter, ENT_QUOTES, 'UTF-8') ?>">
        <select name="status" aria-label="Lọc theo trạng thái" class="text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500 shadow-sm bg-white">
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="locked" <?= $statusFilter === 'locked' ? 'selected' : '' ?>>Đã khóa</option>
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
    </div>

    <div class="max-h-[300px] overflow-auto rounded-lg border border-slate-200" aria-label="Danh sách nhân viên, tối đa 5 dòng hiển thị">
        <table class="w-full text-left text-sm">
            <thead class="sticky top-0 z-10 bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
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
                            $empStatus = (int) ($employee['trangThai'] ?? 1);
                            $isSelf = strcasecmp($employee['maNV'], $currentUser) === 0;
                            // Quản lý kho không được chỉnh sửa thông tin của quản lý kho khác.
                            $isProtectedAccount = !$isAdmin && !$isSelf && ($empRole === 0 || $empRole === 2);
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
                                <?php if ($empRole === 2): ?>
                                    <span class="bg-violet-50 text-violet-600 border border-violet-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-user-shield mr-1"></i> Quản trị hệ thống</span>
                                <?php elseif ($empRole === 0): ?>
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-crown mr-1"></i> Quản lý kho</span>
                                <?php else: ?>
                                    <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-user mr-1"></i> Nhân viên kho</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php $empStatusMeta = $statusMeta[$empStatus] ?? $statusMeta[1]; ?>
                                <span class="<?= $empStatusMeta['class'] ?> border text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid <?= $empStatusMeta['icon'] ?> mr-1"></i> <?= htmlspecialchars($empStatusMeta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) ($employee['sdt'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '<span class="text-slate-400">Chưa cập nhật</span>' ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars((string) ($employee['diaChi'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '<span class="text-slate-400">Chưa cập nhật</span>' ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" class="edit-employee p-1.5 text-blue-600 hover:bg-blue-50 rounded transition disabled:opacity-40 disabled:cursor-not-allowed" title="<?= $isProtectedAccount ? 'Không thể chỉnh sửa tài khoản này' : 'Chỉnh sửa' ?>" <?= $isProtectedAccount ? 'disabled' : '' ?>
                                        data-manv="<?= htmlspecialchars($employee['maNV'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-hotenn="<?= htmlspecialchars($employee['hoTen'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-vaitro="<?= $empRole ?>"
                                        data-trangthai="<?= $empStatus ?>"
                                        data-sdt="<?= htmlspecialchars((string) ($employee['sdt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-diachi="<?= htmlspecialchars((string) ($employee['diaChi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-self="<?= $isSelf ? '1' : '0' ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php $canChangeStatus = !$isSelf && !($isProtectedAccount); ?>
                                    <button type="button" class="lock-employee p-1.5 <?= $empStatus === 1 ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' ?> rounded transition disabled:opacity-40 disabled:cursor-not-allowed" title="<?= $canChangeStatus ? ($empStatus === 1 ? 'Khóa nhân viên' : 'Mở khóa nhân viên') : 'Không thể thay đổi trạng thái tài khoản này' ?>" <?= $canChangeStatus ? '' : 'disabled' ?>
                                        data-manv="<?= htmlspecialchars($employee['maNV'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-hotenn="<?= htmlspecialchars($employee['hoTen'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-trangthai="<?= $empStatus ?>">
                                        <i class="fa-solid <?= $empStatus === 1 ? 'fa-lock' : 'fa-lock-open' ?>"></i>
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
                <?php if ($isAdmin): ?>
                    <div>
                        <label for="addVaiTro" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò <span class="text-rose-500">*</span></label>
                        <select id="addVaiTro" name="vaiTro" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="1">Nhân viên kho</option>
                            <option value="0">Quản lý kho</option>
                            <option value="2">Quản trị hệ thống</option>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="vaiTro" value="1">
                <?php endif; ?>
                <p class="col-span-2 text-xs text-slate-500">Mật khẩu mặc định: <strong>123456</strong>.</p>
                <div class="col-span-2">
                    <label for="addHoTen" class="mb-1.5 block text-sm font-medium text-slate-700">Họ tên <span class="text-rose-500">*</span></label>
                    <input id="addHoTen" name="hoTen" type="text" maxlength="50" required placeholder="Ví dụ: Nguyễn Văn An" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="addSdt" class="mb-1.5 block text-sm font-medium text-slate-700">Số điện thoại <span class="text-rose-500">*</span></label>
                    <input id="addSdt" name="sdt" type="tel" maxlength="12" required placeholder="0901000001" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
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
                    <select id="editVaiTro" required disabled class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none disabled:bg-slate-100 disabled:text-slate-500">
                        <option value="1">Nhân viên kho</option>
                        <option value="0">Quản lý kho</option>
                        <?php if ($isAdmin): ?><option value="2">Quản trị hệ thống</option><?php endif; ?>
                    </select>
                    <input id="editVaiTroValue" type="hidden" name="vaiTro" value="1">
                    <p id="editVaiTroNote" class="mt-1 text-[11px] text-amber-600">Vai trò không thể thay đổi sau khi tạo tài khoản.</p>
                </div>
                <div>
                    <label for="editTrangThai" class="mb-1.5 block text-sm font-medium text-slate-700">Trạng thái <span class="text-rose-500">*</span></label>
                    <select id="editTrangThai" name="trangThai" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <option value="1">Hoạt động</option>
                        <option value="0" id="editTrangThaiLockedOption">Đã khóa</option>
                    </select>
                    <p id="editTrangThaiNote" class="mt-1 hidden text-[11px] text-amber-600">Bạn không thể tự khóa tài khoản của chính mình.</p>
                </div>
                <div class="col-span-2">
                    <label for="editHoTen" class="mb-1.5 block text-sm font-medium text-slate-700">Họ tên <span class="text-rose-500">*</span></label>
                    <input id="editHoTen" name="hoTen" type="text" maxlength="50" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="editSdt" class="mb-1.5 block text-sm font-medium text-slate-700">Số điện thoại <span class="text-rose-500">*</span></label>
                    <input id="editSdt" name="sdt" type="tel" maxlength="12" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="editMatKhau" class="mb-1.5 block text-sm font-medium text-slate-700">Mật khẩu mới</label>
                    <input id="editMatKhau" name="matKhau" type="password" minlength="6" placeholder="Bỏ trống nếu giữ nguyên" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <div class="col-span-2">
                    <label for="editDiaChi" class="mb-1.5 block text-sm font-medium text-slate-700">Địa chỉ <span class="text-rose-500">*</span></label>
                    <input id="editDiaChi" name="diaChi" type="text" maxlength="50" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
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

<!-- Modal thay đổi trạng thái tài khoản -->
<div id="lockEmployeeModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 items-center justify-center px-4">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="lockEmployeeTitle">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 id="lockEmployeeTitle" class="font-bold text-slate-800"><i class="fa-solid fa-lock text-rose-500 mr-2"></i>Khóa nhân viên</h3>
            <button id="closeLockEmployeeModal" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="lockEmployeeForm" action="../ajax/lock_employee.php" method="post" class="p-5">
            <?= csrf_field() ?>
            <input id="lockMaNV" name="maNV" type="hidden">
            <p id="lockEmployeePrompt" class="text-sm text-slate-600">
                <span id="lockEmployeeActionText"></span>
                <strong id="lockEmployeeName" class="text-slate-800"></strong>?
            </p>
            <p id="lockEmployeeMessage" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="alert"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button id="cancelLockEmployee" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Hủy</button>
                <button id="lockEmployeeSubmit" type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Khóa nhân viên</button>
            </div>
        </form>
    </div>
</div>
<div id="employeesPageConfig" data-current-query="<?= htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8') ?>" data-current-role="<?= htmlspecialchars((string) ($_SESSION['role'] ?? 1), ENT_QUOTES, 'UTF-8') ?>" hidden></div>
<script src="../assets/js/employees.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>