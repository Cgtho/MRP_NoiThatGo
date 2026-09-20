<?php require_once '../includes/header.php'; ?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-teal-100 text-teal-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-truck-fast"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Xuất Kho Thành Phẩm Để Bán (PHIEUXUATTP)</h2>
                <p class="text-xs text-slate-500">Khi có đơn hàng cần giao đi, nhân viên lập phiếu xuất bàn/ghế gửi Quản lý duyệt. Khi duyệt, hệ thống giảm tồn kho thành phẩm lưu kho.</p>
            </div>
        </div>
    </div>
    <button class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Lập Phiếu Đề Nghị Xuất TP
    </button>
</div>

<!-- Tabs Lọc Trạng Thái -->
<div class="flex items-center gap-2 mb-4 border-b border-slate-200 pb-2">
    <button class="bg-slate-900 text-white text-[13px] font-medium px-4 py-1.5 rounded-full shadow-sm">Tất cả (2)</button>
    <button class="text-slate-500 hover:text-slate-800 text-[13px] font-medium px-4 py-1.5 flex items-center gap-2 transition"><i class="fa-regular fa-clock"></i> Chờ duyệt (1)</button>
    <button class="text-slate-500 hover:text-slate-800 text-[13px] font-medium px-4 py-1.5 flex items-center gap-2 transition"><i class="fa-regular fa-circle-check"></i> Đã xuất bán (1)</button>
</div>

<div class="flex gap-6 items-start">
    <!-- CỘT TRÁI: Danh sách Phiếu Xuất TP -->
    <div class="w-1/3 shrink-0 space-y-3">
        <!-- Active Card (Đã duyệt bán) -->
        <div class="bg-white border-2 border-teal-400 rounded-xl p-4 shadow-sm cursor-pointer relative overflow-hidden">
            <div class="flex justify-between items-start mb-2">
                <div class="flex items-center gap-2">
                    <span class="bg-teal-600 text-white text-xs font-bold px-2 py-0.5 rounded">PXTP-001</span>
                    <span class="bg-teal-100 text-teal-700 text-[10px] font-medium px-2 py-0.5 rounded">Đã duyệt bán</span>
                </div>
            </div>
            <div class="text-sm text-slate-700 mb-1 font-medium">Khách: Công ty CP Công Nghệ VinaTech - Đơn hàng #9921</div>
            <div class="text-[11px] text-slate-400">2026-09-12 14:00:00 &bull; Lập bởi: Trần Thị Sản Xuất</div>
        </div>

        <!-- Inactive Card (Chờ duyệt) -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-teal-300 transition">
            <div class="flex justify-between items-start mb-2">
                <div class="flex items-center gap-2">
                    <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded border border-slate-200">PXTP-002</span>
                    <span class="bg-amber-100 text-amber-700 text-[10px] font-medium px-2 py-0.5 rounded">Chờ QL duyệt</span>
                </div>
                <span class="bg-red-50 text-red-600 text-[10px] font-medium px-2 py-0.5 rounded border border-red-100">Cần duyệt</span>
            </div>
            <div class="text-sm text-slate-700 mb-1 font-medium">Khách: Tập đoàn Bất Động Sản Hưng Vượng - Đơn...</div>
            <div class="text-[11px] text-slate-400">2026-09-19 09:30:00 &bull; Lập bởi: Lê Hoàng Kho</div>
        </div>
    </div>

    <!-- CỘT PHẢI: Chi tiết Phiếu Xuất TP -->
    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <!-- Header Chi tiết -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="bg-teal-50 text-teal-700 font-bold text-xs px-2 py-1 rounded border border-teal-100">MÃ PHIẾU XUẤT TP: PXTP-001</span>
                    <span class="text-slate-400 text-xs flex items-center gap-1"><i class="fa-regular fa-calendar"></i> 2026-09-12 14:00:00</span>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-2">
                    <div>
                        <div class="text-[11px] text-slate-400">Nhân viên đề xuất (maNV):</div>
                        <div class="text-sm font-semibold text-slate-800">Trần Thị Sản Xuất</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400">Quản lý phê duyệt (maQL):</div>
                        <div class="text-sm font-semibold text-slate-800">Nguyễn Văn Quản</div>
                    </div>
                </div>
                <div class="text-sm text-slate-600">Khách hàng / Đơn hàng: <span class="font-medium text-slate-800">Công ty CP Công Nghệ VinaTech - Đơn hàng #9921</span></div>
            </div>
            <div>
                <span class="border border-teal-200 bg-teal-50 text-teal-600 text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1 uppercase">
                    <i class="fa-regular fa-circle-check"></i> Đã duyệt xuất bán (Đã trừ kho TP)
                </span>
            </div>
        </div>

        <!-- Bảng Danh Sách Thành Phẩm Xuất Bán -->
        <div class="mb-3">
            <h4 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH THÀNH PHẨM XUẤT BÁN (BẢNG CHITIETPHIEUXUATTP)</h4>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3 border-b border-slate-200">Mã TP</th>
                        <th class="px-4 py-3 border-b border-slate-200">Tên Thành Phẩm</th>
                        <th class="px-4 py-3 border-b border-slate-200">ĐVT</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Số lượng xuất</th>
                        <th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Tình trạng kho</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-800">TP02</td>
                        <td class="px-4 py-3 text-slate-700">Ghế xoay văn phòng</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-teal-600">15</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">30 Cái</td>
                        <td class="px-4 py-3 text-center text-teal-600 font-medium">Đã xuất bán</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>