<?php require_once '../includes/header.php'; ?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg text-xl"><i class="fa-regular fa-clipboard"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Lệnh Sản Xuất & Kiểm Tra Định Mức Vật Tư (YEUCAU)</h2>
                <p class="text-xs text-slate-500">Quản lý đề nghị số lượng thành phẩm & Nhân viên đối chiếu định mức NVL, kiểm tra thiếu đủ để xin xuất kho hoặc xác nhận hoàn thành.</p>
            </div>
        </div>
    </div>
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2"
    id="btn-open-add-order">
        <i class="fa-solid fa-plus"></i> Khởi Tạo Lệnh Sản Xuất Mới
    </button>
</div>

<div class="flex gap-6 items-start">
    <!-- CỘT TRÁI: Danh sách Lệnh Sản Xuất -->
    <div class="w-1/3 shrink-0">
        <div class="flex justify-between items-end mb-3 px-1">
            <h3 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH LỆNH SẢN XUẤT (3)</h3>
            <span class="text-xs text-blue-500 font-medium">BẢNG YEUCAU</span>
        </div>
        
        <div class="space-y-3">
            <!-- Active Item (Đang làm) -->
            <div class="bg-blue-50 border-2 border-blue-500 rounded-xl p-4 shadow-sm cursor-pointer relative overflow-hidden">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <span class="bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded">YC-2026-001</span>
                        <span class="bg-blue-100 text-blue-700 text-[10px] font-medium px-2 py-0.5 rounded">Đang làm</span>
                    </div>
                    <i class="fa-solid fa-arrow-right text-blue-500 text-sm"></i>
                </div>
                <div class="text-sm font-semibold text-slate-800 mb-1">Bàn làm việc chữ U &bull; <span class="font-bold">50 Cái</span></div>
                <div class="text-[11px] text-slate-500">Hạn: <strong class="font-medium text-slate-700">2026-09-25</strong> &bull; Quản lý: NV01</div>
            </div>

            <!-- Inactive Item 1 (Chờ xử lý) -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-blue-300 transition">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded border border-slate-200">YC-2026-002</span>
                        <span class="bg-amber-100 text-amber-700 text-[10px] font-medium px-2 py-0.5 rounded">Chờ xử lý</span>
                    </div>
                    <i class="fa-solid fa-arrow-right text-slate-300 text-sm"></i>
                </div>
                <div class="text-sm font-semibold text-slate-800 mb-1">Bàn họp chân sắt &bull; <span class="font-bold">20 Cái</span></div>
                <div class="text-[11px] text-slate-500">Hạn: <strong class="font-medium text-slate-700">2026-09-30</strong> &bull; Quản lý: NV04</div>
            </div>
            
            <!-- Inactive Item 2 (Đã xong) -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-blue-300 transition">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded border border-slate-200">YC-2026-003</span>
                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-medium px-2 py-0.5 rounded">Đã xong</span>
                    </div>
                    <i class="fa-solid fa-arrow-right text-slate-300 text-sm"></i>
                </div>
                <div class="text-sm font-semibold text-slate-800 mb-1">Ghế xoay văn phòng &bull; <span class="font-bold">40 Cái</span></div>
                <div class="text-[11px] text-slate-500">Hạn: <strong class="font-medium text-slate-700">2026-09-12</strong> &bull; Quản lý: NV01</div>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: Chi tiết Lệnh & Đối chiếu BOM -->
    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6 flex flex-col">
        <!-- Thông tin Header -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-5">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-blue-100 text-blue-800 font-bold text-xs px-2 py-1 rounded">Mã Lệnh: YC-2026-001</span>
                    <span class="text-slate-400 text-[11px]">Ngày tạo: 2026-09-15 08:00:00</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">Nhiệm vụ: Sản xuất 50 Cái Bàn làm việc chữ U (TP01)</h3>
                <p class="text-slate-500 text-[11px]">Ghi chú: Sản xuất phục vụ hợp đồng văn phòng FPT Software</p>
            </div>
            <div>
                <span class="border border-blue-200 bg-blue-50 text-blue-600 text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1 uppercase">
                    <i class="fa-regular fa-clock"></i> Đang tiến hành sản xuất
                </span>
            </div>
        </div>

        <!-- Bảng Đối chiếu BOM -->
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-bold text-blue-800 text-xs flex items-center gap-2">
                <i class="fa-solid fa-calculator text-blue-500"></i> TỰ ĐỘNG NHÂN ĐỊNH MỨC (SỐ LƯỢNG X BOM) & ĐỐI CHIẾU KHO NVL
            </h4>
            <span class="bg-emerald-50 text-emerald-600 text-[11px] font-medium px-2.5 py-1 rounded-full border border-emerald-200 flex items-center gap-1">
                <i class="fa-regular fa-circle-check"></i> Kho có đủ vật tư để đáp ứng lệnh này!
            </span>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-slate-200 mb-6 flex-1">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3 border-b border-slate-200">Mã NVL</th>
                        <th class="px-4 py-3 border-b border-slate-200">Tên Nguyên Vật Liệu</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Định mức 1 SP</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Tổng cần dùng</th>
                        <th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Tình trạng kho</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-700">NVL01</td>
                        <td class="px-4 py-3 text-slate-700">Mặt bàn gỗ sồi (120x60cm)</td>
                        <td class="px-4 py-3 text-center text-slate-500">1 Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600">50 Cái</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">145 Cái</td>
                        <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded-full font-medium flex items-center justify-center gap-1 w-max mx-auto"><i class="fa-solid fa-check text-[8px]"></i> Đủ vật tư</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-700">NVL02</td>
                        <td class="px-4 py-3 text-slate-700">Chân bàn sắt sơn tĩnh điện</td>
                        <td class="px-4 py-3 text-center text-slate-500">4 Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600">200 Cái</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">380 Cái</td>
                        <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded-full font-medium flex items-center justify-center gap-1 w-max mx-auto"><i class="fa-solid fa-check text-[8px]"></i> Đủ vật tư</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-700">NVL03</td>
                        <td class="px-4 py-3 text-slate-700">Bộ ốc vít ren thép M6</td>
                        <td class="px-4 py-3 text-center text-slate-500">16 Ốc/Tán</td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600">800 Ốc/Tán</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">2,400 Ốc/Tán</td>
                        <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded-full font-medium flex items-center justify-center gap-1 w-max mx-auto"><i class="fa-solid fa-check text-[8px]"></i> Đủ vật tư</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-700">NVL08</td>
                        <td class="px-4 py-3 text-slate-700">Thanh giằng thép chịu lực</td>
                        <td class="px-4 py-3 text-center text-slate-500">2 Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600">100 Cái</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">210 Cái</td>
                        <td class="px-4 py-3 text-center"><span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded-full font-medium flex items-center justify-center gap-1 w-max mx-auto"><i class="fa-solid fa-check text-[8px]"></i> Đủ vật tư</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Hành Động Tiếp Theo -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex justify-between items-center mt-auto">
            <div>
                <h4 class="font-bold text-slate-800 text-xs mb-1">Hành Động Tiếp Theo Dành Cho Nhân Viên & Quản Lý</h4>
                <p class="text-[10px] text-slate-500">1. Đề xuất xuất kho NVL để có đồ làm việc &rarr; 2. Làm xong bấm xác nhận Hoàn thành để tăng tồn kho thành phẩm.</p>
            </div>
            <div class="flex flex-col gap-2">
                <button class="bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-medium px-4 py-2 rounded-lg transition shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-regular fa-paper-plane"></i> Lập Đề Nghị Xuất Kho NVL
                </button>
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium px-4 py-2 rounded-lg transition shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check-circle"></i> Xác Nhận Đã Hoàn Thành Sản Phẩm (+Kho TP)
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- MODAL: Tạo Lệnh Sản Xuất Mới -->
<div id="modal-add-order" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-xl w-full max-w-[500px] shadow-2xl flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-center p-5 border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-lg">Tạo Lệnh Sản Xuất Mới (YEUCAU)</h3>
            <button class="close-modal text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <!-- Body -->
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Mã yêu cầu lệnh (maYC)</label>
                <input type="text" value="YC-2026-004" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Chọn thành phẩm cần sản xuất (THANHPHAM)</label>
                <select class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-slate-700">
                    <option>Bàn làm việc chữ U (TP01) - Đang tồn: 45 Cái</option>
                    <option>Ghế xoay văn phòng (TP02) - Đang tồn: 30 Cái</option>
                </select>
            </div>
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Số lượng thành phẩm cần làm</label>
                    <input type="number" value="50" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex-1">
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Hạn hoàn thành</label>
                    <input type="date" value="2026-09-30" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-slate-700">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Mục đích / Ghi chú cho nhân viên sản xuất</label>
                <textarea rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 placeholder-slate-400" placeholder="VD: Sản xuất 50 bàn cho hợp đồng FPT Software"></textarea>
            </div>
        </div>
        <!-- Footer -->
        <div class="p-5 border-t border-slate-100 flex justify-end items-center gap-4 bg-slate-50 rounded-b-xl">
            <button class="close-modal text-sm font-medium text-slate-500 hover:text-slate-800 transition">Hủy</button>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition">Phát Lệnh Tới Nhân Viên</button>
        </div>
    </div>
</div>

<script src="../assets/js/production.js"></script>

<?php require_once '../includes/footer.php'; ?>