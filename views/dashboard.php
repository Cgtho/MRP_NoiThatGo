<?php require_once '../includes/header.php'; ?>

<!-- Banner Xin Chào -->
<div class="bg-[#0f172a] rounded-2xl p-6 mb-6 text-white shadow-lg relative overflow-hidden">
    <div class="relative z-10">
        <div class="flex items-center gap-2 text-slate-400 text-xs mb-2">
            <i class="fa-solid fa-circle-info"></i> Vai trò: Quản lý Kho (vaiTro = 0) | Mã NV: NV01
        </div>
        <h2 class="text-2xl font-bold mb-1">Xin chào, Nguyễn Văn Quản!</h2>
        <p class="text-slate-300 text-sm w-2/3">Hôm nay bạn có phiếu xuất cần phê duyệt và lệnh sản xuất đang hoạt động. Hãy kiểm tra các mục bên dưới.</p>
    </div>
    <button class="absolute top-6 right-6 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg
     font-medium shadow-md transition flex items-center gap-2 z-10" onclick="window.location.href='production_orders.php'">
        <i class="fa-solid fa-plus"></i> Tạo Lệnh Sản Xuất
    </button>
    <!-- Background Decoration -->
    <div class="absolute -right-10 -top-20 opacity-20"><i class="fa-solid fa-cubes text-9xl"></i></div>
</div>

<!-- 4 Thống kê -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <!-- Card 1 -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-blue-300 hover:-translate-y-1"
    onclick="window.location.href='inventory.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">NGUYÊN VẬT LIỆU</div>
            <i class="fa-solid fa-cubes text-blue-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800">8 <span class="text-sm font-normal text-slate-500">chủng loại</span></div>
            <div class="text-xs text-slate-400 mt-1">3,445 tổng số lượng tồn kho</div>
        </div>
    </div>
    <!-- Card 2 -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-green-300 hover:-translate-y-1"
    onclick="window.location.href='inventory.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">THÀNH PHẨM LƯU KHO</div>
            <i class="fa-solid fa-box text-emerald-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800">87 <span class="text-sm font-normal text-slate-500">sản phẩm</span></div>
            <div class="text-xs text-slate-400 mt-1">3 danh mục thành phẩm chính</div>
        </div>
    </div>
    <!-- Card 3 (Nổi bật) -->
    <div class="bg-amber-50 p-5 rounded-xl border border-amber-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-yellow-300 hover:-translate-y-1"
    onclick="window.location.href='export_materials.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-amber-700">XUẤT NVL CHỜ DUYỆT</div>
            <i class="fa-regular fa-clock text-amber-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-amber-700">1 <span class="text-sm font-normal">phiếu yêu cầu</span></div>
            <div class="text-xs text-amber-600 mt-1">Nhân viên đang đợi duyệt cấp vật tư</div>
        </div>
    </div>
    <!-- Card 4 -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between
    cursor-pointer transition-all duration-200 hover:shadow-md hover:border-purple-300 hover:-translate-y-1"
    onclick="window.location.href='production_orders.php'">
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs font-semibold text-slate-500">LỆNH SẢN XUẤT</div>
            <i class="fa-regular fa-clipboard text-purple-500"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800">2 <span class="text-sm font-normal text-slate-500">đang chạy</span></div>
            <div class="text-xs text-slate-400 mt-1">Đang sản xuất hoặc chờ kiểm tra định mức</div>
        </div>
    </div>
</div>

<!-- Workflow Box -->
<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-bold text-slate-800">Sơ Đồ Quy Trình Phối Hợp Kho & Sản Xuất (Workflow)</h3>
            <p class="text-xs text-slate-500">Liên kết chặt chẽ giữa Quản lý kho (định mức, duyệt lệnh) và Nhân viên sản xuất (kiểm tra thiếu đủ, xuất kho, làm xong)</p>
        </div>
        <a href="#" class="text-blue-600 text-sm font-medium hover:underline">Xem liên kết các bảng CSDL &rarr;</a>
    </div>
    
    <div class="grid grid-cols-4 gap-4">
        <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
            <div class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold mb-3">1</div>
            <h4 class="font-semibold text-blue-700 text-sm mb-1">QUẢN LÝ KHO</h4>
            <p class="font-medium text-slate-800 text-sm mb-2">Định mức BOM & Tạo lệnh SX</p>
            <p class="text-xs text-slate-500">Khai báo 1 sản phẩm cần bao nhiêu NVL (Bảng CHITIETTHANHPHAM) và phát lệnh YEUCAU (số lượng cần làm).</p>
        </div>
        <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
            <div class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-bold mb-3">2</div>
            <h4 class="font-semibold text-amber-700 text-sm mb-1">NHÂN VIÊN KHO</h4>
            <p class="font-medium text-slate-800 text-sm mb-2">Kiểm tra NVL & Xin xuất</p>
            <p class="text-xs text-slate-500">Hệ thống nhân số lượng x BOM, so sánh kho. Nếu thiếu báo đỏ & tạo PHIEUXUATNVL gửi Quản lý.</p>
        </div>
        <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
            <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold mb-3">3</div>
            <h4 class="font-semibold text-emerald-700 text-sm mb-1">QUẢN LÝ KHO</h4>
            <p class="font-medium text-slate-800 text-sm mb-2">Phê duyệt phiếu xuất NVL</p>
            <p class="text-xs text-slate-500">Quản lý duyệt (trangThai=1). Hệ thống tự động trừ số lượng NVL trong kho NGUYENVATLIEU.</p>
        </div>
        <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
            <div class="w-6 h-6 rounded-full bg-purple-500 text-white flex items-center justify-center text-xs font-bold mb-3">4</div>
            <h4 class="font-semibold text-purple-700 text-sm mb-1">NHÂN VIÊN & QUẢN LÝ</h4>
            <p class="font-medium text-slate-800 text-sm mb-2">Hoàn thành & Xuất bán TP</p>
            <p class="text-xs text-slate-500">Nhân viên bấm Hoàn thành &rarr; tăng kho THANHPHAM. Lập PHIEUXUATTP để bán, Quản lý duyệt trừ kho.</p>
        </div>
    </div>
