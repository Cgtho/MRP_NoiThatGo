/**
 * MRP Analytics & Reporting Dashboard.
 * Gọi ajax/get_mrp_stats.php rồi render KPI, biểu đồ Chart.js và bảng cân đối vật tư.
 */
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('mrpDashboard');
    if (!root) return;

    const state = {
        materials: [],
        search: '',
        status: 'all', // all | thieu | du
        page: 1,
        pageSize: 8,
        charts: { bar: null, doughnut: null },
    };

    const els = {
        loading: document.getElementById('mrpLoading'),
        error: document.getElementById('mrpError'),
        errorText: document.getElementById('mrpErrorText'),
        content: document.getElementById('mrpContent'),
        totalRequirement: document.getElementById('kpiTotalRequirement'),
        shortageKinds: document.getElementById('kpiShortageKinds'),
        ordersPending: document.getElementById('kpiOrdersPending'),
        ordersInProgress: document.getElementById('kpiOrdersInProgress'),
        ordersCompleted: document.getElementById('kpiOrdersCompleted'),
        searchInput: document.getElementById('mrpSearch'),
        statusFilter: document.getElementById('mrpStatusFilter'),
        tableBody: document.getElementById('mrpTableBody'),
        tableInfo: document.getElementById('mrpTableInfo'),
        pagination: document.getElementById('mrpPagination'),
        rowCount: document.getElementById('mrpRowCount'),
    };

    const fmt = (n) => Number(n || 0).toLocaleString('vi-VN');
    const esc = (s) => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

    const setLoading = (isLoading) => {
        els.loading.classList.toggle('hidden', !isLoading);
        els.error.classList.add('hidden');
        els.content.classList.toggle('hidden', isLoading);
    };

    const showError = (message) => {
        els.loading.classList.add('hidden');
        els.content.classList.add('hidden');
        els.errorText.textContent = message;
        els.error.classList.remove('hidden');
    };

    // ---------- Render KPI ----------
    const renderKpis = (kpis) => {
        els.totalRequirement.textContent = fmt(kpis.totalRequirement);
        els.shortageKinds.textContent = fmt(kpis.shortageKinds);
        els.ordersPending.textContent = fmt(kpis.orders.pending);
        els.ordersInProgress.textContent = fmt(kpis.orders.inProgress);
        els.ordersCompleted.textContent = fmt(kpis.orders.completed);
    };

    // ---------- Render biểu đồ ----------
    const renderCharts = (chart, orders) => {
        if (typeof Chart === 'undefined') return;

        const palette = {
            demand: 'rgba(99, 102, 241, 0.85)',   // indigo
            stock: 'rgba(16, 185, 129, 0.85)',    // emerald
        };

        if (state.charts.bar) state.charts.bar.destroy();
        const barCtx = document.getElementById('mrpBarChart');
        if (barCtx) {
            state.charts.bar = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: chart.labels,
                    datasets: [
                        { label: 'Nhu cầu MRP', data: chart.demand, backgroundColor: palette.demand, borderRadius: 4 },
                        { label: 'Tồn kho hiện tại', data: chart.stock, backgroundColor: palette.stock, borderRadius: 4 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        }

        if (state.charts.doughnut) state.charts.doughnut.destroy();
        const doughnutCtx = document.getElementById('mrpDoughnutChart');
        if (doughnutCtx) {
            state.charts.doughnut = new Chart(doughnutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Chờ xử lý', 'Đang làm', 'Đã hoàn thành'],
                    datasets: [{
                        data: [orders.pending, orders.inProgress, orders.completed],
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.85)',   // amber
                            'rgba(59, 130, 246, 0.85)',   // blue
                            'rgba(16, 185, 129, 0.85)',   // emerald
                        ],
                        borderWidth: 1,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        }
    };

    // ---------- Lọc dữ liệu ----------
    const getFiltered = () => {
        const q = state.search.trim().toLowerCase();
        return state.materials.filter((m) => {
            if (state.status !== 'all' && m.trangThai !== state.status) return false;
            if (q === '') return true;
            return m.maNVL.toLowerCase().includes(q) || m.tenNVL.toLowerCase().includes(q);
        });
    };

    // ---------- Render bảng ----------
    const renderTable = () => {
        const filtered = getFiltered();
        const total = filtered.length;
        const pageCount = Math.max(1, Math.ceil(total / state.pageSize));
        if (state.page > pageCount) state.page = pageCount;
        const startIdx = (state.page - 1) * state.pageSize;
        const pageRows = filtered.slice(startIdx, startIdx + state.pageSize);

        els.rowCount.textContent = fmt(total);
        els.tableInfo.textContent = total === 0
            ? 'Không có dữ liệu phù hợp.'
            : `Hiển thị ${startIdx + 1}–${Math.min(startIdx + state.pageSize, total)} trong ${total} nguyên vật liệu`;

        if (pageRows.length === 0) {
            els.tableBody.innerHTML = `<tr><td colspan="7" class="px-4 py-6 text-center text-slate-400 text-sm">Không tìm thấy nguyên vật liệu phù hợp.</td></tr>`;
            els.pagination.innerHTML = '';
            return;
        }

        els.tableBody.innerHTML = pageRows.map((m) => {
            const statusBadge = m.trangThai === 'thieu'
                ? '<span class="border bg-red-50 text-red-600 border-red-200 text-[10px] px-2 py-0.5 rounded font-medium">Thiếu hụt</span>'
                : '<span class="border bg-emerald-50 text-emerald-600 border-emerald-200 text-[10px] px-2 py-0.5 rounded font-medium">Đủ hàng</span>';
            const shortCell = m.thieuHut > 0
                ? `<span class="text-red-600 font-bold">-${fmt(m.thieuHut)}</span>`
                : '<span class="text-slate-300">0</span>';
            return `<tr class="hover:bg-slate-50 transition">
                <td class="px-4 py-3 font-bold text-slate-800">${esc(m.maNVL)}</td>
                <td class="px-4 py-3 text-slate-700">${esc(m.tenNVL)}</td>
                <td class="px-4 py-3 text-slate-500">${esc(m.tenDVT)}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-700">${fmt(m.tonKho)}</td>
                <td class="px-4 py-3 text-right font-medium text-indigo-600">${fmt(m.nhuCau)}</td>
                <td class="px-4 py-3 text-right">${shortCell}</td>
                <td class="px-4 py-3 text-center">${statusBadge}</td>
            </tr>`;
        }).join('');

        renderPagination(pageCount);
    };

    // ---------- Phân trang ----------
    const renderPagination = (pageCount) => {
        if (pageCount <= 1) {
            els.pagination.innerHTML = '';
            return;
        }
        const btn = (label, page, disabled, active) => {
            const base = 'px-3 py-1 text-xs rounded-md border transition';
            const activeCls = 'bg-indigo-600 text-white border-indigo-600';
            const normalCls = 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
            const disCls = 'opacity-40 cursor-not-allowed bg-white text-slate-400 border-slate-200';
            const cls = `${base} ${active ? activeCls : disabled ? disCls : normalCls}`;
            const attr = disabled ? 'disabled' : `data-page="${page}"`;
            return `<button type="button" class="${cls}" ${attr}>${label}</button>`;
        };

        let html = '';
        html += btn('&laquo;', state.page - 1, state.page === 1, false);
        for (let p = 1; p <= pageCount; p++) {
            html += btn(String(p), p, false, p === state.page);
        }
        html += btn('&raquo;', state.page + 1, state.page === pageCount, false);
        els.pagination.innerHTML = html;
    };

    els.pagination.addEventListener('click', (event) => {
        const target = event.target.closest('button[data-page]');
        if (!target) return;
        state.page = Number(target.dataset.page);
        renderTable();
    });

    // ---------- Sự kiện tìm kiếm / lọc ----------
    let debounceTimer = null;
    els.searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            state.search = els.searchInput.value;
            state.page = 1;
            renderTable();
        }, 200);
    });
    els.statusFilter.addEventListener('change', () => {
        state.status = els.statusFilter.value;
        state.page = 1;
        renderTable();
    });

    // ---------- Tải dữ liệu ----------
    const load = async () => {
        setLoading(true);
        try {
            const response = await fetch('../ajax/get_mrp_stats.php', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Không thể tải dữ liệu thống kê MRP.');
            }
            state.materials = data.materials || [];
            renderKpis(data.kpis);
            renderCharts(data.chart, data.kpis.orders);
            renderTable();
            setLoading(false);
        } catch (error) {
            showError(error.message || 'Không thể kết nối máy chủ. Vui lòng thử lại sau.');
        }
    };

    load();
});

