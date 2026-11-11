document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-add-export');
    const openButton = document.getElementById('btn-open-export');
    const itemList = document.getElementById('exportItems');
    const addItemButton = document.getElementById('addExportItem');

    if (modal && openButton && itemList && addItemButton) {
        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        openButton.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
        document.querySelectorAll('.close-export-modal').forEach((button) => {
            button.addEventListener('click', closeModal);
        });
        addItemButton.addEventListener('click', () => {
            const item = itemList.firstElementChild.cloneNode(true);
            item.querySelector('select').value = '';
            item.querySelector('input').value = '';
            item.querySelector('.remove-export-item').classList.remove('hidden');
            itemList.appendChild(item);
        });
        itemList.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-export-item');
            if (removeButton) {
                removeButton.closest('.export-item').remove();
            }
        });
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    document.querySelectorAll('.approve-export-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    window.alert(result.message || 'Không thể duyệt phiếu xuất NVL.');
                    button.disabled = false;
                    return;
                }
                window.location.reload();
            } catch (error) {
                window.alert('Không thể kết nối máy chủ. Vui lòng thử lại sau.');
                button.disabled = false;
            }
        });
    });

    const rejectModal = document.getElementById('modal-reject-export');
    const rejectForm = document.getElementById('reject-export-form');
    const rejectCode = document.getElementById('reject-export-code');
    const rejectReason = document.getElementById('reject-export-reason');
    const rejectError = document.getElementById('reject-export-error');
    const closeRejectModal = () => {
        if (!rejectModal) return;
        rejectModal.classList.add('hidden');
        rejectModal.classList.remove('flex');
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.reject-export-button');
        if (!button || !rejectModal || !rejectCode || !rejectReason || !rejectError) return;

        rejectCode.value = button.getAttribute('data-ma-px') || '';
        rejectReason.value = '';
        rejectError.classList.add('hidden');
        rejectModal.classList.remove('hidden');
        rejectModal.classList.add('flex');
        rejectReason.focus();
    });
    document.querySelectorAll('.close-reject-export').forEach((button) => {
        button.addEventListener('click', closeRejectModal);
    });
    if (rejectForm) {
        rejectForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = rejectForm.querySelector('button[type="submit"]');
            if (!button || !rejectError) return;
            button.disabled = true;
            rejectError.classList.add('hidden');
            try {
                const response = await fetch(rejectForm.action, {
                    method: 'POST',
                    body: new FormData(rejectForm),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Không thể từ chối phiếu xuất NVL.');
                }
                window.location.reload();
            } catch (error) {
                rejectError.textContent = error.message || 'Không thể kết nối máy chủ.';
                rejectError.classList.remove('hidden');
                button.disabled = false;
            }
        });
    }
});
