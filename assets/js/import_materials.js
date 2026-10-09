document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-add-import');
    const openButton = document.getElementById('btn-open-import');
    const itemList = document.getElementById('importItems');
    const addItemButton = document.getElementById('addImportItem');

    if (modal && openButton && itemList && addItemButton) {
        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        openButton.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        document.querySelectorAll('.close-import-modal').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        addItemButton.addEventListener('click', () => {
            const item = itemList.firstElementChild.cloneNode(true);
            item.querySelector('select').value = '';
            item.querySelector('input').value = '';
            item.querySelector('.remove-import-item').classList.remove('hidden');
            itemList.appendChild(item);
        });

        itemList.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-import-item');
            if (removeButton) {
                removeButton.closest('.import-item').remove();
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

    document.querySelectorAll('.approve-import-form').forEach((form) => {
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
                    window.alert(result.message || 'Không thể duyệt phiếu nhập kho.');
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
});
