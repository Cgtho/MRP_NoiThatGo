document.addEventListener('DOMContentLoaded', () => {
    // ---------- Toast notification ----------
    const showToast = (message, type = 'success') => {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'fixed right-5 top-20 z-[70] flex w-full max-w-sm flex-col items-end gap-2';
            document.body.appendChild(container);
        }

        const isSuccess = type === 'success';
        const toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.className = 'flex w-full items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg transition '
            + (isSuccess
                ? 'border-teal-200 bg-white text-teal-700'
                : 'border-red-200 bg-white text-red-700');

        const icon = document.createElement('i');
        icon.className = 'mt-0.5 ' + (isSuccess
            ? 'fa-solid fa-circle-check text-teal-500'
            : 'fa-solid fa-circle-exclamation text-red-500');

        const text = document.createElement('span');
        text.className = 'flex-1';
        text.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'text-lg leading-none text-slate-400 hover:text-slate-700';
        close.setAttribute('aria-label', 'Đóng thông báo');
        close.innerHTML = '&times;';

        toast.append(icon, text, close);
        container.appendChild(toast);

        const remove = () => toast.remove();
        close.addEventListener('click', remove);
        window.setTimeout(remove, 5000);
    };

    // ---------- Modal helpers ----------
    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };
    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    // ---------- Modal lập phiếu xuất (nhân viên) ----------
    const exportModal = document.getElementById('exportModal');
    const exportItems = document.getElementById('exportItems');
    const openExportModal = document.getElementById('openExportModal');
    const addExportItem = document.getElementById('addExportItem');

    if (exportModal && exportItems && openExportModal && addExportItem) {
        openExportModal.addEventListener('click', () => openModal(exportModal));
        document.querySelectorAll('#closeExportModal, #cancelExportModal').forEach((button) => {
            button.addEventListener('click', () => closeModal(exportModal));
        });
        exportModal.addEventListener('click', (event) => {
            if (event.target === exportModal) closeModal(exportModal);
        });

        addExportItem.addEventListener('click', () => {
            const item = exportItems.firstElementChild.cloneNode(true);
            item.querySelector('select').value = '';
            item.querySelector('input').value = '';
            item.querySelector('.remove-export-item').classList.remove('hidden');
            exportItems.appendChild(item);
        });
        exportItems.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-export-item')) {
                event.target.closest('.export-item').remove();
            }
        });
    }

    // ---------- Xử lý Phê duyệt / Từ chối (quản lý) ----------
    const approveForm = document.getElementById('approveForm');
    const confirmApproveModal = document.getElementById('confirmApproveModal');
    const confirmApproveBtn = document.getElementById('confirmApproveBtn');
    const rejectModal = document.getElementById('rejectModal');
    const rejectForm = document.getElementById('rejectForm');
    const rejectReason = document.getElementById('rejectReason');
    const rejectReasonError = document.getElementById('rejectReasonError');
    const openRejectBtn = document.getElementById('openRejectBtn');

    const submitExportAction = async (form, button, onDone) => {
        if (button) button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không thể xử lý yêu cầu. Vui lòng thử lại.');
            }
            showToast(result.message || 'Xử lý thành công.', 'success');
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Không thể kết nối máy chủ. Vui lòng thử lại sau.', 'error');
            if (button) button.disabled = false;
            if (typeof onDone === 'function') onDone(null);
        }
    };

    // Nút "Phê duyệt" trên bảng -> mở popup xác nhận
    if (approveForm && confirmApproveModal && confirmApproveBtn) {
        approveForm.addEventListener('submit', (event) => {
            event.preventDefault();
            openModal(confirmApproveModal);
        });
        document.querySelectorAll('#cancelConfirmApprove').forEach((button) => {
            button.addEventListener('click', () => closeModal(confirmApproveModal));
        });
        confirmApproveModal.addEventListener('click', (event) => {
            if (event.target === confirmApproveModal) closeModal(confirmApproveModal);
        });
        confirmApproveBtn.addEventListener('click', () => submitExportAction(approveForm, confirmApproveBtn, closeModal));
    }

    // Nút "Từ chối" trên bảng -> mở modal nhập lý do
    if (openRejectBtn && rejectModal) {
        openRejectBtn.addEventListener('click', () => {
            rejectReasonError.classList.add('hidden');
            openModal(rejectModal);
            rejectReason.focus();
        });
        document.querySelectorAll('#cancelReject, #closeReject').forEach((button) => {
            button.addEventListener('click', () => closeModal(rejectModal));
        });
        rejectModal.addEventListener('click', (event) => {
            if (event.target === rejectModal) closeModal(rejectModal);
        });

        if (rejectForm) {
            rejectForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const reason = rejectReason.value.trim();
                if (reason === '') {
                    rejectReasonError.classList.remove('hidden');
                    rejectReason.focus();
                    return;
                }
                rejectReasonError.classList.add('hidden');
                const submitBtn = rejectForm.querySelector('button[type="submit"]');
                submitExportAction(rejectForm, submitBtn, closeModal);
            });
        }
    }

    // Đóng toast lỗi tồn kho hiển thị từ phía server (nếu còn)
    const stockErrorToast = document.getElementById('stockErrorToast');
    const closeStockErrorToast = document.getElementById('closeStockErrorToast');
    if (stockErrorToast && closeStockErrorToast) {
        const hideStockErrorToast = () => stockErrorToast.remove();
        closeStockErrorToast.addEventListener('click', hideStockErrorToast);
        window.setTimeout(hideStockErrorToast, 10000);
    }
});

