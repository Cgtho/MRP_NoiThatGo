<?php require_once '../includes/header.php'; ?>

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
        <button class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-download"></i> Xuất Excel / CSV
        </button>
        <button class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-print"></i> In Báo Cáo
        </button>
    </div>
</div>

<!-- Bộ lọc -->
<div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex justify-between items-center mb-6">
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-filter"></i> Kỳ báo cáo:</span>
        <div class="flex bg-slate-100 rounded-lg p-1">
            <button class="bg-white shadow-sm text-blue-600 text-xs font-bold px-4 py-1.5 rounded-md">Theo Tháng</button>
            <button class="text-slate-500 hover:text-slate-700 text-xs font-medium px-4 py-1.5 rounded-md">Theo Quý</button>
            <button class="text-slate-500 hover:text-slate-700 text-xs font-medium px-4 py-1.5 rounded-md">Theo Năm</button>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <select class="border border-slate-300 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-500 font-medium text-slate-700">
            <option>Tháng 09/2026 (Kỳ hiện tại)</option>
        </select>
        <span class="text-[11px] text-slate-400">Dữ liệu tổng hợp từ các bảng PHIEUNHAP & PHIEUXUAT</span>
    </div>
</div>

<!-- 4 Thống kê -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Tổng nhập NVL</div>
            <i class="fa-solid fa-arrow-right-to-bracket text-emerald-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1">+2,730</div>
        <div class="text-[11px] text-slate-400">Gồm 6 lần nhập nguyên vật liệu</div>
    </div>
    
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Tổng xuất NVL làm hàng</div>
            <i class="fa-solid fa-arrow-right-from-bracket text-orange-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1">-600</div>
        <div class="text-[11px] text-slate-400">Cấp phát theo định mức sản xuất</div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Tồn kho NVL lưu kho</div>
            <i class="fa-solid fa-cubes text-blue-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1">3,445</div>
        <div class="text-[11px] text-slate-400">Tổng cộng 8 chủng loại vật tư</div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500 uppercase">Thành phẩm xuất bán</div>
            <i class="fa-solid fa-box text-purple-500"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1">15 <span class="text-sm font-normal text-slate-500">sản phẩm</span></div>
        <div class="text-[11px] text-slate-400">Hiện còn lưu kho: <strong class="text-slate-600">87 SP</strong></div>
    </div>
</div>

<!-- Biểu đồ ngang -->
<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-6">
    <h3 class="font-bold text-slate-800 mb-4">Cân Đối Xuất - Nhập - Tồn Kho Vật Tư (Biểu Đồ Tỷ Lệ)</h3>
    <div class="space-y-4">
        <!-- Bar 1 -->
        <div>
            <div class="flex justify-between text-xs mb-1 font-medium text-slate-700">
                <span>Nguyên vật liệu nhập vào (+2730)</span>
                <span class="text-emerald-600 font-bold">100%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: 100%"></div>
            </div>
        </div>
        <!-- Bar 2 -->
        <div>
            <div class="flex justify-between text-xs mb-1 font-medium text-slate-700">
                <span>Nguyên vật liệu đã xuất sản xuất (-600)</span>
                <span class="text-orange-500 font-bold">22%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-orange-500 h-2.5 rounded-full" style="width: 22%"></div>
            </div>
        </div>
        <!-- Bar 3 -->
        <div>
            <div class="flex justify-between text-xs mb-1 font-medium text-slate-700">
                <span>Tồn kho nguyên vật liệu hiện tại (3445)</span>
                <span class="text-blue-600 font-bold">85%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: 85%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng chi tiết báo cáo -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-end mb-4">
        <h4 class="font-bold text-slate-800">1. Bảng Tổng Hợp Tồn, Nhập & Xuất Nguyên Vật Liệu (NGUYENVATLIEU)</h4>
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
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL01</td>
                    <td class="px-4 py-3 text-slate-700">Mặt bàn gỗ sồi (120x60cm)</td>
                    <td class="px-4 py-3 text-slate-500">Cái</td>
                    <td class="px-4 py-3 text-center text-emerald-600 font-medium">+100</td>
                    <td class="px-4 py-3 text-center text-orange-500 font-medium">-0</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600">145 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-0.5 rounded font-medium">Dồi dào</span></td>
                </tr>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL02</td>
                    <td class="px-4 py-3 text-slate-700">Chân bàn sắt sơn tĩnh điện</td>
                    <td class="px-4 py-3 text-slate-500">Cái</td>
                    <td class="px-4 py-3 text-center text-emerald-600 font-medium">+400</td>
                    <td class="px-4 py-3 text-center text-orange-500 font-medium">-0</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600">380 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-0.5 rounded font-medium">Dồi dào</span></td>
                </tr>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL03</td>
                    <td class="px-4 py-3 text-slate-700">Bộ ốc vít ren thép M6</td>
                    <td class="px-4 py-3 text-slate-500">Ốc/Tán</td>
                    <td class="px-4 py-3 text-center text-emerald-600 font-medium">+2,000</td>
                    <td class="px-4 py-3 text-center text-orange-500 font-medium">-480</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600">2,400 Ốc/Tán</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-0.5 rounded font-medium">Dồi dào</span></td>
                </tr>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL04</td>
                    <td class="px-4 py-3 text-slate-700">Sơn PU chống trầy (Lon 1L)</td>
                    <td class="px-4 py-3 text-slate-500">Thùng</td>
                    <td class="px-4 py-3 text-center text-emerald-600 font-medium">+30</td>
                    <td class="px-4 py-3 text-center text-orange-500 font-medium">-0</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600">65 Thùng</td>
                    <td class="px-4 py-3 text-center"><span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2 py-0.5 rounded font-medium">Mức an toàn</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Bảng tổng hợp sản xuất & xuất bán thành phẩm -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex justify-between items-end mb-4">
        <h4 class="font-bold text-slate-800">2. Bảng Tổng Hợp Sản Xuất &amp; Xuất Bán Thành Phẩm (THANHPHAM)</h4>
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
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">TP01</td>
                    <td class="px-4 py-3 text-slate-700">Bàn làm việc chữ U</td>
                    <td class="px-4 py-3 text-slate-500">Cái</td>
                    <td class="px-4 py-3 text-right text-orange-600 font-medium">0 Cái</td>
                    <td class="px-4 py-3 text-right bg-emerald-50/40 font-bold text-emerald-700">45 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded font-medium">Sẵn sàng bán</span></td>
                </tr>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">TP02</td>
                    <td class="px-4 py-3 text-slate-700">Ghế xoay văn phòng</td>
                    <td class="px-4 py-3 text-slate-500">Cái</td>
                    <td class="px-4 py-3 text-right text-orange-600 font-medium">15 Cái</td>
                    <td class="px-4 py-3 text-right bg-emerald-50/40 font-bold text-emerald-700">30 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded font-medium">Sẵn sàng bán</span></td>
                </tr>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">TP03</td>
                    <td class="px-4 py-3 text-slate-700">Bàn họp chân sắt</td>
                    <td class="px-4 py-3 text-slate-500">Cái</td>
                    <td class="px-4 py-3 text-right text-orange-600 font-medium">0 Cái</td>
                    <td class="px-4 py-3 text-right bg-emerald-50/40 font-bold text-emerald-700">12 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded font-medium">Sẵn sàng bán</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>