<?php
session_start();

if (isset($_SESSION['current_user'])) {
    header('Location: ./views/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Đăng nhập hệ thống quản lý kho và sản xuất nội thất gỗ">
    <title>Đăng nhập | KHO & SẢN XUẤT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #16252b;
            --cream: #f4f1e9;
            --copper: #c66a3d;
            --sage: #738f80;
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--ink); }
        .display-font { font-family: 'Space Grotesk', sans-serif; }
        .grain { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.13'/%3E%3C/svg%3E"); }
        .wood-lines { background-image: repeating-linear-gradient(112deg, transparent 0, transparent 31px, rgba(255,255,255,.055) 32px, transparent 33px, transparent 70px); }
        .field:focus-within { border-color: var(--copper); box-shadow: 0 0 0 4px rgba(198,106,61,.12); }
        .login-panel { animation: rise .7s cubic-bezier(.22, 1, .36, 1) both; }
        .art-panel { animation: reveal .9s cubic-bezier(.22, 1, .36, 1) both; }
        @keyframes rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes reveal { from { opacity: 0; transform: scale(.98); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="min-h-screen">
    <main class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
        <section class="art-panel relative hidden overflow-hidden bg-[#19343a] p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
            <div class="grain wood-lines absolute inset-0 opacity-50"></div>
            <div class="absolute -right-20 top-24 h-80 w-80 rounded-full border border-white/10"></div>
            <div class="absolute -right-4 top-40 h-52 w-52 rounded-full border border-[#d58a61]/30"></div>

            <div class="relative z-10 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#d58a61] text-xl text-[#19343a] shadow-lg shadow-black/10">
                    <i class="fa-solid fa-cubes"></i>
                </span>
                <div>
                    <div class="display-font text-lg font-bold tracking-tight">KHO & SẢN XUẤT</div>
                    <div class="text-xs tracking-[.18em] text-white/50">NỘI THẤT GỖ</div>
                </div>
            </div>

            <div class="relative z-10 max-w-xl py-12">
                <p class="mb-5 text-xs font-bold tracking-[.24em] text-[#d58a61]">WORKSHOP CONTROL / 01</p>
                <h1 class="display-font max-w-lg text-5xl font-bold leading-[1.05] tracking-tight xl:text-6xl">Mọi quy trình, cùng một nhịp vận hành.</h1>
                <p class="mt-7 max-w-md text-base leading-7 text-white/65">Theo dõi nguyên vật liệu, định mức BOM và lệnh sản xuất trong một không gian làm việc thống nhất.</p>
            </div>

            <div class="relative z-10 flex items-end justify-between border-t border-white/15 pt-5 text-xs text-white/45">
                <span>MRP / WOODWORKING OPERATIONS</span>
                <span>v1.0.0</span>
            </div>
        </section>

        <section class="login-panel flex items-center justify-center px-6 py-10 sm:px-10 lg:px-16 xl:px-24">
            <div class="w-full max-w-[440px]">
                <div class="mb-10 lg:hidden">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#19343a] text-[#d58a61]"><i class="fa-solid fa-cubes"></i></span>
                        <div class="display-font text-lg font-bold">KHO & SẢN XUẤT</div>
                    </div>
                </div>

                <div class="mb-9">
                    <h2 class="display-font text-4xl font-bold tracking-tight text-[#16252b]">Đăng nhập vào hệ thống</h2>
                    <p class="mt-3 text-sm leading-6 text-[#607075]">Nhập thông tin tài khoản để tiếp tục phiên làm việc của bạn.</p>
                </div>

                <form id="loginForm" class="space-y-5" action="ajax/auth_login.php" method="post" novalidate>
                    <div>
                        <label for="employeeId" class="mb-2 block text-sm font-semibold text-[#263940]">Mã nhân viên</label>
                        <div class="field flex items-center gap-3 rounded-xl border border-[#d9d7ce] bg-white px-4 transition-all">
                            <i class="fa-regular fa-id-card text-[#879397]"></i>
                            <input id="employeeId" name="employeeId" type="text" autocomplete="username" class="h-12 min-w-0 flex-1 bg-transparent text-sm text-[#16252b] outline-none placeholder:text-[#a6abad]" required>
                        </div>
                        <p id="employeeError" class="mt-1.5 hidden text-xs text-red-600">Vui lòng nhập mã nhân viên.</p>
                    </div>

                    <div>
                        <div class="mb-2">
                            <label for="password" class="block text-sm font-semibold text-[#263940]">Mật khẩu</label>
                        </div>
                        <div class="field flex items-center gap-3 rounded-xl border border-[#d9d7ce] bg-white px-4 transition-all">
                            <i class="fa-solid fa-lock text-[#879397]"></i>
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu" class="h-12 min-w-0 flex-1 bg-transparent text-sm text-[#16252b] outline-none placeholder:text-[#a6abad]" required>
                            <button id="togglePassword" type="button" aria-label="Hiện mật khẩu" class="p-1 text-[#879397] transition hover:text-[#19343a]"><i class="fa-regular fa-eye"></i></button>
                        </div>
                        <p id="passwordError" class="mt-1.5 hidden text-xs text-red-600">Vui lòng nhập mật khẩu.</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-[#607075]">
                        <input id="rememberAccount" name="rememberAccount" type="checkbox" class="h-4 w-4 rounded border-[#c7c9c2] accent-[#c66a3d]">
                        Ghi nhớ đăng nhập trên thiết bị này
                    </label>

                    <button type="submit" class="group flex h-13 w-full items-center justify-center gap-3 rounded-xl bg-[#19343a] px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#19343a]/15 transition hover:-translate-y-0.5 hover:bg-[#24474e] focus:outline-none focus:ring-4 focus:ring-[#19343a]/15">
                        Đăng nhập
                        <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                    </button>
                    <p id="loginMessage" class="hidden rounded-lg bg-[#fff3e9] px-4 py-3 text-center text-xs text-[#a9522b]" role="alert"></p>
                </form>

                <div class="mt-12 border-t border-[#dddcd4] pt-5 text-center text-xs text-[#8a9496]">
                    <span><i class="fa-solid fa-shield-halved mr-1 text-[#738f80]"></i> Kết nối nội bộ bảo mật</span>
                    <span class="mx-2 text-[#c7c7c0]">•</span>
                    <span>Hỗ trợ IT: 1900 0001</span>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/js/login.js" defer></script>
</body>
</html>