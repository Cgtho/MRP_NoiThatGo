<?php
require_once '../includes/header.php';
require_once '../config.php';

$reportError = null;
$allowedModes = ['month', 'quarter', 'year'];
$mode = $_GET['mode'] ?? 'month';
if (!in_array($mode, $allowedModes, true)) { $mode = 'month'; }

// Các kỳ có dữ liệu (từ ngày phiếu nhập / xuất).
$availablePeriods = ['months' => [], 'quarters' => [], 'years' => []];
try {
    $dateRows = $pdo->query(
        'SELECT ngayNhap AS ngay FROM PHIEUNHAPKHO
         UNION ALL SELECT ngayXuat FROM PHIEUXUATNVL
         UNION ALL SELECT ngayXuat FROM PHIEUXUATTP
         ORDER BY ngay DESC'
    )->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($dateRows as $d) {
        $ts = strtotime((string)$d);
        if ($ts === false) { continue; }
        $mKey = date('Y-m', $ts);
        $qKey = date('Y', $ts) . '-Q' . (int)ceil((int)date('n', $ts) / 3);
        $yKey = date('Y', $ts);
        $availablePeriods['months'][$mKey] = $mKey;
        $availablePeriods['quarters'][$qKey] = $qKey;
        $availablePeriods['years'][$yKey] = $yKey;
    }
} catch (PDOException $e) {
    $reportError = 'Không thể tải dữ liệu báo cáo từ cơ sở dữ liệu.';
}
if ($availablePeriods['months'] === []) {
    $nk = date('Y-m'); $yk = date('Y');
    $qk = $yk . '-Q' . (int)ceil((int)date('n') / 3);
    $availablePeriods['months'][$nk] = $nk;
    $availablePeriods['quarters'][$qk] = $qk;
    $availablePeriods['years'][$yk] = $yk;
}
// Kỳ đang chọn -> mốc thời gian [start, end).
$rawPeriod = trim((string)($_GET['period'] ?? ''));
if ($mode === 'quarter') {
    $list = array_values($availablePeriods['quarters']);
    $def = $list[0];
    $period = preg_match('/^\d{4}-Q[1-4]$/', $rawPeriod) ? $rawPeriod : $def;
    if (!isset($availablePeriods['quarters'][$period])) { $period = $def; }
    [$pYear, $pQ] = explode('-Q', $period);
    $sm = ((int)$pQ - 1) * 3 + 1;
    $start = new DateTime(sprintf('%04d-%02d-01 00:00:00', (int)$pYear, $sm));
    $end = (clone $start)->modify('+3 months');
    $periodLabel = 'Quý ' . (int)$pQ . '/' . $pYear;
} elseif ($mode === 'year') {
    $list = array_values($availablePeriods['years']);
    $def = $list[0];
    $period = preg_match('/^\d{4}$/', $rawPeriod) ? $rawPeriod : $def;
    if (!isset($availablePeriods['years'][$period])) { $period = $def; }
    $start = new DateTime($period . '-01-01 00:00:00');
    $end = (clone $start)->modify('+1 year');
    $periodLabel = 'Năm ' . $period;
} else {
    $mode = 'month';
    $list = array_values($availablePeriods['months']);
    $def = $list[0];
    $period = preg_match('/^\d{4}-\d{2}$/', $rawPeriod) ? $rawPeriod : $def;
    if (!isset($availablePeriods['months'][$period])) { $period = $def; }
    $start = new DateTime($period . '-01 00:00:00');
    $end = (clone $start)->modify('+1 month');
    $periodLabel = 'Tháng ' . $start->format('m/Y');
}
$startStr = $start->format('Y-m-d H:i:s');
$endStr = $end->format('Y-m-d H:i:s');