</div>

<!-- Khối Danh sách Phiếu chờ duyệt & Lệnh sản xuất -->
<div class="grid grid-cols-2 gap-6 mb-6">
    
    <!-- CỘT TRÁI: Phiếu Chờ Phê Duyệt -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col gap-4">
        <div class="flex justify-between items-center mb-1">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-regular fa-clock text-amber-500"></i> Phiếu Chờ Phê Duyệt (2)
            </h3>
            <span class="text-sm text-slate-400">Cần bạn phê duyệt</span>
        </div>

        <!-- Card 1: PXNVL -->
        <div class="border border-amber-200 rounded-lg p-4 flex justify-between items-center bg-white shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="font-bold text-amber-700">PXNVL-002</span>
                    <span class="bg-amber-100 text-amber-800 text-[11px] font-medium px-2 py-0.5 rounded">Xuất NVL làm hàng</span>
                </div>
                <div class="text-sm text-slate-700 mb-1">Mục đích: Xin cấp vật tư làm 50 bàn chữ U (YC-2026-001)</div>
                <div class="text-[11px] text-slate-400">Ngày tạo: 2026-09-16 11:20:00 &bull; Người tạo: NV02</div>
            </div>
            <button class="bg-[#e27a13] hover:bg-[#c96a0e] text-white text-sm font-medium px-4 py-2 rounded-lg transition shrink-0 shadow-sm"
            onclick="window.location.href='export_materials.php'">
                Duyệt ngay
            </button>
        </div>

        <!-- Card 2: PXTP -->
        <div class="border border-blue-200 rounded-lg p-4 flex justify-between items-center bg-white shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="font-bold text-blue-700">PXTP-002</span>
                    <span class="bg-blue-100 text-blue-800 text-[11px] font-medium px-2 py-0.5 rounded">Xuất bán thành phẩm</span>
                </div>
                <div class="text-sm text-slate-700 mb-1">Khách hàng: Tập đoàn Bất Động Sản Hưng Vượng - Đơn #9945</div>
                <div class="text-[11px] text-slate-400">Ngày tạo: 2026-09-19 09:30:00 &bull; Người tạo: NV03</div>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shrink-0 shadow-sm"
            onclick="window.location.href='export_products.php'">
                Duyệt ngay
            </button>
        </div>
    </div>

    <!-- CỘT PHẢI: Lệnh Sản Xuất Hiện Có -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col gap-4">
        <div class="flex justify-between items-center mb-1">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-regular fa-clipboard text-indigo-500"></i> Lệnh Sản Xuất Hiện Có (YEUCAU)
            </h3>
            <a href="production_orders.php" class="text-sm text-indigo-600 hover:underline">Xem tất cả &rarr;</a>
        </div>

        <!-- Card Lệnh 1 -->
        <div class="border border-slate-200 rounded-lg p-4 flex justify-between items-center bg-white shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="font-bold text-slate-800">YC-2026-001</span>
                    <span class="bg-indigo-100 text-indigo-700 text-[11px] font-medium px-2 py-0.5 rounded">Đang sản xuất</span>
                </div>
                <div class="text-sm text-slate-700 mb-1">Sản xuất phục vụ hợp đồng văn phòng FPT Software</div>
                <div class="text-[11px] text-slate-400">Hạn chót: 2026-09-25 &bull; Quản lý yêu cầu: NV01</div>
            </div>
            <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition shrink-0 border border-slate-200"
            onclick="window.location.href='production_orders.php'">
                Kiểm tra NVL
            </button>
        </div>

        <!-- Card Lệnh 2 -->
        <div class="border border-slate-200 rounded-lg p-4 flex justify-between items-center bg-white shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="font-bold text-slate-800">YC-2026-002</span>
                    <span class="bg-amber-100 text-amber-700 text-[11px] font-medium px-2 py-0.5 rounded">Chờ xử lý</span>
                </div>
                <div class="text-sm text-slate-700 mb-1">Đơn đặt hàng showroom Tân Bình</div>
                <div class="text-[11px] text-slate-400">Hạn chót: 2026-09-30 &bull; Quản lý yêu cầu: NV04</div>
            </div>
            <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition shrink-0 border border-slate-200"
            onclick="window.location.href='production_orders.php'">
                Kiểm tra NVL
            </button>
        </div>

        <!-- Card Lệnh 3 -->
        <div class="border border-slate-200 rounded-lg p-4 flex justify-between items-center bg-white shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="font-bold text-slate-800">YC-2026-003</span>
                    <span class="bg-emerald-100 text-emerald-700 text-[11px] font-medium px-2 py-0.5 rounded">Đã hoàn thành</span>
                </div>
                <div class="text-sm text-slate-700 mb-1">Lô ghế xoay dự trữ đợt 1</div>
                <div class="text-[11px] text-slate-400">Hạn chót: 2026-09-12 &bull; Quản lý yêu cầu: NV01</div>
            </div>
            <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition shrink-0 border border-slate-200"
            onclick="window.location.href='production_orders.php'">
                Kiểm tra NVL
            </button>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>