function initializeProductionOrderForm() {
    const modalAddOrder = document.getElementById('modal-add-order');
    const btnOpenAddOrder = document.getElementById('btn-open-add-order');
    const orderItems = document.getElementById('order-items');
    const addOrderItem = document.getElementById('add-order-item');

    if (!modalAddOrder) {
        return;
    }

    const closeModal = () => {
        modalAddOrder.classList.add('hidden');
        modalAddOrder.classList.remove('flex');
        modalAddOrder.style.display = 'none';
    };

    if (btnOpenAddOrder) {
        btnOpenAddOrder.addEventListener('click', () => {
            modalAddOrder.classList.remove('hidden');
            modalAddOrder.classList.add('flex');
            modalAddOrder.style.display = 'flex';
        });
    }

    modalAddOrder.querySelectorAll('.close-modal').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modalAddOrder.addEventListener('click', (event) => {
        if (event.target === modalAddOrder) {
            closeModal();
        }
    });

    if (!addOrderItem || !orderItems) {
        return;
    }

    const updateRemoveButtons = () => {
        const rows = orderItems.querySelectorAll('.order-item');
        rows.forEach((row) => {
            row.querySelector('.remove-order-item').classList.toggle('hidden', rows.length === 1);
        });
    };

    addOrderItem.addEventListener('click', () => {
        const index = orderItems.querySelectorAll('.order-item').length;
        const row = orderItems.firstElementChild.cloneNode(true);

        row.querySelectorAll('select, input').forEach((field) => {
            field.name = field.name.replace(/\[\d*\]/, `[${index}]`);
            field.value = '';
        });
        row.querySelector('.remove-order-item').classList.remove('hidden');
        orderItems.appendChild(row);
        updateRemoveButtons();
    });

    orderItems.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-order-item');
        if (removeButton) {
            removeButton.closest('.order-item').remove();
            updateRemoveButtons();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProductionOrderForm);
} else {
    initializeProductionOrderForm();
}
