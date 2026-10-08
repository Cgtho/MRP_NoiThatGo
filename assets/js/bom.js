document.addEventListener("DOMContentLoaded", () => {
    let currentBomData = [];
    const productItems = document.querySelectorAll('.product-item');
    const bomSimulationArea = document.getElementById('bomSimulationArea');
    const bomEmptyState = document.getElementById('bomEmptyState');
    const bomTableBody = document.getElementById('bomTableBody');
    const selectedProductName = document.getElementById('selectedProductName');

    // Thêm biến cho ô tìm kiếm
    const productSearch = document.getElementById('productSearch');

    const modalAddProduct = document.getElementById('modal-add-product');
    const btnOpenProduct = document.getElementById('btn-open-add-product');
    const addProductForm = document.getElementById('addProductForm');
    const materialRows = document.getElementById('materialRows');
    const addMaterialRow = document.getElementById('addMaterialRow');
    const addProductMessage = document.getElementById('addProductMessage');
    const saveProductButton = document.getElementById('saveProductButton');

    if (btnOpenProduct) {
        btnOpenProduct.addEventListener('click', () => {
            modalAddProduct.classList.remove('hidden');
        });
    }

    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            if (modalAddProduct) modalAddProduct.classList.add('hidden');
        });
    });

    window.addEventListener('click', function (e) {
        if (e.target === modalAddProduct) {
            modalAddProduct.classList.add('hidden');
        }
    });

    if (addMaterialRow) {
        addMaterialRow.addEventListener('click', () => {
            const index = materialRows.querySelectorAll('.material-row').length;
            const row = materialRows.querySelector('.material-row').cloneNode(true);
            row.querySelectorAll('input, select').forEach(field => {
                field.name = field.name.replace(/\[\d+\]/, `[${index}]`);
                if (field.tagName === 'SELECT') field.selectedIndex = 0;
                else field.value = '1';
            });
            row.querySelector('.remove-material-row').classList.remove('hidden');
            materialRows.appendChild(row);
            updateRemoveButtons();
        });
    }

    function updateRemoveButtons() {
        const rows = materialRows ? materialRows.querySelectorAll('.material-row') : [];
        rows.forEach(row => {
            row.querySelector('.remove-material-row').classList.toggle('hidden', rows.length === 1);
        });
    }

    if (materialRows) {
        materialRows.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-material-row');
            if (removeButton) {
                removeButton.closest('.material-row').remove();
                updateRemoveButtons();
            }
        });
    }

    if (addProductForm) {
        addProductForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            addProductMessage.className = 'mt-4 hidden rounded-lg px-3 py-2 text-sm';
            saveProductButton.disabled = true;
            saveProductButton.classList.add('opacity-60', 'cursor-not-allowed');
            try {
                const response = await fetch(addProductForm.action, {
                    method: 'POST',
                    body: new FormData(addProductForm),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    addProductMessage.textContent = result.message || 'Không thể lưu thành phẩm.';
                    addProductMessage.className = 'mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
                    return;
                }
                window.location.reload();
            } catch (error) {
                addProductMessage.textContent = 'Không thể kết nối máy chủ.';
                addProductMessage.className = 'mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600';
            } finally {
                saveProductButton.disabled = false;
                saveProductButton.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });
    }

    // 1. Chức năng Lọc (Tìm kiếm) Sản phẩm
    if (productSearch) {
        productSearch.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();

            productItems.forEach(item => {
                const maTP = item.getAttribute('data-matp').toLowerCase();
                const tenTP = item.getAttribute('data-name').toLowerCase();

                // Nếu mã hoặc tên chứa từ khóa tìm kiếm thì hiện, ngược lại thì ẩn
                if (maTP.includes(keyword) || tenTP.includes(keyword)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // 2. Bắt sự kiện click chọn sản phẩm
    productItems.forEach(item => {
        item.addEventListener('click', async function (e) {
            e.preventDefault();

            // Đổi giao diện trạng thái Active
            document.querySelectorAll('.product-item').forEach(el => {
                el.classList.remove('border-2', 'border-blue-500');
                el.classList.add('border');
                el.querySelector('.product-code').classList.remove('bg-blue-600', 'text-white');
                el.querySelector('.product-code').classList.add('bg-slate-200', 'text-slate-600');
            });
            this.classList.remove('border');
            this.classList.add('border-2', 'border-blue-500');
            this.querySelector('.product-code').classList.remove('bg-slate-200', 'text-slate-600');
            this.querySelector('.product-code').classList.add('bg-blue-600', 'text-white');

            const maTP = this.getAttribute('data-matp');
            const tenTP = this.dataset.name;

            // Ẩn Empty State, hiện Area (dùng classList)
            bomEmptyState.classList.add('hidden');
            bomSimulationArea.classList.remove('hidden');

            document.getElementById('selectedProductCode').innerText = maTP;
            selectedProductName.innerText = tenTP;

            await fetchBomData(maTP);
        });
    });

    // 3. Fetch dữ liệu BOM từ API
    async function fetchBomData(maTP) {
        try {
            const response = await fetch(`../ajax/ajax_bom.php?action=get_bom&maTP=${encodeURIComponent(maTP)}`);
            const result = await response.json();

            if (result.status === "success") {
                currentBomData = result.data;
                bomEmptyState.classList.add('hidden');
                bomSimulationArea.classList.remove('hidden');
                renderBomTable();
            } else {
                alert("Lỗi: " + result.message);
            }
        } catch (error) {
            console.error("Lỗi Fetch:", error);
        }
    }

    // 4. Render bảng
    function renderBomTable() {
        bomTableBody.innerHTML = '';
        if (currentBomData.length === 0) {
            bomTableBody.innerHTML = '<tr><td colspan="6" class="px-4 py-3 text-center text-slate-500">Chưa có định mức cho sản phẩm này</td></tr>';
            return;
        }

        currentBomData.forEach(item => {
            const tongCanDung = item.dinhMuc; // Chỉ lấy định mức 1 SP
            const tonKho = item.tonKho;
            const thieu = tongCanDung - tonKho;

            let statusHtml = '';
            let rowClass = '';

            if (thieu > 0) {
                statusHtml = `<span class="bg-red-100 text-red-700 text-[10px] px-2 py-1 rounded-full font-medium">Thiếu ${thieu}</span>`;
                rowClass = 'bg-red-50';
            } else {
                statusHtml = `<span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-1 rounded-full font-medium">Đủ</span>`;
            }

            const tr = `
                <tr class="${rowClass}">
                    <td class="px-4 py-3">${item.maNVL}</td>
                    <td class="px-4 py-3">${item.tenNVL}</td>
                    <td class="px-4 py-3 text-slate-500">${item.donViTinh || ''}</td>
                    <td class="px-4 py-3 text-center font-bold text-blue-600">${item.dinhMuc}</td>
                    <td class="px-4 py-3 text-right">${tonKho}</td>
                    <td class="px-4 py-3 text-center">${statusHtml}</td>
                </tr>
            `;
            bomTableBody.insertAdjacentHTML('beforeend', tr);
        });
    }
});