document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('select[name="status"]')?.addEventListener('change', (event) => {
        event.target.form.submit();
    });

    const config = document.getElementById('employeesPageConfig');
    const reloadUrl = 'employees.php' + (config?.dataset.currentQuery ? `?${config.dataset.currentQuery}` : '');
    const addMessage = document.getElementById('addEmployeeMessage');
    const editMessage = document.getElementById('editEmployeeMessage');
    const lockMessage = document.getElementById('lockEmployeeMessage');
    const openModal = (modal) => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };
    const closeModal = (modal) => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };
    const setMessage = (element, text, ok) => {
        element.textContent = text;
        element.className = ok
            ? 'mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-600'
            : 'mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
    };
    const clearMessage = (element) => {
        element.className = 'mt-4 hidden rounded-lg px-3 py-2 text-sm';
        element.textContent = '';
    };
    const submitForm = async (form, messageElement) => {
        clearMessage(messageElement);
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                setMessage(messageElement, result.message || 'Thao tác không thành công.', false);
                return false;
            }
            return true;
        } catch (error) {
            setMessage(messageElement, 'Không thể kết nối máy chủ.', false);
            return false;
        }
    };

    const addModal = document.getElementById('addEmployeeModal');
    const addForm = document.getElementById('addEmployeeForm');
    document.getElementById('openAddEmployeeModal')?.addEventListener('click', () => {
        addForm.reset();
        clearMessage(addMessage);
        openModal(addModal);
        document.getElementById('addVaiTro').focus();
    });
    document.getElementById('closeAddEmployeeModal')?.addEventListener('click', () => closeModal(addModal));
    document.getElementById('cancelAddEmployee')?.addEventListener('click', () => closeModal(addModal));
    addForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (await submitForm(addForm, addMessage)) window.location.href = reloadUrl;
    });

    const editModal = document.getElementById('editEmployeeModal');
    const editForm = document.getElementById('editEmployeeForm');
    const editVaiTro = document.getElementById('editVaiTro');
    const editVaiTroNote = document.getElementById('editVaiTroNote');
    const editTrangThaiLockedOption = document.getElementById('editTrangThaiLockedOption');
    const editTrangThaiNote = document.getElementById('editTrangThaiNote');
    document.querySelectorAll('.edit-employee').forEach((button) => {
        button.addEventListener('click', () => {
            const isSelf = button.dataset.self === '1';
            document.getElementById('editMaNV').value = button.dataset.manv || '';
            document.getElementById('editHoTen').value = button.dataset.hotenn || '';
            document.getElementById('editVaiTroValue').value = button.dataset.vaitro || '1';
            document.getElementById('editTrangThai').value = button.dataset.trangthai || '1';
            document.getElementById('editSdt').value = button.dataset.sdt || '';
            document.getElementById('editDiaChi').value = button.dataset.diachi || '';
            document.getElementById('editMatKhau').value = '';
            editVaiTro.value = isSelf ? config.dataset.currentRole : (button.dataset.vaitro || '1');
            editVaiTroNote.classList.remove('hidden');
            editTrangThaiLockedOption.hidden = isSelf;
            editTrangThaiNote.classList.toggle('hidden', !isSelf);
            clearMessage(editMessage);
            openModal(editModal);
            document.getElementById('editHoTen').focus();
        });
    });
    document.getElementById('closeEditEmployeeModal')?.addEventListener('click', () => closeModal(editModal));
    document.getElementById('cancelEditEmployee')?.addEventListener('click', () => closeModal(editModal));
    editForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (await submitForm(editForm, editMessage)) window.location.href = reloadUrl;
    });

    const lockModal = document.getElementById('lockEmployeeModal');
    const lockForm = document.getElementById('lockEmployeeForm');
    document.querySelectorAll('.lock-employee').forEach((button) => {
        button.addEventListener('click', () => {
            const isLocked = button.dataset.trangthai === '0';
            lockForm.action = isLocked ? '../ajax/unlock_employee.php' : '../ajax/lock_employee.php';
            document.getElementById('lockEmployeeTitle').textContent = isLocked ? 'Mở khóa nhân viên' : 'Khóa nhân viên';
            document.getElementById('lockEmployeeActionText').textContent = isLocked
                ? 'Bạn có chắc chắn muốn mở khóa tài khoản nhân viên '
                : 'Bạn có chắc chắn muốn khóa tài khoản nhân viên ';
            document.getElementById('lockEmployeeSubmit').textContent = isLocked ? 'Mở khóa nhân viên' : 'Khóa nhân viên';
            document.getElementById('lockEmployeeSubmit').className = isLocked
                ? 'rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700'
                : 'rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700';
            document.getElementById('lockMaNV').value = button.dataset.manv || '';
            document.getElementById('lockEmployeeName').textContent =
                `${button.dataset.hotenn || ''} (${button.dataset.manv || ''})`;
            clearMessage(lockMessage);
            openModal(lockModal);
        });
    });
    document.getElementById('closeLockEmployeeModal')?.addEventListener('click', () => closeModal(lockModal));
    document.getElementById('cancelLockEmployee')?.addEventListener('click', () => closeModal(lockModal));
    lockForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (await submitForm(lockForm, lockMessage)) window.location.href = reloadUrl;
    });
});