// Tổng hợp số liệu thật từ CSDL.
$totalImportQty = 0; $totalImportLines = 0;
$totalExportNvlQty = 0; $totalStockNvlQty = 0; $totalStockNvlKinds = 0;
$totalExportTpQty = 0; $totalStockTpQty = 0; $totalStockTpKinds = 0;
$nvlRows = []; $tpRows = [];
if ($reportError === null) {
    try {
        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(ct.soLuong),0) t, COUNT(ct.maNVL) c
             FROM CHITIETPHIEUNHAP ct
             INNER JOIN PHIEUNHAPKHO p ON p.maPN = ct.maPN
             WHERE p.ngayNhap >= :s AND p.ngayNhap < :e'
        );
        $st->execute(['s' => $startStr, 'e' => $endStr]);
        $r = $st->fetch() ?: [];
        $totalImportQty = (int)($r['t'] ?? 0);
        $totalImportLines = (int)($r['c'] ?? 0);

        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(ct.soLuong),0)
             FROM CHITIETPHIEUXUATNVL ct
             INNER JOIN PHIEUXUATNVL p ON p.maPX = ct.maPX
             WHERE p.trangThai = 1 AND p.ngayXuat >= :s AND p.ngayXuat < :e'
        );
        $st->execute(['s' => $startStr, 'e' => $endStr]);
        $totalExportNvlQty = (int)$st->fetchColumn();

        $sr = $pdo->query(
            'SELECT COALESCE(SUM(soLuong),0) t, COUNT(*) c FROM NGUYENVATLIEU'
        )->fetch() ?: [];
        $totalStockNvlQty = (int)($sr['t'] ?? 0);
        $totalStockNvlKinds = (int)($sr['c'] ?? 0);

        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(ct.soLuong),0)
             FROM CHITIETPHIEUXUATTP ct
             INNER JOIN PHIEUXUATTP p ON p.maPX = ct.maPX
             WHERE p.trangThai = 1 AND p.ngayXuat >= :s AND p.ngayXuat < :e'
        );
        $st->execute(['s' => $startStr, 'e' => $endStr]);
        $totalExportTpQty = (int)$st->fetchColumn();

        $tr = $pdo->query(
            'SELECT COALESCE(SUM(soLuong),0) t, COUNT(*) c FROM THANHPHAM'
        )->fetch() ?: [];
        $totalStockTpQty = (int)($tr['t'] ?? 0);
        $totalStockTpKinds = (int)($tr['c'] ?? 0);

        $st = $pdo->prepare(
            'SELECT nvl.maNVL, nvl.tenNVL, dvt.tenDVT, nvl.soLuong tonKho,
                    COALESCE(nh.t,0) tongNhap, COALESCE(xu.t,0) tongXuat
             FROM NGUYENVATLIEU nvl
             INNER JOIN DONVITINH dvt ON dvt.maDVT = nvl.maDVT
             LEFT JOIN (SELECT ct.maNVL, SUM(ct.soLuong) t
                 FROM CHITIETPHIEUNHAP ct
                 INNER JOIN PHIEUNHAPKHO p ON p.maPN = ct.maPN
                 WHERE p.ngayNhap >= :s1 AND p.ngayNhap < :e1 GROUP BY ct.maNVL) nh
                 ON nh.maNVL = nvl.maNVL
             LEFT JOIN (SELECT ct.maNVL, SUM(ct.soLuong) t
                 FROM CHITIETPHIEUXUATNVL ct
                 INNER JOIN PHIEUXUATNVL p ON p.maPX = ct.maPX
                 WHERE p.trangThai = 1 AND p.ngayXuat >= :s2 AND p.ngayXuat < :e2
                 GROUP BY ct.maNVL) xu ON xu.maNVL = nvl.maNVL
             ORDER BY nvl.maNVL ASC'
        );
        $st->execute(['s1' => $startStr, 'e1' => $endStr, 's2' => $startStr, 'e2' => $endStr]);
        $nvlRows = $st->fetchAll();

        $st = $pdo->prepare(
            'SELECT tp.maTP, tp.tenTP, tp.donViTinh, tp.soLuong tonKho,
                    COALESCE(xu.t,0) tongXuat
             FROM THANHPHAM tp
             LEFT JOIN (SELECT ct.maTP, SUM(ct.soLuong) t
                 FROM CHITIETPHIEUXUATTP ct
                 INNER JOIN PHIEUXUATTP p ON p.maPX = ct.maPX
                 WHERE p.trangThai = 1 AND p.ngayXuat >= :s AND p.ngayXuat < :e
                 GROUP BY ct.maTP) xu ON xu.maTP = tp.maTP
             ORDER BY tp.maTP ASC'
        );
        $st->execute(['s' => $startStr, 'e' => $endStr]);
        $tpRows = $st->fetchAll();
    } catch (PDOException $e) {
        $reportError = 'Không thể tải dữ liệu báo cáo từ cơ sở dữ liệu.';
    }
}

