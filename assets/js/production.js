document.addEventListener("DOMContentLoaded", () => {
    const modalAddOrder = document.getElementById('modal-add-order');
    const btnOpenAddOrder = document.getElementById('btn-open-add-order');

    // Mở Modal khi bấm Khởi Tạo Lệnh
    if(btnOpenAddOrder) {
        btnOpenAddOrder.addEventListener('click', () => {
            modalAddOrder.classList.remove('hidden');
        });
    }

    // Đóng Modal khi bấm Dấu X hoặc Hủy
    const closeButtons = modalAddOrder.querySelectorAll('.close-modal');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            modalAddOrder.classList.add('hidden');
        });
    });

    // Bấm ra ngoài vùng tối để đóng Modal
    window.addEventListener('click', (e) => {
        if (e.target === modalAddOrder) {
            modalAddOrder.classList.add('hidden');
        }
    });
});