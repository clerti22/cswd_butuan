const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');

openBtn?.addEventListener('click', () => {
    sidebar?.classList.add('open');
    overlay?.classList.add('active');
});

closeBtn?.addEventListener('click', () => {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
});

overlay?.addEventListener('click', () => {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
});

const filters = {
    disability: document.getElementById('filterDisability'),
    barangay: document.getElementById('filterBarangay'),
    year: document.getElementById('filterYear'),
    month: document.getElementById('filterMonth'),
    sex: document.getElementById('filterSex'),
    date_from: document.getElementById('filterDateFrom'),
    date_to: document.getElementById('filterDateTo')
};

const summaryCards = document.getElementById('summaryCards');
const tableBody = document.getElementById('tableBody');
const tablePagination = document.getElementById('tablePagination');
const emptyState = document.getElementById('emptyState');
const tableSearchInput = document.getElementById('tableSearchInput');
const sortHeaders = document.querySelectorAll('th[data-sort]');
const tabButtons = document.querySelectorAll('.tab-btn');
const dashboardView = document.getElementById('dashboardView');
const tableView = document.getElementById('tableView');

const state = {
    page: 1,
    limit: 10,
    search: '',
    sort_by: 'control_number',
    sort_dir: 'asc',
    data: null
};

let charts = {};

function getFilterValues() {
    return {
        disability: filters.disability?.value || '',
        barangay: filters.barangay?.value || '',
        year: filters.year?.value || '',
        month: filters.month?.value || '',
        sex: filters.sex?.value || '',
        date_from: filters.date_from?.value || '',
        date_to: filters.date_to?.value || ''
    };
}

async function loadReports() {
    const params = new URLSearchParams({
        page: state.page,
        limit: state.limit,
        search: state.search,
        sort_by: state.sort_by,
        sort_order: state.sort_dir,
        ...getFilterValues()
    });

    try {
        const response = await fetch('api_pwd_report.php?' + params.toString());
        if (!response.ok) throw new Error('API Error');
        const resData = await response.json();
        state.data = resData;

        updateFilterOptions(resData.options);
        renderSummaryCards(resData.totals);
        renderCharts(resData.charts);
        renderTable(resData.data);
        renderPagination(resData.pagination);

        updateSortIndicators();

        const hasData = resData.data.length > 0;
        if (hasData) {
            emptyState?.classList.add('hidden');
        } else {
            emptyState?.classList.remove('hidden');
            tableBody.innerHTML = '';
        }
    } catch (error) {
        console.error('Failed to load PWD reports', error);
        alert('Failed to load PWD reports.');
    }
}

function updateFilterOptions(options) {
    if (!options) return;
    
    const currentBrgy = filters.barangay.value;
    filters.barangay.innerHTML = '<option value="">All Barangays</option>';
    options.barangays.forEach(b => {
        const opt = document.createElement('option');
        opt.value = b;
        opt.textContent = b;
        filters.barangay.appendChild(opt);
    });
    filters.barangay.value = currentBrgy;

    const currentYear = filters.year.value;
    filters.year.innerHTML = '<option value="">All Years</option>';
    options.years.forEach(y => {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        filters.year.appendChild(opt);
    });
    filters.year.value = currentYear;
}

function renderSummaryCards(totals) {
    if (!summaryCards || !totals) return;
    
    // Default colors for the cards matching the design
    const colors = ['#2563eb', '#f97316', '#1d4ed8', '#db2777', '#7c3aed'];
    
    summaryCards.innerHTML = totals.map((t, i) => `
        <article class="summary-card" style="background:${colors[i % colors.length]}">
            <h4>${t.title}</h4>
            <p>${Number(t.value || 0).toLocaleString()}</p>
        </article>
    `).join('');
}

