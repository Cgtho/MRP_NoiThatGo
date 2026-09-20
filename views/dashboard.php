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
    <button class="absolute top-6 right-6 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-medium shadow-md transition flex items-center gap-2 z-10">
        <i class="fa-solid fa-plus"></i> Tạo Lệnh Sản Xuất
    </button>
    <!-- Background Decoration -->
    <div class="absolute -right-10 -top-20 opacity-20"><i class="fa-solid fa-cubes text-9xl"></i></div>
</div>

<!-- 4 Thống kê -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <!-- Card 1 -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
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
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
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
    <div class="bg-amber-50 p-5 rounded-xl border border-amber-200 shadow-sm flex flex-col justify-between">
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
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
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

<?php require_once '../includes/footer.php'; ?>