document.addEventListener("DOMContentLoaded", () => {
    let currentBomData = [];
    
    const productItems = document.querySelectorAll('.product-item');
    const bomSimulationArea = document.getElementById('bomSimulationArea');
    const bomEmptyState = document.getElementById('bomEmptyState');
    const bomTableBody = document.getElementById('bomTableBody');
    const simQtyInput = document.getElementById('simQty');
    const selectedProductName = document.getElementById('selectedProductName');
    const btnCreateRequest = document.getElementById('btnCreateRequest');

    // ==========================================
    // 1. XỬ LÝ MODAL (POPUP) THÊM SẢN PHẨM & VẬT TƯ
    // ==========================================
    const modalAddProduct = document.getElementById('modal-add-product');
    const modalAddMaterial = document.getElementById('modal-add-material');

    // Nút mở Modal 1: Thêm Sản phẩm
    const btnOpenProduct = document.getElementById('btn-open-add-product');
    if(btnOpenProduct) {
        btnOpenProduct.addEventListener('click', () => {
            modalAddProduct.classList.remove('hidden');
        });
    }

    // Nút mở Modal 2: Thêm Vật tư
    const btnOpenMaterial = document.getElementById('btn-open-add-material');
    if(btnOpenMaterial) {
        btnOpenMaterial.addEventListener('click', () => {
            modalAddMaterial.classList.remove('hidden');
        });
    }

    // Xử lý tất cả các nút đóng (Dấu X, nút Hủy Bỏ, nút Đóng)
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (modalAddProduct) modalAddProduct.classList.add('hidden');
            if (modalAddMaterial) modalAddMaterial.classList.add('hidden');
        });
    });

    // Bấm ra ngoài vùng tối (overlay) để đóng Modal
    window.addEventListener('click', function(e) {
        if (e.target === modalAddProduct) {
            modalAddProduct.classList.add('hidden');
        }
        if (e.target === modalAddMaterial) {
            modalAddMaterial.classList.add('hidden');
        }
    });

    // 1. Bắt sự kiện click chọn sản phẩm
    productItems.forEach(item => {
        item.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Xóa active class cũ
            document.querySelectorAll('.product-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');

            const maTP = this.getAttribute('data-matp');
            const tenTP = this.innerText;
            
            selectedProductName.innerText = `Định mức: ${tenTP}`;
            simQtyInput.value = 1; // Reset số lượng mô phỏng

            await fetchBomData(maTP);
        });
    });

    // 2. Fetch dữ liệu BOM từ API
    async function fetchBomData(maTP) {
        try {
            const response = await fetch(`../ajax/ajax_bom.php?action=get_bom&maTP=${maTP}`);
            const result = await response.json();

            if (result.status === "success") {
                currentBomData = result.data;
                bomEmptyState.style.display = 'none';
                bomSimulationArea.style.display = 'block';
                renderBomTable(1); // Render với số lượng = 1
            } else {
                alert("Lỗi: " + result.message);
            }
        } catch (error) {
            console.error("Lỗi Fetch:", error);
        }
    }

    // 3. Render bảng và tính toán (Mô phỏng)
    function renderBomTable(simQty) {
        bomTableBody.innerHTML = '';
        let isShortage = false;

        if (currentBomData.length === 0) {
            bomTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Chưa có định mức cho sản phẩm này</td></tr>';
            btnCreateRequest.style.display = 'none';
            return;
        }

        currentBomData.forEach(item => {
            const tongCanDung = item.dinhMuc * simQty;
            const tonKho = item.tonKho;
            const thieu = tongCanDung - tonKho;
            
            let statusHtml = '';
            let rowClass = '';

            if (thieu > 0) {
                statusHtml = `<span class="badge bg-danger">Thiếu ${thieu}</span>`;
                rowClass = 'table-danger'; // Tô đỏ dòng thiếu vật tư
                isShortage = true;
            } else {
                statusHtml = `<span class="badge bg-success">Đủ</span>`;
            }

            const tr = `
                <tr class="${rowClass}">
                    <td>${item.maNVL}</td>
                    <td>${item.tenNVL}</td>
                    <td>${item.dinhMuc}</td>
                    <td class="fw-bold">${tongCanDung}</td>
                    <td>${tonKho}</td>
                    <td>${statusHtml}</td>
                </tr>
            `;
            bomTableBody.insertAdjacentHTML('beforeend', tr);
        });

        // Nếu đủ NVL, hiện nút tạo Yêu cầu xuất
        if(!isShortage) {
            btnCreateRequest.style.display = 'inline-block';
        } else {
            btnCreateRequest.style.display = 'none';
        }
    }

    // 4. Lắng nghe thay đổi ô input số lượng để tính toán realtime
    simQtyInput.addEventListener('input', function() {
        const qty = parseInt(this.value);
        if (qty > 0) {
            renderBomTable(qty);
        }
    });
});