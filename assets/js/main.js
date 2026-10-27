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

    const changePasswordModal = document.getElementById('changePasswordModal');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const changePasswordMessage = document.getElementById('changePasswordMessage');
    const closeChangePasswordModal = () => {
        if (changePasswordModal) {
            changePasswordModal.classList.add('hidden');
            changePasswordModal.classList.remove('flex');
        }
    };

    if (changePasswordModal && changePasswordForm) {
        const openChangePasswordModal = document.getElementById('openChangePasswordModal');
        const closeChangePasswordButton = document.getElementById('closeChangePasswordModal');
        const cancelChangePasswordButton = document.getElementById('cancelChangePassword');
        if (openChangePasswordModal && changePasswordMessage) {
            openChangePasswordModal.addEventListener('click', () => {
                changePasswordForm.reset();
                changePasswordMessage.className = 'mt-4 hidden rounded-lg px-3 py-2 text-sm';
                changePasswordMessage.textContent = '';
                changePasswordModal.classList.remove('hidden');
                changePasswordModal.classList.add('flex');
                document.getElementById('currentPassword').focus();
            });
        }
        if (closeChangePasswordButton) {
            closeChangePasswordButton.addEventListener('click', closeChangePasswordModal);
        }
        if (cancelChangePasswordButton) {
            cancelChangePasswordButton.addEventListener('click', closeChangePasswordModal);
        }
        changePasswordForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const response = await fetch(changePasswordForm.action, {
                    method: 'POST',
                    body: new FormData(changePasswordForm),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    changePasswordMessage.className = 'mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
                    changePasswordMessage.textContent = result.message || 'Không thể đổi mật khẩu.';
                    return;
                }
                changePasswordMessage.className = 'mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-600';
                changePasswordMessage.textContent = result.message;
                changePasswordForm.reset();
            } catch (error) {
                changePasswordMessage.className = 'mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
                changePasswordMessage.textContent = 'Không thể kết nối máy chủ.';
            }
        });
    }

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

    const roleButtons = document.querySelectorAll('.switch-role');
    roleButtons.forEach(btn => {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            const name = this.getAttribute('data-name');
            alert(`Đã chuyển vai trò thành: ${name}`);
        });
    });
});
