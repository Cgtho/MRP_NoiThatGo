<?php require_once '../includes/header.php'; ?>

<!-- Header màn hình -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg text-xl"><i class="fa-solid fa-box-open"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">Nhập Kho Nguyên Vật Liệu (PHIEUNHAPKHO & CHITIETPHIEUNHAP)</h2>
                <p class="text-xs text-slate-500">Quản lý lập phiếu nhập khi mua thêm gỗ, ốc, sắt... Hệ thống tự động cộng dồn số lượng vào bảng NGUYENVATLIEU.</p>
            </div>
        </div>
    </div>
    <button id="btn-open-import" type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Tạo Phiếu Nhập Kho Mới
    </button>
</div>

<div class="flex gap-6 items-start">
    <!-- CỘT TRÁI: Danh sách Phiếu Nhập -->
    <div class="w-1/3 shrink-0 flex flex-col gap-4">
        <!-- Thanh tìm kiếm -->
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-sm"></i>
            <input type="text" placeholder="Tìm theo mã phiếu, ghi chú..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 shadow-sm">
        </div>
        
        <div class="space-y-3">
            <!-- Active Card -->
            <div class="bg-white border-2 border-emerald-500 rounded-xl p-4 shadow-sm cursor-pointer relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <span class="bg-emerald-600 text-white text-xs font-bold px-2 py-0.5 rounded">PN-20260901-01</span>
                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-medium px-2 py-0.5 rounded">Đã nhập kho</span>
                    </div>
                    <div class="text-right leading-tight">
                        <div class="font-bold text-slate-800">+500</div>
                        <div class="text-[10px] text-slate-400">2 mặt hàng</div>
                    </div>
                </div>
                <div class="text-sm text-slate-700 mb-1 truncate">Nhập lô mặt bàn và chân sắt từ NCC Minh Phát</div>
                <div class="text-[10px] text-slate-400">2026-09-01 08:30:00 &bull; Lập bởi: Nguyễn Văn Quản</div>
            </div>

            <!-- Inactive Card 1 -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-emerald-300 transition">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <span class="bg-slate-200 text-slate-700 text-xs font-bold px-2 py-0.5 rounded">PN-20260910-02</span>
                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-medium px-2 py-0.5 rounded">Đã nhập kho</span>
                    </div>
                    <div class="text-right leading-tight">
                        <div class="font-bold text-slate-800">+2,050</div>
                        <div class="text-[10px] text-slate-400">2 mặt hàng</div>
                    </div>
                </div>
                <div class="text-sm text-slate-700 mb-1 truncate">Bổ sung ốc vít và phụ kiện ghế xoay</div>
                <div class="text-[10px] text-slate-400">2026-09-10 14:15:00 &bull; Lập bởi: Nguyễn Văn Quản</div>
            </div>
            
            <!-- Inactive Card 2 -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm cursor-pointer hover:border-emerald-300 transition">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <span class="bg-slate-200 text-slate-700 text-xs font-bold px-2 py-0.5 rounded">PN-20260918-03</span>
                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-medium px-2 py-0.5 rounded">Đã nhập kho</span>
                    </div>
                    <div class="text-right leading-tight">
                        <div class="font-bold text-slate-800">+180</div>
                        <div class="text-[10px] text-slate-400">2 mặt hàng</div>
                    </div>
                </div>
                <div class="text-sm text-slate-700 mb-1 truncate">Nhập sơn PU và phụ kiện thanh giằng</div>
                <div class="text-[10px] text-slate-400">2026-09-18 09:00:00 &bull; Lập bởi: Phạm Trọng Duyệt</div>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: Chi tiết Phiếu Nhập -->
    <div class="w-2/3 bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <!-- Header Chi tiết -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-emerald-100 text-emerald-800 font-bold text-sm px-2 py-1 rounded">MÃ PHIẾU: PN-20260901-01</span>
                    <span class="text-slate-500 text-xs flex items-center gap-1"><i class="fa-regular fa-calendar"></i> 2026-09-01 08:30:00</span>
                </div>
                <div class="text-sm text-slate-600 mb-1"><i class="fa-regular fa-user mr-1 text-slate-400"></i> Nhân viên lập phiếu (maNV): <strong class="text-slate-800">Nguyễn Văn Quản</strong></div>
                <div class="text-sm text-slate-600">Ghi chú phiếu: Nhập lô mặt bàn và chân sắt từ NCC Minh Phát</div>
            </div>
            <div>
                <span class="border border-emerald-200 bg-emerald-50 text-emerald-600 text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1">
                    <i class="fa-regular fa-circle-check"></i> Đã cộng dồn vào kho NVL
                </span>
            </div>
        </div>

        <!-- Bảng Chi tiết Vật tư Nhập -->
        <div class="mb-2">
            <h4 class="font-bold text-slate-700 text-xs uppercase">DANH SÁCH VẬT TƯ NHẬP (BẢNG CHITIETPHIEUNHAP)</h4>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3 border-b border-slate-200">Mã NVL</th>
                        <th class="px-4 py-3 border-b border-slate-200">Tên Nguyên Vật Liệu</th>
                        <th class="px-4 py-3 border-b border-slate-200">Đơn vị tính</th>
                        <th class="px-4 py-3 text-center border-b border-slate-200">Số lượng nhập</th>
                        <th class="px-4 py-3 text-right border-b border-slate-200">Tồn kho hiện tại</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-700">NVL01</td>
                        <td class="px-4 py-3 text-slate-700">Mặt bàn gỗ sồi (120x60cm)</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-emerald-600">+100</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">145 Cái</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-bold text-slate-700">NVL02</td>
                        <td class="px-4 py-3 text-slate-700">Chân bàn sắt sơn tĩnh điện</td>
                        <td class="px-4 py-3 text-slate-500">Cái</td>
                        <td class="px-4 py-3 text-center font-bold text-emerald-600">+400</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">380 Cái</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal lập phiếu nhập kho mới -->
