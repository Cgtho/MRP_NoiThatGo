<?php require_once '../includes/header.php'; ?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Định Mức Cấu Thành Sản Phẩm (BOM - Bill of Materials)</h2>
                <p class="text-xs text-slate-500">Khai báo công thức sản phẩm: 1 sản phẩm hoàn chỉnh cần bao nhiêu nguyên vật liệu chi tiết (Bảng CHITIETTHANHPHAM).</p>
            </div>
        </div>
    </div>
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Thêm Sản Phẩm & Định Mức Mới
    </button>
</div>

<div class="flex gap-6 items-start">
    <!-- CỘT TRÁI: Danh sách -->
    <div class="w-1/3 shrink-0">
        <div class="flex justify-between items-end mb-3 px-1">
            <h3 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH THÀNH PHẨM (3)</h3>
            <span class="text-xs text-blue-500 font-medium">BẢNG THANHPHAM</span>
        </div>
        
        <div class="space-y-3">
            <!-- Active Item -->
            <div class="bg-white border-2 border-blue-500 rounded-xl p-4 shadow-sm cursor-pointer relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-2">
                        <span class="bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded">TP01</span>
                        <h4 class="font-bold text-slate-800 text-base">Bàn làm việc chữ U</h4>
                    </div>
                    <span class="text-emerald-500 font-medium text-sm">45 tồn kho</span>
                </div>
                <div class="flex justify-between items-center text-xs text-slate-500">
                    <span>Đơn vị tính: Cái</span>
                    <span>4 loại vật tư</span>
                </div>
            </div>

            <!-- Inactive Item -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-blue-300 transition">
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-2">
                        <span class="bg-slate-200 text-slate-600 text-xs font-bold px-2 py-0.5 rounded">TP02</span>
                        <h4 class="font-bold text-slate-800 text-base">Ghế xoay văn phòng</h4>
                    </div>
                    <span class="text-emerald-500 font-medium text-sm">30 tồn kho</span>
                </div>
                <div class="flex justify-between items-center text-xs text-slate-500">
                    <span>Đơn vị tính: Cái</span>
                    <span>4 loại vật tư</span>
                </div>
            </div>
            
            <!-- Inactive Item -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-blue-300 transition">
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-2">
                        <span class="bg-slate-200 text-slate-600 text-xs font-bold px-2 py-0.5 rounded">TP03</span>
                        <h4 class="font-bold text-slate-800 text-base">Bàn họp chân sắt</h4>
                    </div>
                    <span class="text-emerald-500 font-medium text-sm">12 tồn kho</span>
                </div>
                <div class="flex justify-between items-center text-xs text-slate-500">
                    <span>Đơn vị tính: Cái</span>
                    <span>4 loại vật tư</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: Chi tiết định mức -->
    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <!-- Thông tin sản phẩm đang chọn -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-blue-600 font-bold text-sm">Mã TP: TP01</span>
                    <h3 class="text-2xl font-bold text-slate-800">Bàn làm việc chữ U</h3>
                </div>
                <p class="text-slate-500 text-xs">Công thức định mức cấu thành cho 1 Cái sản phẩm.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-emerald-50 text-emerald-700 text-xs px-3 py-2 rounded-lg border border-emerald-200">
                    Tồn kho vật tư hiện tại có thể sản xuất tối đa: <strong class="text-base">95</strong> Cái
                </div>
                <button class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Thêm Vật Tư Vào BOM
                </button>
            </div>
        </div>

        <!-- Bảng chi tiết NVL -->
        <div class="flex justify-between items-end mb-3">
            <h4 class="font-bold text-slate-700 text-xs uppercase">CHI TIẾT ĐỊNH MỨC VẬT TƯ (BẢNG CHITIETTHANHPHAM)</h4>
            <span class="text-[11px] text-slate-400">Dữ liệu gốc tính toán nhu cầu sản xuất</span>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-slate-200 mb-6">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3">Mã NVL</th>
                        <th class="px-4 py-3">Tên Nguyên Vật Liệu</th>
                        <th class="px-4 py-3">Đơn vị tính</th>
                        <th class="px-4 py-3 text-center">Định mức (1 SP)</th>
                        <th class="px-4 py-3 text-right">Tồn kho thực tế</th>
                        <th class="px-4 py-3 text-center">Khả năng đáp ứng</th>
                        <th class="px-4 py-3 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-medium text-slate-800">NVL01</td>
                        <td class="px-4 py-3">Mặt bàn gỗ sồi (120x60cm)</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600">1</td>
                        <td class="px-4 py-3 text-right font-medium">145 Cái</td>
                        <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-1 rounded-full font-medium">Làm được 145 cái</span></td>
                        <td class="px-4 py-3 text-center text-slate-400 hover:text-red-500 cursor-pointer"><i class="fa-regular fa-trash-can"></i></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-medium text-slate-800">NVL02</td>
                        <td class="px-4 py-3">Chân bàn sắt sơn tĩnh điện</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600">4</td>
                        <td class="px-4 py-3 text-right font-medium">380 Cái</td>
                        <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-1 rounded-full font-medium">Làm được 95 cái</span></td>
                        <td class="px-4 py-3 text-center text-slate-400 hover:text-red-500 cursor-pointer"><i class="fa-regular fa-trash-can"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Công cụ mô phỏng -->
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-2 text-blue-800 font-bold text-sm">
                    <i class="fa-solid fa-calculator"></i> CÔNG CỤ MÔ PHỎNG NHU CẦU VẬT TƯ THEO ĐỊNH MỨC
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-600">Nhập số lượng cần làm:</span>
                    <input type="number" value="100" class="w-20 px-2 py-1 border border-slate-300 rounded text-right font-bold text-blue-600 focus:outline-none focus:border-blue-500">
                    <span class="text-xs text-slate-600">Cái</span>
                </div>
            </div>
            
            <div class="grid grid-cols-4 gap-3">
                <!-- Đủ vật tư -->
                <div class="bg-white border-l-4 border-emerald-500 p-3 rounded shadow-sm">
                    <div class="text-[11px] text-slate-500 truncate mb-1">Mặt bàn gỗ sồi (120x60cm)</div>
                    <div class="font-bold text-slate-800">Cần: 100</div>
                    <div class="text-[10px] text-emerald-600 mt-1">Kho đủ đáp ứng</div>
                </div>
                <!-- Thiếu vật tư (Báo đỏ) -->
                <div class="bg-white border-l-4 border-red-500 p-3 rounded shadow-sm">
                    <div class="text-[11px] text-slate-500 truncate mb-1">Chân bàn sắt sơn tĩnh điện</div>
                    <div class="font-bold text-slate-800">Cần: 400</div>
                    <div class="text-[10px] text-red-500 font-semibold mt-1">Thiếu 20 cái!</div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>