// Xuất CSV + helpers hiển thị.
if (($_GET['export'] ?? '') === 'csv' && $reportError === null) {
    $fn = 'bao-cao-kho_' . preg_replace('/[^0-9A-Za-z]+/', '', $period) . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fn . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['BAO CAO TON - NHAP - XUAT KHO (' . $periodLabel . ')']);
    fputcsv($out, ['Tong nhap Nguyên Vật Liệu trong ky', $totalImportQty]);
    fputcsv($out, ['Tong xuat Nguyên Vật Liệu san xuat (da duyet)', $totalExportNvlQty]);
    fputcsv($out, ['Ton kho Nguyên Vật Liệu hien tai', $totalStockNvlQty]);
    fputcsv($out, ['Thanh pham xuat ban (da duyet)', $totalExportTpQty]);
    fputcsv($out, ['Thanh pham ton kho hien tai', $totalStockTpQty]);
    fputcsv($out, []);
    fputcsv($out, ['BANG 1: NGUYEN VAT LIEU']);
    fputcsv($out, ['Ma NVL', 'Ten NVL', 'DVT', 'Nhap trong ky', 'Xuat SX (da duyet)', 'Ton cuoi ky']);
    foreach ($nvlRows as $rw) {
        fputcsv($out, [$rw['maNVL'], $rw['tenNVL'], $rw['tenDVT'], $rw['tongNhap'], $rw['tongXuat'], $rw['tonKho']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['BANG 2: THANH PHAM']);
    fputcsv($out, ['Ma TP', 'Ten TP', 'DVT', 'Xuat ban (da duyet)', 'Ton kho']);
    foreach ($tpRows as $rw) {
        fputcsv($out, [$rw['maTP'], $rw['tenTP'], $rw['donViTinh'], $rw['tongXuat'], $rw['tonKho']]);
    }
    fclose($out);
    exit;
}

$fmt = static function ($n): string { return number_format((int)$n, 0, ',', '.'); };
$maxBar = max($totalImportQty, $totalExportNvlQty, $totalStockNvlQty, 1);
$barPct = static function ($n) use ($maxBar): int {
    return (int)round(((int)$n / $maxBar) * 100);
};
$nvlStatus = static function (int $ton): array {
    if ($ton <= 0) { return ['Hết hàng', 'bg-red-50 text-red-600 border-red-200']; }
    if ($ton <= 150) { return ['Sắp hết', 'bg-red-50 text-red-600 border-red-200']; }
    if ($ton <= 400) { return ['Mức an toàn', 'bg-amber-50 text-amber-600 border-amber-200']; }
    return ['Dồi dào', 'bg-emerald-50 text-emerald-600 border-emerald-200'];
};
$tpStatus = static function (int $xuat, int $ton): array {
    if ($ton <= 0) { return ['Hết hàng', 'bg-red-100 text-red-700']; }
    if ($xuat > 0 && $ton < 20) { return ['Bán chạy', 'bg-blue-100 text-blue-700']; }
    if ($ton < 10) { return ['Sắp hết', 'bg-amber-100 text-amber-700']; }
    return ['Sẵn sàng bán', 'bg-emerald-100 text-emerald-700'];
};
$tabClass = static function (string $t, string $c): string {
    return $t === $c
        ? 'bg-white shadow-sm text-blue-600 text-xs font-bold px-4 py-1.5 rounded-md'
        : 'text-slate-500 hover:text-slate-700 text-xs font-medium px-4 py-1.5 rounded-md';
};
$periodOptions = $mode === 'quarter' ? array_values($availablePeriods['quarters'])
    : ($mode === 'year' ? array_values($availablePeriods['years'])
    : array_values($availablePeriods['months']));
$periodOptLabel = static function (string $k, string $m): string {
    if ($m === 'quarter') { [$y, $q] = explode('-Q', $k); return 'Quý ' . $q . '/' . $y; }
    if ($m === 'year') { return 'Năm ' . $k; }
    $dt = DateTime::createFromFormat('Y-m', $k);
    return $dt ? 'Tháng ' . $dt->format('m/Y') : $k;
};
$qs = static function (array $o): string {
    return http_build_query(array_merge($_GET, $o));
};
?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-purple-100 text-purple-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-chart-simple"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Báo Cáo Thống Kê Số Lượng Tồn, Xuất & Lưu Kho</h2>
                <p class="text-xs text-slate-500">Hệ thống tự động tổng hợp số liệu từ các phiếu nhập/xuất để xuất ra các bảng báo cáo theo tháng, quý, năm về vật liệu và sản phẩm.</p>
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="?<?= htmlspecialchars($qs(['export' => 'csv', 'mode' => $mode, 'period' => $period]), ENT_QUOTES, 'UTF-8') ?>"
           class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-download"></i> Xuất Excel / CSV
        </a>
        <button type="button" onclick="window.print()"
           class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-print"></i> In Báo Cáo
        </button>
    </div>
</div>

<?php if ($reportError !== null): ?>
<div class="bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-xl mb-6">
<i class="fa-solid fa-circle-exclamation mr-2"></i><?= htmlspecialchars($reportError, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<!-- Bộ lọc -->
<form method="get" class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex justify-between items-center mb-6">
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-filter"></i> Kỳ báo cáo:</span>
        <div class="flex bg-slate-100 rounded-lg p-1">
            <a href="?<?= htmlspecialchars($qs(['mode' => 'month', 'period' => array_values($availablePeriods['months'])[0]]), ENT_QUOTES, 'UTF-8') ?>"
               class="<?= $tabClass('month', $mode) ?>">Theo Tháng</a>
            <a href="?<?= htmlspecialchars($qs(['mode' => 'quarter', 'period' => array_values($availablePeriods['quarters'])[0]]), ENT_QUOTES, 'UTF-8') ?>"
               class="<?= $tabClass('quarter', $mode) ?>">Theo Quý</a>
            <a href="?<?= htmlspecialchars($qs(['mode' => 'year', 'period' => array_values($availablePeriods['years'])[0]]), ENT_QUOTES, 'UTF-8') ?>"
               class="<?= $tabClass('year', $mode) ?>">Theo Năm</a>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <input type="hidden" name="mode" value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>">
        <select name="period" onchange="this.form.submit()"
           class="border border-slate-300 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-500 font-medium text-slate-700">
            <?php foreach ($periodOptions as $opt): ?>
            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>" <?= $opt === $period ? 'selected' : '' ?>><?= htmlspecialchars($periodOptLabel($opt, $mode), ENT_QUOTES, 'UTF-8') ?><?= $opt === $list[0] ? ' (Kỳ hiện tại)' : '' ?></option>
            <?php endforeach; ?>
        </select>
        <span class="text-[11px] text-slate-400">Dữ liệu tổng hợp từ các bảng PHIEUNHAP & PHIEUXUAT (<?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>)</span>
    </div>
</form>

<!-- 4 Thống kê -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Tổng nhập nguyên vật liệu</div>
            <i class="fa-solid fa-arrow-right-to-bracket text-emerald-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1">+<?= $fmt($totalImportQty) ?></div>
        <div class="text-[11px] text-slate-400">Gồm <?= $fmt($totalImportLines) ?> dòng nhập Nguyên Vật Liệu (<?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
    </div>
    
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Tổng xuất Nguyên Vật Liệu làm hàng</div>
            <i class="fa-solid fa-arrow-right-from-bracket text-orange-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1">-<?= $fmt($totalExportNvlQty) ?></div>
        <div class="text-[11px] text-slate-400">Cấp phát theo định mức sản xuất (đã duyệt)</div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Tồn kho Nguyên Vật Liệu lưu kho</div>
            <i class="fa-solid fa-cubes text-blue-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1"><?= $fmt($totalStockNvlQty) ?></div>
        <div class="text-[11px] text-slate-400">Tổng cộng <?= $fmt($totalStockNvlKinds) ?> chủng loại vật tư</div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Thành phẩm xuất bán</div>
            <i class="fa-solid fa-box text-purple-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1"><?= $fmt($totalExportTpQty) ?> <span class="text-sm font-normal text-slate-500">sản phẩm</span></div>
        <div class="text-[11px] text-slate-400">Hiện còn lưu kho: <strong class="text-slate-600"><?= $fmt($totalStockTpQty) ?> SP</strong></div>
    </div>
</div>

<!-- Biểu đồ ngang -->
<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-6">
    <h3 class="font-bold text-slate-800 mb-4">Cân Đối Xuất - Nhập - Tồn Kho Vật Tư (Biểu Đồ Tỷ Lệ) — <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="space-y-4">
        <!-- Bar 1 -->
        <div>
            <div class="flex justify-between text-xs mb-1 font-medium text-slate-700">
                <span>Nguyên vật liệu nhập vào (+<?= $fmt($totalImportQty) ?>)</span>
                <span class="text-emerald-600 font-bold"><?= $barPct($totalImportQty) ?>%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: <?= $barPct($totalImportQty) ?>%"></div>
            </div>
        </div>
        <!-- Bar 2 -->
        <div>
            <div class="flex justify-between text-xs mb-1 font-medium text-slate-700">
                <span>Nguyên vật liệu đã xuất sản xuất (-<?= $fmt($totalExportNvlQty) ?>)</span>
                <span class="text-orange-500 font-bold"><?= $barPct($totalExportNvlQty) ?>%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-orange-500 h-2.5 rounded-full" style="width: <?= $barPct($totalExportNvlQty) ?>%"></div>
            </div>
        </div>
        <!-- Bar 3 -->
        <div>
            <div class="flex justify-between text-xs mb-1 font-medium text-slate-700">
                <span>Tồn kho nguyên vật liệu hiện tại (<?= $fmt($totalStockNvlQty) ?>)</span>
                <span class="text-blue-600 font-bold"><?= $barPct($totalStockNvlQty) ?>%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?= $barPct($totalStockNvlQty) ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng chi tiết báo cáo -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-end mb-4">
        <h4 class="font-bold text-slate-800">1. Bảng Tổng Hợp Tồn, Nhập & Xuất Nguyên Vật Liệu (NGUYENVATLIEU) — <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></h4>
        <span class="text-[11px] text-slate-400">Đơn vị tính quy chuẩn</span>
    </div>
    
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                <tr>
                    <th class="px-4 py-3 border-b border-slate-200">Mã NVL</th>
                    <th class="px-4 py-3 border-b border-slate-200">Tên Nguyên Vật Liệu</th>
                    <th class="px-4 py-3 border-b border-slate-200">ĐVT</th>
                    <th class="px-4 py-3 text-center border-b border-slate-200">Tổng nhập trong kỳ</th>
                    <th class="px-4 py-3 text-center border-b border-slate-200">Tổng xuất sản xuất</th>
                    <th class="px-4 py-3 text-right border-b border-slate-200">Số lượng tồn cuối kỳ</th>
                    <th class="px-4 py-3 text-center border-b border-slate-200">Tình trạng</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php if ($nvlRows === []): ?>
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">Chưa có nguyên vật liệu nào trong kho.</td></tr>
                <?php else: ?>
                <?php foreach ($nvlRows as $r): ?>
                <?php [$stLabel, $stClass] = $nvlStatus((int)$r['tonKho']); ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800"><?= htmlspecialchars($r['maNVL'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($r['tenNVL'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($r['tenDVT'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-center text-emerald-600 font-medium">+<?= $fmt($r['tongNhap']) ?></td>
                    <td class="px-4 py-3 text-center text-orange-500 font-medium">-<?= $fmt($r['tongXuat']) ?></td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600"><?= $fmt($r['tonKho']) ?> <?= htmlspecialchars($r['tenDVT'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-center"><span class="border text-[10px] px-2 py-0.5 rounded font-medium <?= $stClass ?>"><?= $stLabel ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bảng tổng hợp sản xuất & xuất bán thành phẩm -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex justify-between items-end mb-4">
        <h4 class="font-bold text-slate-800">2. Bảng Tổng Hợp Sản Xuất &amp; Xuất Bán Thành Phẩm (THANHPHAM) — <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></h4>
        <span class="text-[11px] text-slate-400">Lưu kho thành phẩm</span>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                <tr>
                    <th class="px-4 py-3 border-b border-slate-200">Mã TP</th>
                    <th class="px-4 py-3 border-b border-slate-200">Tên Thành Phẩm</th>
                    <th class="px-4 py-3 border-b border-slate-200">Đơn vị tính</th>
                    <th class="px-4 py-3 text-right border-b border-slate-200">Tổng xuất bán đã duyệt</th>
                    <th class="px-4 py-3 text-right border-b border-slate-200 bg-emerald-50/60">Hiện tồn kho</th>
                    <th class="px-4 py-3 text-center border-b border-slate-200">Tình trạng kinh doanh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php if ($tpRows === []): ?>
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Chưa có thành phẩm nào trong kho.</td></tr>
                <?php else: ?>
                <?php foreach ($tpRows as $r): ?>
                <?php [$tpLabel, $tpClass] = $tpStatus((int)$r['tongXuat'], (int)$r['tonKho']); ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800"><?= htmlspecialchars($r['maTP'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($r['tenTP'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($r['donViTinh'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-right text-orange-600 font-medium"><?= $fmt($r['tongXuat']) ?> <?= htmlspecialchars($r['donViTinh'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-right bg-emerald-50/40 font-bold text-emerald-700"><?= $fmt($r['tonKho']) ?> <?= htmlspecialchars($r['donViTinh'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-center"><span class="text-[10px] px-2 py-0.5 rounded font-medium <?= $tpClass ?>"><?= $tpLabel ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>