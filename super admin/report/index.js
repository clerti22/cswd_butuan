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
    program: document.getElementById('filterProgram'),
    barangay: document.getElementById('filterBarangay'),
    year: document.getElementById('filterYear'),
    month: document.getElementById('filterMonth'),
    sex: document.getElementById('filterSex'),
    age_bracket: document.getElementById('filterAgeBracket'),
    sector: document.getElementById('filterSector'),
    household: document.getElementById('filterHousehold'),
    date_from: document.getElementById('filterDateFrom'),
    date_to: document.getElementById('filterDateTo')
};

const summaryCards = document.getElementById('summaryCards');
const tableBody = document.getElementById('tableBody');
const householdTableBody = document.getElementById('householdTableBody');
const tablePagination = document.getElementById('tablePagination');
const emptyState = document.getElementById('emptyState');
const tableSearchInput = document.getElementById('tableSearchInput');

const dashboardView = document.getElementById('dashboardView');
const tableView = document.getElementById('tableView');
const householdView = document.getElementById('householdView');

const tabButtons = document.querySelectorAll('.tab-btn');
const sortHeaders = document.querySelectorAll('th[data-sort]');

const state = {
    page: 1,
    per_page: 10,
    search: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    data: null
};

const charts = {};

function getFilterValues() {
    return Object.fromEntries(
        Object.entries(filters).map(([key, element]) => [key, (element?.value || '').trim()])
    );
}

function toQuery(params) {
    const url = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== '') {
            url.set(key, String(value));
        }
    });
    return url.toString();
}

function asDataPoints(objectData) {
    return Object.entries(objectData || {}).map(([label, y]) => ({ label, y: Number(y || 0) }));
}

function renderChart(containerId, type, data, color = '#2563eb') {
    if (typeof CanvasJS === 'undefined') return;

    if (!charts[containerId]) {
        charts[containerId] = new CanvasJS.Chart(containerId, {
            animationEnabled: true,
            theme: 'light2',
            axisX: { labelAngle: -45 },
            axisY: { includeZero: true },
            data: [{ type, color, dataPoints: [] }]
        });
    }

    charts[containerId].options.data[0].dataPoints = data;
    charts[containerId].render();
}

function renderPie(containerId, data) {
    if (typeof CanvasJS === 'undefined') return;

    if (!charts[containerId]) {
        charts[containerId] = new CanvasJS.Chart(containerId, {
            animationEnabled: true,
            theme: 'light2',
            legend: { verticalAlign: 'bottom', horizontalAlign: 'center' },
            data: [{ type: 'pie', showInLegend: true, indexLabel: '{label}: {y}', dataPoints: [] }]
        });
    }

    charts[containerId].options.data[0].dataPoints = data;
    charts[containerId].render();
}

function renderSummary(summary) {
    const cards = [
        ['Total Beneficiaries', summary.total_beneficiaries, '#2563eb'],
        ['Total Households Served', summary.total_households_served, '#059669'],
        ['Total Programs', summary.total_programs, '#f97316'],
        ['Total Male', summary.total_male, '#1d4ed8'],
        ['Total Female', summary.total_female, '#db2777'],
        ['Total with 2+ Interventions', summary.total_with_two_plus_interventions, '#7c3aed']
    ];

    summaryCards.innerHTML = cards.map(([label, value, color]) => `
        <article class="summary-card" style="background:${color}">
            <h4>${label}</h4>
            <p>${Number(value || 0).toLocaleString()}</p>
        </article>
    `).join('');
}

function fillSelectOptions(selectElement, values, defaultLabel) {
    if (!selectElement) return;
    const current = selectElement.value;
    const options = [`<option value="">${defaultLabel}</option>`].concat(
        values.map((value) => `<option value="${value}">${value}</option>`)
    );
    selectElement.innerHTML = options.join('');
    if (values.includes(current)) {
        selectElement.value = current;
    }
}

