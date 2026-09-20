<?php require_once '../includes/header.php'; ?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-orange-100 text-orange-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-box-open"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Xuất Kho Nguyên Vật Liệu (PHIEUXUATNVL)</h2>
                <p class="text-xs text-slate-500">Nhân viên xin cấp vật tư &rarr; Quản lý phê duyệt (trangThai: 0 &rarr; 1). Lúc này hệ thống tự động trừ đi số lượng vật tư trong kho.</p>
            </div>
        </div>
    </div>
    <button class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Lập Đề Nghị Xuất NVL Mới
    </button>
</div>

<!-- Tabs Lọc Trạng Thái -->
<div class="flex items-center gap-2 mb-4 border-b border-slate-200 pb-2">
    <button class="bg-slate-900 text-white text-[13px] font-medium px-4 py-1.5 rounded-full shadow-sm">Tất cả (2)</button>
    <button class="text-slate-500 hover:text-slate-800 text-[13px] font-medium px-4 py-1.5 flex items-center gap-2 transition"><i class="fa-regular fa-clock"></i> Chờ duyệt (1)</button>
    <button class="text-slate-500 hover:text-slate-800 text-[13px] font-medium px-4 py-1.5 flex items-center gap-2 transition"><i class="fa-regular fa-circle-check"></i> Đã duyệt (1)</button>
</div>

<div class="flex gap-6 items-start">
    <!-- CỘT TRÁI: Danh sách Phiếu Xuất -->
    <div class="w-1/3 shrink-0 space-y-3">
        <!-- Active Card (Đã duyệt) -->
        <div class="bg-white border-2 border-amber-400 rounded-xl p-4 shadow-sm cursor-pointer relative overflow-hidden">
            <div class="flex justify-between items-start mb-2">
                <div class="flex items-center gap-2">
                    <span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded">PXNVL-001</span>
                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-medium px-2 py-0.5 rounded">Đã duyệt (trừ kho)</span>
                </div>
            </div>
            <div class="text-sm text-slate-700 mb-1 font-medium">Cấp vật tư cho lệnh YC-2026-003</div>
            <div class="text-[11px] text-slate-400">2026-09-03 10:00:00 &bull; Xin cấp: Trần Thị Sản Xuất</div>
        </div>

        <!-- Inactive Card (Chờ duyệt) -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-amber-300 transition">
            <div class="flex justify-between items-start mb-2">
                <div class="flex items-center gap-2">
                    <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded border border-slate-200">PXNVL-002</span>
                    <span class="bg-amber-100 text-amber-700 text-[10px] font-medium px-2 py-0.5 rounded">Chờ QL duyệt</span>
                </div>
                <span class="bg-red-50 text-red-600 text-[10px] font-medium px-2 py-0.5 rounded border border-red-100">Cần duyệt</span>
            </div>
            <div class="text-sm text-slate-700 mb-1 font-medium">Xin cấp vật tư làm 50 bàn chữ U (YC-2026-001)</div>
            <div class="text-[11px] text-slate-400">2026-09-16 11:20:00 &bull; Xin cấp: Trần Thị Sản Xuất</div>
        </div>
    </div>

    <!-- CỘT PHẢI: Chi tiết Phiếu Xuất -->
    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <!-- Header Chi tiết -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="bg-orange-50 text-orange-700 font-bold text-xs px-2 py-1 rounded border border-orange-100">MÃ PHIẾU XUẤT: PXNVL-001</span>
                    <span class="text-slate-400 text-xs flex items-center gap-1"><i class="fa-regular fa-calendar"></i> 2026-09-03 10:00:00</span>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-2">
                    <div>
                        <div class="text-[11px] text-slate-400">Nhân viên yêu cầu (maNV):</div>
                        <div class="text-sm font-semibold text-slate-800">Trần Thị Sản Xuất</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400">Quản lý phê duyệt (maQL):</div>
                        <div class="text-sm font-semibold text-slate-800">Nguyễn Văn Quản</div>
                    </div>
                </div>
                <div class="text-sm text-slate-600">Mục đích: <span class="font-medium text-slate-800">Cấp vật tư cho lệnh YC-2026-003</span></div>
            </div>
            <div>
                <span class="border border-emerald-200 bg-emerald-50 text-emerald-600 text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1 uppercase">
                    <i class="fa-regular fa-circle-check"></i> Đã phê duyệt (Đã trừ kho)
                </span>
            </div>
        </div>

        <!-- Bảng Danh Sách Vật Tư Xin Xuất -->
        <div class="mb-3">
            <h4 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH VẬT TƯ XIN XUẤT (BẢNG CHITIETPHIEUXUATNVL)</h4>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3 border-b border-slate-200">Mã NVL</th>
                        <th class="px-4 py-3 border-b border-slate-200">Tên Nguyên Vật Liệu</th>
                        <th class="px-4 py-3 border-b border-slate-200">ĐVT</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Số lượng xuất</th>
                        <th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Khả năng xuất</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-800">NVL05</td>
                        <td class="px-4 py-3 text-slate-700">Mặt ghế bọc nệm simili</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-[#ea580c]">40</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">80 Cái</td>
                        <td class="px-4 py-3 text-center text-emerald-600 font-medium">Đã xuất kho</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-800">NVL06</td>
                        <td class="px-4 py-3 text-slate-700">Khung lưng ghế uốn cong</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-[#ea580c]">40</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">95 Cái</td>
                        <td class="px-4 py-3 text-center text-emerald-600 font-medium">Đã xuất kho</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-800">NVL07</td>
                        <td class="px-4 py-3 text-slate-700">Chân ghế xoay inox</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-[#ea580c]">40</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">70 Cái</td>
                        <td class="px-4 py-3 text-center text-emerald-600 font-medium">Đã xuất kho</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-800">NVL03</td>
                        <td class="px-4 py-3 text-slate-700">Bộ ốc vít ren thép M6</td>
                        <td class="px-4 py-3 text-slate-500">Ốc/Tán</td>
                        <td class="px-4 py-3 text-center font-bold text-[#ea580c]">480</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">2,400 Ốc/Tán</td>
                        <td class="px-4 py-3 text-center text-emerald-600 font-medium">Đã xuất kho</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>