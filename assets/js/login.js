document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const employeeId = document.getElementById('employeeId');
    const password = document.getElementById('password');
    const employeeError = document.getElementById('employeeError');
    const passwordError = document.getElementById('passwordError');
    const loginMessage = document.getElementById('loginMessage');
    const submitButton = form.querySelector('button[type="submit"]');
    const togglePassword = document.getElementById('togglePassword');
    const rememberAccount = document.getElementById('rememberAccount');
    const rememberedEmployeeId = localStorage.getItem('mrp_employee_id');

    if (rememberedEmployeeId) {
        employeeId.value = rememberedEmployeeId;
        rememberAccount.checked = true;
    }

    rememberAccount.addEventListener('change', () => {
        if (!rememberAccount.checked) {
            localStorage.removeItem('mrp_employee_id');
        }
    });

    const showMessage = (message) => {
        loginMessage.textContent = message;
        loginMessage.classList.remove('hidden');
    };

    togglePassword.addEventListener('click', () => {
        const shouldShow = password.type === 'password';
        password.type = shouldShow ? 'text' : 'password';
        togglePassword.setAttribute('aria-label', shouldShow ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        togglePassword.innerHTML = `<i class="fa-regular fa-eye${shouldShow ? '-slash' : ''}"></i>`;
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!rememberAccount.checked) {
            localStorage.removeItem('mrp_employee_id');
        }

        const validEmployee = employeeId.value.trim() !== '';
        const validPassword = password.value !== '';

        employeeError.classList.toggle('hidden', validEmployee);
        passwordError.classList.toggle('hidden', validPassword);
        loginMessage.classList.add('hidden');

        if (!validEmployee || !validPassword) {
            return;
        }

        submitButton.disabled = true;
        submitButton.classList.add('cursor-wait', 'opacity-70');
        submitButton.querySelector('i').className = 'fa-solid fa-spinner fa-spin text-xs';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                showMessage(result.message || 'Đăng nhập không thành công.');
                return;
            }

            if (rememberAccount.checked) {
                localStorage.setItem('mrp_employee_id', employeeId.value.trim().toUpperCase());
            } else {
                localStorage.removeItem('mrp_employee_id');
            }

            window.location.href = result.redirect;
        } catch (error) {
            showMessage('Không thể kết nối máy chủ. Vui lòng thử lại sau.');
        } finally {
            submitButton.disabled = false;
            submitButton.classList.remove('cursor-wait', 'opacity-70');
            submitButton.querySelector('i').className = 'fa-solid fa-arrow-right text-xs';
        }
    });
});