function renderTable(rows) {
    if (!rows.length) {
        tableBody.innerHTML = '<tr><td colspan="11">No records found.</td></tr>';
        return;
    }

    tableBody.innerHTML = rows.map((row) => `
        <tr>
            <td>${row.control_number || '-'}</td>
            <td>${row.full_name || '-'}</td>
            <td>${row.program || '-'}</td>
            <td>${row.barangay || '-'}</td>
            <td>${row.sex || '-'}</td>
            <td>${row.age ?? '-'}</td>
            <td>${row.age_bracket || '-'}</td>
            <td>${row.sector || '-'}</td>
            <td>${row.household || '-'}</td>
            <td>${row.interventions || '-'}</td>
            <td>${row.created_at || '-'}</td>
        </tr>
    `).join('');
}

function renderHouseholds(rows) {
    if (!rows.length) {
        householdTableBody.innerHTML = '<tr><td colspan="6">No household has 2 or more interventions in this filter set.</td></tr>';
        return;
    }

    householdTableBody.innerHTML = rows.map((row) => `
        <tr>
            <td>${row.household_head || '-'}</td>
            <td>${row.control_number || '-'}</td>
            <td>${row.barangay || '-'}</td>
            <td>${row.household || '-'}</td>
            <td>${row.intervention_count || 0}</td>
            <td>${row.interventions_received || '-'}</td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const totalPages = Number(pagination.total_pages || 1);
    const currentPage = Number(pagination.page || 1);

    tablePagination.innerHTML = '';
    for (let page = 1; page <= totalPages; page += 1) {
        const button = document.createElement('button');
        button.textContent = page;
        button.classList.toggle('active', page === currentPage);
        button.addEventListener('click', () => {
            state.page = page;
            loadReports();
        });
        tablePagination.appendChild(button);
    }
}

function renderAll(data) {
    state.data = data;

    renderSummary(data.summary || {});

    renderChart('chartProgram', 'column', asDataPoints(data.charts.beneficiaries_per_program), '#6d28d9');
    renderChart('chartBarangay', 'bar', asDataPoints(data.charts.beneficiaries_per_barangay), '#0ea5e9');
    renderChart('chartYear', 'line', asDataPoints(data.charts.beneficiaries_per_year), '#16a34a');
    renderChart('chartMonth', 'line', asDataPoints(data.charts.beneficiaries_per_month), '#f97316');
    renderPie('chartSex', asDataPoints(data.charts.by_sex));
    renderPie('chartAge', asDataPoints(data.charts.by_age_bracket));
    renderPie('chartSector', asDataPoints(data.charts.by_sector));

    renderTable(data.table.rows || []);
    renderPagination(data.table.pagination || { page: 1, total_pages: 1 });
    renderHouseholds(data.household_interventions || []);

    fillSelectOptions(filters.program, data.filter_options.programs || [], 'All Programs');
    fillSelectOptions(filters.barangay, data.filter_options.barangays || [], 'All Barangays');
    fillSelectOptions(filters.year, data.filter_options.years || [], 'All Years');
    fillSelectOptions(filters.sex, data.filter_options.sexes || [], 'All');
    fillSelectOptions(filters.age_bracket, data.filter_options.age_brackets || [], 'All Age Brackets');
    fillSelectOptions(filters.sector, data.filter_options.sectors || [], 'All Sectors');

    emptyState.classList.toggle('hidden', Number(data.table.pagination?.total_rows || 0) > 0);
}

async function loadReports() {
    const params = {
        ...getFilterValues(),
        page: state.page,
        per_page: state.per_page,
        search: state.search,
        sort_by: state.sort_by,
        sort_dir: state.sort_dir
    };

    const response = await fetch(`get_reports_data.php?${toQuery(params)}`);
    const payload = await response.json();

    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to load report.');
    }

    renderAll(payload.data);
    document.getElementById('exportExcelBtn').href = `export_excel.php?${toQuery(getFilterValues())}`;
}

async function fetchAllFilteredRows() {
    const baseParams = {
        ...getFilterValues(),
        search: state.search,
        sort_by: state.sort_by,
        sort_dir: state.sort_dir,
        per_page: 100
    };

    const firstResponse = await fetch(`get_reports_data.php?${toQuery({ ...baseParams, page: 1 })}`);
    const firstPayload = await firstResponse.json();

    if (!firstResponse.ok || !firstPayload.success) {
        throw new Error(firstPayload.message || 'Failed to load rows for PDF export.');
    }

    const allRows = [...(firstPayload.data?.table?.rows || [])];
    const totalPages = Number(firstPayload.data?.table?.pagination?.total_pages || 1);

    for (let page = 2; page <= totalPages; page += 1) {
        const response = await fetch(`get_reports_data.php?${toQuery({ ...baseParams, page })}`);
        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || `Failed to load export page ${page}.`);
        }
        allRows.push(...(payload.data?.table?.rows || []));
    }

    return {
        rows: allRows,
        totalRows: Number(firstPayload.data?.table?.pagination?.total_rows || allRows.length),
        summary: firstPayload.data?.summary || {}
    };
}

function switchView(view) {
    tabButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.view === view));
    dashboardView.classList.toggle('hidden', view !== 'dashboard');
    tableView.classList.toggle('hidden', view !== 'table');
    householdView.classList.toggle('hidden', view !== 'household');
}

async function exportPdf() {
    if (!state.data || !window.jspdf) return;

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const appliedFilters = getFilterValues();

    doc.setFontSize(14);
    doc.text('CSWDD Reports and Analytics', 14, 14);
    doc.setFontSize(10);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 20);

    let y = 26;
    doc.text('Applied Filters:', 14, y);
    y += 5;

    Object.entries(appliedFilters).forEach(([key, value]) => {
        if (value) {
            doc.text(`${key.replaceAll('_', ' ')}: ${value}`, 16, y);
            y += 5;
        }
    });

    const exportDataset = await fetchAllFilteredRows();
    const summary = exportDataset.summary || state.data.summary || {};
    y += 2;
    doc.text(`Total Beneficiaries: ${summary.total_beneficiaries || 0}`, 14, y); y += 5;
    doc.text(`Total Rows in Filtered Result: ${exportDataset.totalRows || 0}`, 14, y); y += 5;
    doc.text(`Total Households Served: ${summary.total_households_served || 0}`, 14, y); y += 5;
    doc.text(`Total with 2+ Interventions: ${summary.total_with_two_plus_interventions || 0}`, 14, y); y += 6;

    const rows = (exportDataset.rows || []).map((row) => [
        row.control_number || '-',
        row.full_name || '-',
        row.program || '-',
        row.barangay || '-',
        row.sex || '-',
        String(row.age ?? '-'),
        row.age_bracket || '-',
        row.created_at || '-'
    ]);

    doc.autoTable({
        startY: y,
        head: [['Control #', 'Name', 'Program', 'Barangay', 'Sex', 'Age', 'Age Bracket', 'Date']],
        body: rows,
        styles: { fontSize: 8 }
    });

    doc.save(`cswdd-report-${new Date().toISOString().slice(0, 10)}.pdf`);
}

document.getElementById('generateReportBtn')?.addEventListener('click', () => {
    state.page = 1;
    loadReports().catch(console.error);
});

document.getElementById('resetFiltersBtn')?.addEventListener('click', () => {
    Object.values(filters).forEach((element) => {
        if (element) element.value = '';
    });
    state.page = 1;
    state.search = '';
    tableSearchInput.value = '';
    loadReports().catch(console.error);
});

document.getElementById('exportPdfBtn')?.addEventListener('click', () => {
    exportPdf().catch((error) => {
        console.error(error);
        alert('Unable to export PDF right now. Please try again.');
    });
});

tableSearchInput?.addEventListener('input', (event) => {
    state.search = event.target.value.trim();
    state.page = 1;
    loadReports().catch(console.error);
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

tabButtons.forEach((button) => {
    button.addEventListener('click', () => switchView(button.dataset.view || 'dashboard'));
});

document.addEventListener('DOMContentLoaded', () => {
    switchView('dashboard');
    loadReports().catch(console.error);
});
