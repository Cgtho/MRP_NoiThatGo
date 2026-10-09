document.addEventListener("DOMContentLoaded", () => {
    const logoutForm = document.getElementById('logoutForm');
    if (logoutForm) {
        logoutForm.addEventListener('submit', () => {
            localStorage.removeItem('mrp_employee_id');
        });
    }

    const notificationButton = document.getElementById('notificationButton');
    const notificationPanel = document.getElementById('notificationPanel');
    if (notificationButton && notificationPanel) {
        notificationButton.addEventListener('click', () => {
            const isHidden = notificationPanel.classList.toggle('hidden');
            notificationButton.setAttribute('aria-expanded', String(!isHidden));
        });

        document.addEventListener('click', (event) => {
            if (!notificationPanel.contains(event.target) && !notificationButton.contains(event.target)) {
                notificationPanel.classList.add('hidden');
                notificationButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

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
    const pendingExportBadge = document.getElementById('badge-pending-export');
    if (pendingExportBadge) {
        pendingExportBadge.innerText = "3";
    }
});