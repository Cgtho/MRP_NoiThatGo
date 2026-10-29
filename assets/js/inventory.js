document.addEventListener('DOMContentLoaded', () => {
    const inventorySearchInput = document.querySelector('#inventorySearch input[name="search"]');
    const masterStatusFilter = document.getElementById('masterStatusFilter');
    const inventoryRows = [...document.querySelectorAll('[data-inventory-row]')];
    const inventoryEmptyRow = document.getElementById('inventoryEmptyRow');
    const inventoryFilterEmptyRow = document.getElementById('inventoryFilterEmptyRow');

    inventorySearchInput?.addEventListener('input', () => {
        const searchValue = inventorySearchInput.value.trim().toLocaleLowerCase();
        let visibleRows = 0;

        inventoryRows.forEach((row) => {
            const matches = row.textContent.toLocaleLowerCase().includes(searchValue);
            row.classList.toggle('hidden', !matches);
            visibleRows += matches ? 1 : 0;
        });

        inventoryEmptyRow?.classList.toggle('hidden', visibleRows > 0 || searchValue === '');
        inventoryFilterEmptyRow?.classList.toggle('hidden', visibleRows > 0 || searchValue === '');
    });

    masterStatusFilter?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('status', masterStatusFilter.value);
        url.searchParams.delete('search');
        window.location.href = url.toString();
    });

    const addMaterialModal = document.getElementById('addMaterialModal');
    const addMaterialForm = document.getElementById('addMaterialForm');
    const addMaterialMessage = document.getElementById('addMaterialMessage');
    const closeModal = (modal) => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };
    const showModal = (modal) => {
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    };

    document.getElementById('openAddMaterialModal')?.addEventListener('click', () => {
        showModal(addMaterialModal);
        document.getElementById('materialName')?.focus();
    });
    document.getElementById('closeAddMaterialModal')?.addEventListener('click', () => closeModal(addMaterialModal));
    document.getElementById('cancelAddMaterial')?.addEventListener('click', () => closeModal(addMaterialModal));

    addMaterialForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        addMaterialMessage.className = 'mt-3 hidden rounded-lg px-3 py-2 text-sm';
        try {
            const response = await fetch(addMaterialForm.action, {
                method: 'POST',
                body: new FormData(addMaterialForm),
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                addMaterialMessage.textContent = result.message || 'Không thể thêm nguyên vật liệu.';
                addMaterialMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
                return;
            }
            window.location.href = 'inventory.php?view=warehouse_materials';
        } catch (error) {
            addMaterialMessage.textContent = 'Không thể kết nối máy chủ.';
            addMaterialMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
        }
    });

    const addUnitModal = document.getElementById('addUnitModal');
    const addUnitForm = document.getElementById('addUnitForm');
    const addUnitMessage = document.getElementById('addUnitMessage');

    document.getElementById('openAddUnitModal')?.addEventListener('click', () => {
        showModal(addUnitModal);
        document.getElementById('unitName')?.focus();
    });
    document.getElementById('closeAddUnitModal')?.addEventListener('click', () => closeModal(addUnitModal));
    document.getElementById('cancelAddUnit')?.addEventListener('click', () => closeModal(addUnitModal));

    addUnitForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        addUnitMessage.className = 'mt-3 hidden rounded-lg px-3 py-2 text-sm';
        try {
            const response = await fetch(addUnitForm.action, {
                method: 'POST',
                body: new FormData(addUnitForm),
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                addUnitMessage.textContent = result.message || 'Không thể thêm đơn vị tính.';
                addUnitMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
                return;
            }
            window.location.href = 'inventory.php?view=units';
        } catch (error) {
            addUnitMessage.textContent = 'Không thể kết nối máy chủ.';
            addUnitMessage.className = 'mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
        }
    });

    const confirmationModal = document.getElementById('masterActionConfirmModal');
    const confirmationMessage = document.getElementById('masterActionConfirmMessage');
    let resolveConfirmation = null;
    const closeConfirmation = (confirmed) => {
        closeModal(confirmationModal);
        resolveConfirmation?.(confirmed);
        resolveConfirmation = null;
    };
    const askConfirmation = (message) => new Promise((resolve) => {
        if (!confirmationModal || !confirmationMessage) {
            resolve(false);
            return;
        }
        confirmationMessage.textContent = message;
        resolveConfirmation = resolve;
        showModal(confirmationModal);
        document.getElementById('acceptMasterActionConfirm')?.focus();
    });

    document.getElementById('acceptMasterActionConfirm')?.addEventListener('click', () => closeConfirmation(true));
    document.getElementById('closeMasterActionConfirm')?.addEventListener('click', () => closeConfirmation(false));
    document.getElementById('cancelMasterActionConfirm')?.addEventListener('click', () => closeConfirmation(false));
    confirmationModal?.addEventListener('click', (event) => {
        if (event.target === confirmationModal) closeConfirmation(false);
    });

    document.querySelectorAll('[data-master-action]').forEach((button) => {
        button.addEventListener('click', async () => {
            const action = button.dataset.masterAction;
            const entity = button.dataset.masterEntity;
            const code = button.dataset.masterCode;
            const confirmationMessageText = action === 'delete'
                ? `Xóa vĩnh viễn ${code}? Thao tác này không thể hoàn tác.`
                : `${action === 'hide' ? 'Ẩn' : 'Hiển thị lại'} ${code}?`;

            if (!await askConfirmation(confirmationMessageText)) return;

            button.disabled = true;
            try {
                const body = new FormData();
                body.append('csrf_token', confirmationModal.dataset.csrfToken);
                body.append('entity', entity);
                body.append('code', code);
                body.append('action', action);
                const response = await fetch('../ajax/manage_master_data.php', {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Không thể cập nhật dữ liệu.');
                }
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Không thể kết nối máy chủ.');
                button.disabled = false;
            }
        });
    });
});
