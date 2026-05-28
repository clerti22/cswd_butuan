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
const pdfPreviewModal = document.getElementById('pdfPreviewModal');
const pdfPreviewFrame = document.getElementById('pdfPreviewFrame');
const pdfDownloadBtn = document.getElementById('pdfDownloadBtn');
const pdfCloseBtn = document.getElementById('pdfCloseBtn');

const state = {
    page: 1,
    per_page: 10,
    search: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    data: null
};

let previewPdfBlobUrl = null;
let previewPdfFilename = '';
const pdfAssetCache = new Map();


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
        tableBody.innerHTML = '<tr><td colspan="8">No records found.</td></tr>';
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
            <td>${row.created_at || '-'}</td>
        </tr>
    `).join('');
}

function renderHouseholds(rows) {
    if (!rows.length) {
        householdTableBody.innerHTML = '<tr><td colspan="5">No client has availed 2 or more distinct CSWD programs in this filter set.</td></tr>';
        return;
    }

    householdTableBody.innerHTML = rows.map((row) => `
        <tr>
            <td>${row.client_name || '-'}</td>
            <td>${row.barangay || '-'}</td>
            <td>${row.sex || '-'}</td>
            <td>${row.programs_availed || row.interventions_received || '-'}</td>
            <td>${row.intervention_count || 0}</td>
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

    renderTable(data.table.rows || []);
    renderPagination(data.table.pagination || { page: 1, total_pages: 1 });
    renderHouseholds(data.household_interventions || []);

    fillSelectOptions(filters.program, data.filter_options.programs || [], 'All Programs');
    fillSelectOptions(filters.barangay, data.filter_options.barangays || [], 'All Barangays');
    fillSelectOptions(filters.year, data.filter_options.years || [], 'All Years');
    fillSelectOptions(filters.sex, data.filter_options.sexes || [], 'All');
    fillSelectOptions(filters.age_bracket, data.filter_options.age_brackets || [], 'All Age Brackets');

    emptyState.classList.toggle('hidden', Number(data.table.pagination?.total_rows || 0) > 0);
}

async function loadReports() {
    try {
        const params = {
            ...getFilterValues(),
            page: state.page,
            per_page: state.per_page,
            search: state.search,
            sort_by: state.sort_by,
            sort_dir: state.sort_dir
        };

        const response = await fetch(`get_reports_data.php?${toQuery(params)}`);
        const responseText = await response.text();
        let payload = null;

        try {
            payload = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error('Report endpoint returned an invalid response.');
        }

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load report.');
        }

        renderAll(payload.data);
        document.getElementById('exportExcelBtn').href = `export_excel.php?${toQuery(getFilterValues())}`;
    } catch (error) {
        console.error('loadReports failed:', error);
        emptyState.textContent = 'Unable to load report data right now. Please refresh the page or try again later.';
        emptyState.classList.remove('hidden');
        if (!summaryCards.innerHTML.trim()) {
            renderSummary({
                total_beneficiaries: 0,
                total_households_served: 0,
                total_programs: 0,
                total_male: 0,
                total_female: 0,
                total_with_two_plus_interventions: 0
            });
        }
    }
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

function closePdfPreview() {
    pdfPreviewModal?.classList.add('hidden');
    if (pdfPreviewFrame) {
        pdfPreviewFrame.src = 'about:blank';
    }

    if (previewPdfBlobUrl) {
        URL.revokeObjectURL(previewPdfBlobUrl);
        previewPdfBlobUrl = null;
    }

    previewPdfFilename = '';
}

function openPdfPreview(blob, filename) {
    if (!pdfPreviewModal || !pdfPreviewFrame) {
        throw new Error('PDF preview UI is missing.');
    }

    if (previewPdfBlobUrl) {
        URL.revokeObjectURL(previewPdfBlobUrl);
    }

    previewPdfBlobUrl = URL.createObjectURL(blob);
    previewPdfFilename = filename;

    pdfPreviewFrame.src = previewPdfBlobUrl;
    pdfPreviewModal.classList.remove('hidden');
}

