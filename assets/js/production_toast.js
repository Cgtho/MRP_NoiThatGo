document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('productionSuccessToast');
    const closeButton = document.getElementById('closeProductionSuccessToast');
    if (!toast) return;

    const dismiss = () => toast.remove();
    closeButton?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 5000);
});
