document.addEventListener("DOMContentLoaded", () => {
    const logoutForm = document.getElementById('logoutForm');
    if (logoutForm) {
        logoutForm.addEventListener('submit', () => {
            localStorage.removeItem('mrp_employee_id');
        });
    }

    const notificationButton = document.getElementById('notificationButton');
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');

    const loadNotifications = async () => {
        if (!notificationButton || !notificationList) {
            return;
        }

        try {
            const response = await fetch(notificationButton.dataset.notificationsUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Không thể tải thông báo.');
            }

            notificationList.replaceChildren();
            if (notificationBadge) {
                notificationBadge.textContent = data.count > 99 ? '99+' : String(data.count);
                notificationBadge.classList.toggle('hidden', data.count === 0);
            }
            if (notificationCount) {
                notificationCount.textContent = data.count > 0 ? `${data.count} đang chờ` : 'Không có mới';
            }

            if (data.notifications.length === 0) {
                const emptyMessage = document.createElement('p');
                emptyMessage.className = 'px-4 py-5 text-center text-sm text-slate-500';
                emptyMessage.textContent = 'Không có thông báo mới.';
                notificationList.append(emptyMessage);
                return;
            }

            data.notifications.forEach((notification) => {
                const link = document.createElement('a');
                link.href = notification.href;
                link.className = 'flex gap-3 px-4 py-3 transition hover:bg-slate-50';

                const icon = document.createElement('i');
                icon.className = `fa-solid ${notification.icon} ${notification.color} mt-0.5`;
                const content = document.createElement('span');
                const title = document.createElement('strong');
                title.className = 'block text-sm';
                title.textContent = `${notification.title} (${notification.code})`;
                const message = document.createElement('small');
                message.className = 'text-xs text-slate-500';
                message.textContent = notification.message;
                content.append(title, message);
                link.append(icon, content);
                notificationList.append(link);
            });
        } catch (error) {
            if (notificationCount) {
                notificationCount.textContent = 'Lỗi tải dữ liệu';
            }
            notificationList.replaceChildren();
            const errorMessage = document.createElement('p');
            errorMessage.className = 'px-4 py-5 text-center text-sm text-rose-600';
            errorMessage.textContent = 'Không thể tải thông báo.';
            notificationList.append(errorMessage);
        }
    };

    if (notificationButton && notificationPanel) {
        loadNotifications();
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
        btn.addEventListener('click', function (e) {
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