function renderCharts(chartData) {
    if (!chartData || !window.CanvasJS) return;

    const createChart = (id, type, dataPoints, color) => {
        if (!document.getElementById(id)) return;
        const c = new CanvasJS.Chart(id, {
            animationEnabled: true,
            theme: "light2",
            axisX: { labelAngle: -45 },
            axisY: { includeZero: true },
            data: [{
                type: type,
                color: color,
                dataPoints: dataPoints
            }]
        });
        c.render();
        charts[id] = c;
    };

    const createPie = (id, dataPoints) => {
        if (!document.getElementById(id)) return;
        const c = new CanvasJS.Chart(id, {
            animationEnabled: true,
            theme: "light2",
            legend: { verticalAlign: 'bottom', horizontalAlign: 'center' },
            data: [{
                type: "pie",
                showInLegend: true,
                indexLabel: "{label}: {y}",
                dataPoints: dataPoints
            }]
        });
        c.render();
        charts[id] = c;
    };

    createChart("chartDisability", "column", chartData.disability, "#6a25d2");
    createChart("chartBarangay", "bar", chartData.barangay, "#1ea4f4");
    createChart("chartYear", "line", chartData.year, "#18b146");
    createChart("chartMonth", "line", chartData.month, "#ff7500");
    createPie("chartSex", chartData.sex);
}

function renderTable(rows) {
    if (!tableBody) return;
    tableBody.innerHTML = rows.map(row => `
        <tr>
            <td>${row.control_number || '-'}</td>
            <td>${row.full_name || '-'}</td>
            <td>${row.barangay || '-'}</td>
            <td>${row.sex || '-'}</td>
            <td>${row.age || '-'}</td>
            <td>${row.disability_types_primary || '-'}</td>
            <td>${row.date_issued || '-'}</td>
        </tr>
    `).join('');
}

function renderPagination(pg) {
    if (!tablePagination) return;
    if (pg.pages <= 1) {
        tablePagination.innerHTML = '';
        return;
    }
    
    let html = '';
    if (pg.current > 1) {
        html += `<button onclick="goToPage(${pg.current - 1})" class="btn btn-muted">Prev</button>`;
    }
    html += `<span>Page ${pg.current} of ${pg.pages}</span>`;
    if (pg.current < pg.pages) {
        html += `<button onclick="goToPage(${pg.current + 1})" class="btn btn-muted">Next</button>`;
    }
    tablePagination.innerHTML = html;
}

window.goToPage = function(page) {
    state.page = page;
    loadReports();
};

function updateSortIndicators() {
    sortHeaders.forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        if (th.dataset.sort === state.sort_by) {
            th.classList.add(state.sort_dir === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    });
}

function switchView(viewId) {
    tabButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.target === viewId);
    });
    
    dashboardView?.classList.toggle('hidden', viewId !== 'dashboardView');
    tableView?.classList.toggle('hidden', viewId !== 'tableView');
    
    if (viewId === 'dashboardView') {
        Object.values(charts).forEach(c => c.render());
    }
}

tabButtons.forEach(btn => {
    btn.addEventListener('click', () => switchView(btn.dataset.target));
});

sortHeaders.forEach((header) => {
    header.addEventListener('click', () => {
        const sortBy = header.dataset.sort;
        if (!sortBy) return;
        if (state.sort_by === sortBy) {
            state.sort_dir = state.sort_dir === 'asc' ? 'desc' : 'asc';
        } else {
            state.sort_by = sortBy;
            state.sort_dir = 'asc';
        }
        loadReports().catch(console.error);
    });
});

tableSearchInput?.addEventListener('input', (event) => {
    state.search = event.target.value.trim();
    state.page = 1;
    loadReports().catch(console.error);
});

Object.values(filters).forEach(select => {
    if (select) {
        select.addEventListener('change', () => {
            state.page = 1;
            loadReports().catch(console.error);
        });
    }
});

document.getElementById('resetFiltersBtn')?.addEventListener('click', () => {
    Object.values(filters).forEach(element => {
        if (element) element.value = '';
    });
    state.page = 1;
    state.search = '';
    tableSearchInput.value = '';
    loadReports().catch(console.error);
});

document.addEventListener('DOMContentLoaded', () => {
    switchView('dashboardView');
    loadReports().catch(console.error);
});

