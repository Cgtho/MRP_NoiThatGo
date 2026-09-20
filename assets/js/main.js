document.addEventListener("DOMContentLoaded", () => {
    // 1. Logic chuyển đổi tài khoản (Demo nhanh bằng cách gọi ajax set session)
    const roleButtons = document.querySelectorAll('.switch-role');
    roleButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nv = this.getAttribute('data-nv');
            const name = this.getAttribute('data-name');
            const role = this.getAttribute('data-role');
            
            // Trong thực tế sẽ call API auth, ở đây ta fake refresh
            alert(`Đã chuyển vai trò thành: ${name}`);
            // TODO: Viết AJAX gọi file PHP set lại $_SESSION và window.location.reload()
        });
    });

    // 2. Fake Fetch số lượng phiếu chờ duyệt
    // Trong thực tế: fetch('ajax/ajax_get_pending.php')...
    document.getElementById('badge-pending-export').innerText = "3"; 
});