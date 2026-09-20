<?php require_once '../includes/header.php'; ?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-amber-100 text-amber-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-cubes-stacked"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Tra Cứu Danh Mục Kho & Dữ Liệu Gốc (Master Data)</h2>
                <p class="text-xs text-slate-500">Tra cứu trực tiếp các bảng danh mục: NGUYENVATLIEU, THANHPHAM, DONVITINH, NHANVIEN.</p>
            </div>
        </div>
    </div>
    <div class="relative w-64">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-sm"></i>
        <input type="text" placeholder="Tìm kiếm mã, tên..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 shadow-sm">
    </div>
</div>

<!-- Tabs Điều Hướng -->
<div class="flex items-center gap-2 mb-6 border-b border-slate-200 pb-2">
    <button class="bg-blue-600 text-white text-[13px] font-medium px-4 py-2 rounded-lg shadow-sm flex items-center gap-2">
        <i class="fa-solid fa-cubes"></i> Nguyên Vật Liệu (8)
    </button>
    <button class="text-slate-500 hover:text-slate-800 text-[13px] font-medium px-4 py-2 flex items-center gap-2 transition">
        <i class="fa-solid fa-box"></i> Thành Phẩm (3)
    </button>
    <button class="text-slate-500 hover:text-slate-800 text-[13px] font-medium px-4 py-2 flex items-center gap-2 transition">
        <i class="fa-solid fa-tags"></i> Đơn Vị Tính (6)
    </button>
    <button class="text-slate-500 hover:text-slate-800 text-[13px] font-medium px-4 py-2 flex items-center gap-2 transition">
        <i class="fa-solid fa-users"></i> Nhân Viên & Vai Trò (4)
    </button>
</div>

<!-- Bảng Master Data -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-end mb-4">
        <h3 class="font-bold text-slate-800">Bảng NGUYENVATLIEU (maNVL, tenNVL, maDVT, soLuong)</h3>
        <span class="text-[11px] text-slate-400">Tồn kho nguyên vật liệu hiện tại</span>
    </div>
    
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                <tr>
                    <th class="px-4 py-3 border-b border-slate-200 w-32">Mã NVL (PK)</th>
                    <th class="px-4 py-3 border-b border-slate-200">Tên Nguyên Vật Liệu</th>
                    <th class="px-4 py-3 border-b border-slate-200 w-48 text-center">Mã ĐVT (FK)</th>
                    <th class="px-4 py-3 border-b border-slate-200 w-48 text-right">Số Lượng Tồn Kho (soLuong)</th>
                    <th class="px-4 py-3 border-b border-slate-200 w-40 text-center">Cảnh Báo Dự Trữ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <!-- Row 1 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL01</td>
                    <td class="px-4 py-3 text-slate-700">Mặt bàn gỗ sồi (120x60cm)</td>
                    <td class="px-4 py-3 text-slate-500 text-center">1 - Cái</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">145 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium">Tồn kho an toàn</span></td>
                </tr>
                <!-- Row 2 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL02</td>
                    <td class="px-4 py-3 text-slate-700">Chân bàn sắt sơn tĩnh điện</td>
                    <td class="px-4 py-3 text-slate-500 text-center">1 - Cái</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">380 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium">Tồn kho an toàn</span></td>
                </tr>
                <!-- Row 3 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL03</td>
                    <td class="px-4 py-3 text-slate-700">Bộ ốc vít ren thép M6</td>
                    <td class="px-4 py-3 text-slate-500 text-center">6 - Ốc/Tán</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">2,400 Ốc/Tán</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium">Tồn kho an toàn</span></td>
                </tr>
                <!-- Row 4 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL04</td>
                    <td class="px-4 py-3 text-slate-700">Sơn PU chống trầy (Lon 1L)</td>
                    <td class="px-4 py-3 text-slate-500 text-center">5 - Thùng</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">65 Thùng</td>
                    <td class="px-4 py-3 text-center"><span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Cần nhập thêm</span></td>
                </tr>
                <!-- Row 5 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL05</td>
                    <td class="px-4 py-3 text-slate-700">Mặt ghế bọc nệm simili</td>
                    <td class="px-4 py-3 text-slate-500 text-center">1 - Cái</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">80 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Cần nhập thêm</span></td>
                </tr>
                <!-- Row 6 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL06</td>
                    <td class="px-4 py-3 text-slate-700">Khung lưng ghế uốn cong</td>
                    <td class="px-4 py-3 text-slate-500 text-center">1 - Cái</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">95 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Cần nhập thêm</span></td>
                </tr>
                <!-- Row 7 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL07</td>
                    <td class="px-4 py-3 text-slate-700">Chân ghế xoay inox</td>
                    <td class="px-4 py-3 text-slate-500 text-center">1 - Cái</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">70 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2 py-1 rounded-full font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Cần nhập thêm</span></td>
                </tr>
                <!-- Row 8 -->
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 font-bold text-slate-800">NVL08</td>
                    <td class="px-4 py-3 text-slate-700">Thanh giằng thép chịu lực</td>
                    <td class="px-4 py-3 text-slate-500 text-center">1 - Cái</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-sm">210 Cái</td>
                    <td class="px-4 py-3 text-center"><span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-1 rounded-full font-medium">Tồn kho an toàn</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>