function downloadPreviewPdf() {
    if (!previewPdfBlobUrl) return;

    const link = document.createElement('a');
    link.href = previewPdfBlobUrl;
    link.download = previewPdfFilename || `cswdd-report-${new Date().toISOString().slice(0, 10)}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
}

async function loadImageDataUrl(path) {
    if (pdfAssetCache.has(path)) return pdfAssetCache.get(path);

    try {
        const response = await fetch(path);
        if (!response.ok) throw new Error(`Failed to load image: ${path}`);
        const blob = await response.blob();
        const dataUrl = await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
        pdfAssetCache.set(path, dataUrl);
        return dataUrl;
    } catch (error) {
        console.warn('PDF asset load failed:', path, error);
        pdfAssetCache.set(path, null);
        return null;
    }
}

async function drawReportHeaderAndFooter(doc, pageNumber, pageCount) {
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();

    const sealImagePath = '../images-logo/butuanseal.png';
    const qrImagePath = '../../super admin/images-logo/4ea40ea1-15b1-44ac-b015-6fab929d199e.jpg';
    const footerLogoPath = '../../super admin/client management/dpsd forms/pwud/print form/footer.png';

    const sealImageData = await loadImageDataUrl(sealImagePath);
    if (sealImageData) {
        doc.addImage(sealImageData, 'PNG', 12, 7, 20, 20);
    }

    doc.setFont('times', 'normal');
    doc.setFontSize(10);
    doc.setTextColor(10, 35, 83);
    doc.text('Republic of the Philippines', 36, 12);

    doc.setFontSize(14);
    doc.text('CITY GOVERNMENT OF ', 36, 17);
    doc.setTextColor(165, 42, 42);
    doc.text('BUTUAN', 83.5, 17);

    doc.setTextColor(10, 35, 83);
    doc.setFont('times', 'bold');
    doc.setFontSize(12);
    doc.text('City Social Welfare and Development Department', 36, 22.5);
    doc.text('Butuan City', 36, 27.5);

    doc.setFillColor(71, 187, 236);
    doc.rect(12, 30, pageWidth - 24, 1.8, 'F');
    doc.setFillColor(17, 73, 136);
    doc.rect(pageWidth * 0.63, 30, (pageWidth - 24) * 0.37, 1.8, 'F');

    const footerTop = pageHeight - 16;
    doc.setFillColor(11, 61, 110);
    doc.rect(12, footerTop - 7.5, pageWidth - 24, 1.5, 'F');

    const qrImageData = await loadImageDataUrl(qrImagePath);
    if (qrImageData) {
        doc.addImage(qrImageData, 'JPEG', 16, footerTop - 5, 12, 12);
    } else {
        doc.rect(16, footerTop - 5, 12, 12);
    }

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(0, 51, 153);
    doc.setFontSize(7);
    doc.text('Jose Rosales Ave. Brgy. Tandang Sora, Butuan City', 31, footerTop - 0.5);
    doc.text('Email: cswdd@butuan.gov.ph', 31, footerTop + 2.5);
    doc.text('Phone: 0956-224-1679', 31, footerTop + 5.5);
    doc.text('www.butuan.gov.ph', 31, footerTop + 8.5);

    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.text('TUVNORD', pageWidth - 66, footerTop + 1, { align: 'center' });
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7);
    doc.text('Certificate Registration', pageWidth - 66, footerTop + 4, { align: 'center' });
    doc.text('No. PHP', pageWidth - 66, footerTop + 7, { align: 'center' });
    doc.text('QMS 23 95 0116', pageWidth - 66, footerTop + 10, { align: 'center' });

    const footerLogoData = await loadImageDataUrl(footerLogoPath);
    if (footerLogoData) {
        doc.addImage(footerLogoData, 'PNG', pageWidth - 46, footerTop - 2.5, 30, 10);
    }

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(70, 70, 70);
    doc.text(`Page ${pageNumber} of ${pageCount}`, pageWidth - 12, pageHeight - 2.5, { align: 'right' });
    doc.setTextColor(0, 0, 0);
}

async function exportPdf() {
    if (!state.data || !window.jspdf) return;

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const appliedFilters = getFilterValues();

    doc.setFontSize(14);
    doc.text('CSWDD Reports and Analytics', 14, 48);
    doc.setFontSize(10);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 54);

    let y = 60;
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
        row.created_at || '-'
    ]);

    doc.autoTable({
        startY: y,
        head: [['Control #', 'Name', 'Program', 'Barangay', 'Sex', 'Age', 'Date']],
        body: rows,
        margin: { top: 44, bottom: 22 },
        styles: { fontSize: 8 }
    });

    const pageCount = doc.internal.getNumberOfPages();
    for (let page = 1; page <= pageCount; page += 1) {
        doc.setPage(page);
        await drawReportHeaderAndFooter(doc, page, pageCount);
    }

    const filename = `cswdd-report-${new Date().toISOString().slice(0, 10)}.pdf`;
    const pdfBlob = doc.output('blob');
    openPdfPreview(pdfBlob, filename);
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

pdfCloseBtn?.addEventListener('click', closePdfPreview);
pdfDownloadBtn?.addEventListener('click', downloadPreviewPdf);
pdfPreviewModal?.addEventListener('click', (event) => {
    if (event.target === pdfPreviewModal) {
        closePdfPreview();
    }
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && pdfPreviewModal && !pdfPreviewModal.classList.contains('hidden')) {
        closePdfPreview();
    }
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

if (window.adminDivision) {
  const divisionLabel = document.getElementById('reportDivisionLabel');
  if (divisionLabel) divisionLabel.textContent = window.adminDivision;
}