<div id="modal-add-import" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center px-4">
    <div class="bg-white rounded-xl w-full max-w-[540px] shadow-2xl flex flex-col" role="dialog" aria-modal="true" aria-labelledby="modal-add-import-title">
        <div class="flex justify-between items-center px-6 py-5 border-b border-slate-100">
            <h3 id="modal-add-import-title" class="font-bold text-slate-800 text-lg">Lập Phiếu Nhập Kho Nguyên Vật Liệu (PHIEUNHAPKHO)</h3>
            <button type="button" class="close-import-modal text-slate-400 hover:text-slate-600" aria-label="Đóng">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="import-form" class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="import-code" class="block text-xs font-medium text-slate-600 mb-1">Mã phiếu nhập (maPN)</label>
                    <input id="import-code" name="maPN" type="text" readonly class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-600 bg-slate-50 focus:outline-none">
                </div>
                <div>
                    <label for="import-date" class="block text-xs font-medium text-slate-600 mb-1">Ngày nhập (ngayNhap)</label>
                    <input id="import-date" name="ngayNhap" type="text" readonly class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-600 bg-slate-50 focus:outline-none">
                </div>
            </div>

            <div>
                <label for="import-note" class="block text-xs font-medium text-slate-600 mb-1">Ghi chú nguồn hàng / Nhà cung cấp</label>
                <input id="import-note" name="ghiChu" type="text" placeholder="VD: Mua thêm từ NCC Gỗ Việt Tiến" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <div class="flex justify-between items-end mb-1">
                    <label for="import-material" class="block text-xs font-medium text-slate-600">Danh sách vật tư nhập kho (CHITIETPHIEUNHAP)</label>
                    <button type="button" class="text-blue-600 hover:text-blue-700 text-xs font-medium">+ Thêm mặt hàng</button>
                </div>
                <div class="flex gap-2">
                    <select id="import-material" name="maNVL" class="flex-1 min-w-0 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-slate-700">
                        <option value="NVL01">Mặt bàn gỗ sồi (120x60cm) (NVL01) - Hiện có: 95</option>
                        <option value="NVL02">Chân bàn sắt sơn tĩnh điện (NVL02) - Hiện có: 380</option>
                        <option value="NVL03">Bộ ốc vít ren thép M6 (NVL03) - Hiện có: 2,400</option>
                    </select>
                    <input name="soLuong" type="number" min="1" value="1" aria-label="Số lượng nhập" class="w-24 border border-slate-300 rounded-lg px-3 py-2 text-sm text-center font-semibold text-emerald-600 focus:outline-none focus:border-blue-500">
                </div>
            </div>
        </form>

        <div class="px-6 py-4 border-t border-slate-100 flex justify-end items-center gap-5 bg-slate-50 rounded-b-xl">
            <button type="button" class="close-import-modal text-sm font-medium text-slate-500 hover:text-slate-800 transition">Hủy</button>
            <button type="button" form="import-form" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium shadow-sm transition">Lưu &amp; Cộng Dồn Vào Kho</button>
        </div>
    </div>
</div>

<script src="../assets/js/import_materials.js"></script>

<?php require_once '../includes/footer.php'; ?>