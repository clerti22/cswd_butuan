const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');
const searchInput = document.getElementById('clientSearchInput');
const searchButton = document.getElementById('clientSearchButton');
const filterProgramSelect = document.getElementById('filterProgramSelect');
const filterBarangaySelect = document.getElementById('filterBarangaySelect');
const filterAgeBracketSelect = document.getElementById('filterAgeBracketSelect');
const filterSexSelect = document.getElementById('filterSexSelect');
const applyFilterButton = document.getElementById('applyFilterButton');
const clearFilterButton = document.getElementById('clearFilterButton');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

openBtn.addEventListener('click', openSidebar);
closeBtn.addEventListener('click', closeSidebar);
overlay.addEventListener('click', closeSidebar);

// Close sidebar when clicking nav items on mobile
document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', () => {
        if (window.innerWidth < 1024) {
            closeSidebar();
        }
    });
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        closeSidebar();
    }
});

const tabs = document.querySelectorAll('.tab');
const tabPanels = document.querySelectorAll('.tab-panel');
const addClientBtn = document.getElementById('addClientBtn');
const scanCopyModal = document.getElementById('scanCopyModal');
const scanCopyModalTitle = document.getElementById('scanCopyModalTitle');
const scanCopyClientMeta = document.getElementById('scanCopyClientMeta');
const scanCopyInstruction = document.getElementById('scanCopyInstruction');
const scanModalBody = document.getElementById('scanModalBody');
const scanGenerateQrAgainButton = document.getElementById('scanGenerateQrAgainButton');
const scanCloseModalButton = document.getElementById('scanCloseModalButton');
const clientDataCache = {
    dpsd: {},
    pssd: {},
    rpsd: {},
    cod: {}
};
let activeScanClient = null;

const tabPaginationState = {
    dpsd: { page: 1, totalPages: 1 },
    pssd: { page: 1, totalPages: 1 },
    rpsd: { page: 1, totalPages: 1 },
    cod: { page: 1, totalPages: 1 }
};

const tabSearchState = {
    dpsd: '',
    pssd: '',
    rpsd: '',
    cod: ''
};

const tabFilterState = {
    dpsd: { program: '', barangay: '', age_bracket: '', sex: '' },
    pssd: { program: '', barangay: '', age_bracket: '', sex: '' },
    rpsd: { program: '', barangay: '', age_bracket: '', sex: '' },
    cod: { program: '', barangay: '', age_bracket: '', sex: '' }
};

const tabFilterOptionsState = {
    dpsd: { programs: [], barangays: [] },
    pssd: { programs: [], barangays: [] },
    rpsd: { programs: [], barangays: [] },
    cod: { programs: [], barangays: [] }
};

const prevButton = document.querySelector('.pagination .pager-btn:first-child');
const nextButton = document.querySelector('.pagination .pager-btn:last-child');

const addClientRoutes = {
    'PYAP': 'dpsd forms/pyap/index.php',
    'SOLO PARENT': 'dpsd forms/solo parent/index.php',
    'PWUD': 'dpsd forms/pwud/index.php',
    'SENIOR CITIZEN': 'pssd forms/senior citizen/index.php',
    'PWD': 'pssd forms/pwd/index.php',
    'VAWC': 'rpsd forms/vawc/index.php',
    'CSAE': 'rpsd forms/csae/index.php',
    'CAR': 'rpsd forms/car/index.php',
    'CICL': 'rpsd forms/cicl/index.php',
    'CIU': 'rpsd forms/ciu/index.php'
};
const formRoutePrefix = '';
const normalizedProgramAliases = {
    SOLOPARENT: 'SOLO PARENT'
};
const programRouteCatalog = Object.entries(addClientRoutes).reduce((catalog, [programName, route]) => {
    const normalizedProgramName = String(programName || '').trim().toUpperCase().replace(/\s+/g, ' ');
    if (normalizedProgramName) {
        catalog[normalizedProgramName] = { route };
    }
    return catalog;
}, {});

function buildFormRoute(path) {
    return `${formRoutePrefix}${path}`;
}

function normalizeProgramName(programName) {
    const normalized = String(programName || '').trim().toUpperCase().replace(/\s+/g, ' ');
    const collapsed = normalized.replace(/\s+/g, '');
    return normalizedProgramAliases[collapsed] || normalized;
}

function getScopedAddClientRoute() {
    const scopedProgram = normalizeProgramName((window.encoderScope && window.encoderScope.program) || '');
    if (!scopedProgram || !programRouteCatalog[scopedProgram]) {
        return '';
    }

    return programRouteCatalog[scopedProgram].route;
}
let addClientDirectRoute = '#';
function setAddClientRoute() {
    const route = getScopedAddClientRoute();

    if (!route) {
        addClientDirectRoute = '#';
        if (addClientBtn) {
            addClientBtn.style.display = 'none';
            addClientBtn.title = 'No assigned intake form available.';
        }
        return;
    }

    addClientDirectRoute = buildFormRoute(route);

    if (addClientBtn) {
        addClientBtn.style.display = '';
        addClientBtn.title = 'Add Client';
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getShowRoute(client, variant = 'ciu') {
    const sourceTable = String(client.source_table || '');
    if (sourceTable === 'faced_table') {
        const cn = String(client.control_number || '').trim();
        return cn ? `${buildFormRoute('cod forms/faced/index.php')}?control_number=${encodeURIComponent(cn)}` : '#';
    }

    const keyId = Number(client.key_id);

    if (!Number.isInteger(keyId) || keyId <= 0) {
        return '#';
    }

    const routesByTable = {
        pwud_table: 'dpsd forms/pwud/index.php',
        pyap_table: 'dpsd forms/pyap/index.php',
        soloparent_info_table: 'dpsd forms/solo parent/index.php',
        snr_table: 'pssd forms/senior citizen/index.php',
        pwd_table: 'pssd forms/pwd/index.php',
        car_table: 'rpsd forms/car/index.php',
        cicl_table: 'rpsd forms/cicl/index.php',
        ciu_table: 'rpsd forms/ciu/index.php',
        csae_table: 'rpsd forms/csae/index.php',
        vawc_table: 'rpsd forms/vawc/index.php',
        faced_table: 'cod forms/faced/index.php'
    };

    const baseRoute = routesByTable[sourceTable];
    if (!baseRoute) {
        return '#';
    }

    const params = new URLSearchParams({ id: String(keyId) });
    if (sourceTable === 'ciu_table' && String(variant).toLowerCase() === 'scsr') {
        params.set('variant', 'scsr');
    }

    return `${buildFormRoute(baseRoute)}?${params.toString()}`;
}

function getPrintRoute(client, variant = 'ciu') {
    const keyId = Number(client.key_id);

    if (!Number.isInteger(keyId) || keyId <= 0) {
        return '#';
    }

    const sourceTable = String(client.source_table || '');
    const routesByTable = {
        pwud_table: 'dpsd forms/pwud/print_form.php',
        pyap_table: 'dpsd forms/pyap/print_form.php',
        soloparent_info_table: 'dpsd forms/solo parent/print_form.php',
        snr_table: 'pssd forms/senior citizen/print_form.php',
        pwd_table: 'pssd forms/pwd/print_form.php',
        car_table: 'rpsd forms/car/print_form.php',
        cicl_table: 'rpsd forms/cicl/print_form.php',
        ciu_table: 'rpsd forms/ciu/print_form.php',
        csae_table: 'rpsd forms/csae/print_form.php',
        vawc_table: 'rpsd forms/vawc/print_form.php'
    };

    let baseRoute = routesByTable[sourceTable] || '';
    if (sourceTable === 'ciu_table' && String(variant).toLowerCase() === 'scsr') {
        baseRoute = 'rpsd forms/ciu/print_scsr.php';
    }

    if (!baseRoute) {
        return '#';
    }

    return `${buildFormRoute(baseRoute)}?id=${encodeURIComponent(String(keyId))}`;
}

function createClientRow(client) {
    const showRoute = getShowRoute(client);
    const printRoute = getPrintRoute(client);
    const sourceTable = String(client.source_table || '');
    const isCodRecord = sourceTable === 'faced_table';
    const ciuFormRoute = getShowRoute(client, 'ciu');
    const scsrFormRoute = getShowRoute(client, 'scsr');
    const ciuPrintRoute = getPrintRoute(client, 'ciu');
    const scsrPrintRoute = getPrintRoute(client, 'scsr');
    const showButton = showRoute === '#'
        ? '<button class="btn btn-sm btn-show" type="button" disabled title="Show is currently available for PWUD, PYAP, Solo Parent, PWD, Senior Citizen, CAR, CICL, CSAE, and VAWC records only.">Show</button>'
        : sourceTable === 'ciu_table'
            ? `<div class="show-dropdown">
                <button class="btn btn-sm btn-show show-dropdown-toggle" type="button" data-action="toggle-show-options" aria-haspopup="true" aria-expanded="false">Show</button>
                <div class="show-dropdown-menu" role="menu">
                    <a class="show-dropdown-item" href="${escapeHtml(ciuFormRoute)}" role="menuitem">CIU Form</a>
                    <a class="show-dropdown-item" href="${escapeHtml(scsrFormRoute)}" role="menuitem">Social Case Study Report (SCSR)</a>
                </div>
            </div>`
            : `<a class="btn btn-sm btn-show" href="${escapeHtml(showRoute)}">Show</a>`;
    const printButton = printRoute === '#'
        ? '<button class="btn btn-sm btn-print" type="button" disabled title="Print is not available for this record.">Print</button>'
        : sourceTable === 'ciu_table'
            ? `<div class="print-dropdown">
                <button class="btn btn-sm btn-print print-dropdown-toggle" type="button" data-action="toggle-print-options" aria-haspopup="true" aria-expanded="false">Print</button>
                <div class="print-dropdown-menu" role="menu">
                    <a class="print-dropdown-item" href="${escapeHtml(ciuPrintRoute)}" role="menuitem">CIU Form</a>
                    <a class="print-dropdown-item" href="${escapeHtml(scsrPrintRoute)}" role="menuitem">Social Case Study Report (SCSR)</a>
                </div>
            </div>`
            : `<a class="btn btn-sm btn-print" href="${escapeHtml(printRoute)}">Print</a>`;

    const program = String(client.program ?? '').trim().toUpperCase();
    const isCiuProgram = program === 'CIU';
    const scanLabel = client.has_scan_copy ? 'Show Scan Copy' : 'Scan Copy';
    const scanButton = isCiuProgram
        ? `<div class="scan-dropdown">
                <button class="btn btn-scan scan-dropdown-toggle" type="button" data-action="toggle-scan-options" aria-haspopup="true" aria-expanded="false">${scanLabel}</button>
                <div class="scan-dropdown-menu" role="menu">
                    <button class="scan-dropdown-item" type="button" role="menuitem" data-action="scan-copy" data-control-number="${escapeHtml(client.control_number)}" data-program="CIU Form" data-full-name="${escapeHtml(client.full_name)}">CIU Form</button>
                    <button class="scan-dropdown-item" type="button" role="menuitem" data-action="scan-copy" data-control-number="${escapeHtml(client.control_number)}" data-program="Social Case Study Report (SCSR)" data-full-name="${escapeHtml(client.full_name)}">Social Case Study Report (SCSR)</button>
                </div>
            </div>`
        : `<button class="btn btn-scan" type="button" data-action="scan-copy" data-control-number="${escapeHtml(client.control_number)}" data-program="${escapeHtml(client.program)}" data-full-name="${escapeHtml(client.full_name)}">${scanLabel}</button>`;

    return `
        <tr>
            <td>${escapeHtml(client.control_number)}</td>
            <td>${escapeHtml(client.full_name)}</td>
            <td>${escapeHtml(client.age)}</td>
            <td>${escapeHtml(client.sex)}</td>
            <td>${escapeHtml(client.barangay)}</td>
            <td>${escapeHtml(client.program)}</td>
            <td>${escapeHtml(client.division)}</td>
            <td>${escapeHtml(client.contact_number)}</td>
            <td>${escapeHtml(client.created_at)}</td>
            <td>
                <div class="client-action-buttons">${isCodRecord ? `${showButton}${scanButton}` : `${showButton}${printButton}${scanButton}`}</div>
            </td>
        </tr>
    `;
}

function renderClients(tabKey, clients) {
    const panel = document.querySelector(`.tab-panel[data-tab-content="${tabKey}"]`);

    if (!panel) {
        return;
    }

    if (!Array.isArray(clients) || clients.length === 0) {
        panel.innerHTML = '<tr><td colspan="10">No client records found.</td></tr>';
        return;
    }

    panel.innerHTML = clients.map(createClientRow).join('');
}

function openScanModal() {
    if (!scanCopyModal) {
        return;
    }
    scanCopyModal.hidden = false;
    document.body.classList.add('has-modal-open');
}

function closeScanModal() {
    if (!scanCopyModal) {
        return;
    }
    scanCopyModal.hidden = true;
    if (!document.querySelector('.scan-modal:not([hidden])')) {
        document.body.classList.remove('has-modal-open');
    }
}

async function fetchScanDetails(clientControlNumber, program) {
    const params = new URLSearchParams({
        client_control_number: clientControlNumber,
        program
    });
    const response = await fetch(`get_scan_copy.php?${params.toString()}`);
    const payload = await response.json();
    if (!response.ok) {
        throw new Error(payload.message || 'Unable to fetch scan copy.');
    }
    return payload;
}

async function fetchQrUploadLink(clientControlNumber, program) {
    const params = new URLSearchParams({
        client_control_number: clientControlNumber,
        program
    });
    const response = await fetch(`generate_scan_qr.php?${params.toString()}`);
    const payload = await response.json();
    if (!response.ok) {
        throw new Error(payload.message || 'Unable to generate QR link.');
    }
    return {
        mobileUploadUrl: String(payload.mobile_upload_url || ''),
    };
}

function buildQrMarkup(qrData) {
    const absoluteUploadUrl = String(qrData.mobileUploadUrl || '').trim();
    const qrSource = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(absoluteUploadUrl)}`;
    const isLikelyUnreachable = /^https?:\/\/(localhost|127\.0\.0\.1|::1)([:/]|$)/i.test(absoluteUploadUrl);
    const warningMarkup = isLikelyUnreachable
        ? '<p class="scan-error">This QR uses localhost/loopback. Set <code>CCIMS_PUBLIC_ORIGIN</code> in your server (example: <code>http://192.168.1.20</code>) so phones on your Wi‑Fi can open the upload page.</p>'
        : '';

    return `
        <div class="scan-qr-wrapper">
            <img src="${escapeHtml(qrSource)}" alt="QR code for scan copy upload" id="scanQrImage">
            ${warningMarkup}
            <p class="scan-upload-link">Open link directly: <a href="${escapeHtml(absoluteUploadUrl)}" target="_blank" rel="noopener" id="scanUploadLink">Mobile Upload Page</a></p>
        </div>
    `;
}

async function renderScanModal(client, forceQrView = false) {
    if (!scanCopyModal || !scanModalBody || !scanCopyInstruction || !scanCopyClientMeta) {
        return;
    }

    activeScanClient = client;
    openScanModal();

    const safeName = String(client.fullName || '');
    scanCopyModalTitle.textContent = 'Scan Copy';
    scanCopyClientMeta.textContent = `${safeName} • ${client.controlNumber} • ${client.program}`;
    scanModalBody.innerHTML = '<p>Loading scan copy details...</p>';
    scanCopyInstruction.textContent = '';
    scanGenerateQrAgainButton.hidden = true;

    try {
        const scanPayload = await fetchScanDetails(client.controlNumber, client.program);
        const hasScan = Boolean(scanPayload.has_scan);

        if (!hasScan || forceQrView) {
            const qrData = await fetchQrUploadLink(client.controlNumber, client.program);
            scanCopyInstruction.textContent = 'Scan this QR code using your phone to capture and upload a scan copy. Uploaded photos are stored in the database.';
            scanModalBody.innerHTML = buildQrMarkup(qrData);
            scanGenerateQrAgainButton.hidden = false;
            return;
        }

        const scan = scanPayload.scan || {};
        const imagePath = String(scan.image_path || '');
        const uploadedBy = String(scan.uploaded_by || 'Unknown');
        const uploadedAt = String(scan.uploaded_at || '');

        scanCopyInstruction.textContent = `Uploaded by ${uploadedBy}${uploadedAt ? ` on ${uploadedAt}` : ''}. Uploaded photos are stored in the database.`;
        scanModalBody.innerHTML = `
            <div class="scan-preview-wrap">
                <img src="${escapeHtml(imagePath)}" alt="Client scan copy" class="scan-preview-image">
            </div>
            <div class="scan-preview-actions">
                <button class="btn btn-scan" type="button" id="scanReuploadButton">Re-upload / Replace Image</button>
            </div>
        `;
        scanGenerateQrAgainButton.hidden = false;

        const reuploadButton = document.getElementById('scanReuploadButton');
        if (reuploadButton) {
            reuploadButton.addEventListener('click', async () => {
                const qrData = await fetchQrUploadLink(client.controlNumber, client.program);
                scanCopyInstruction.textContent = 'Scan this QR code using your phone to retake and replace the scan copy. Uploaded photos are stored in the database.';
                scanModalBody.innerHTML = buildQrMarkup(qrData);
                scanGenerateQrAgainButton.hidden = false;
            });
        }
    } catch (error) {
        scanModalBody.innerHTML = `<p class="scan-error">${escapeHtml(error.message || 'Unable to load scan copy flow.')}</p>`;
    }
}

function getActiveTabKey() {
    const activeTab = document.querySelector('.tab.active');
    if (activeTab?.dataset?.tab) {
        return activeTab.dataset.tab;
    }

    const division = String((window.encoderScope && window.encoderScope.division) || '').trim().toLowerCase();
    if (division && Object.prototype.hasOwnProperty.call(tabPaginationState, division)) {
        return division;
    }

    return 'dpsd';
}

function getCacheKey(tabKey) {
    const pagination = tabPaginationState[tabKey] || { page: 1 };
    const searchTerm = tabSearchState[tabKey] || '';
    const filters = tabFilterState[tabKey] || {};
    return `${pagination.page}::${searchTerm}::${filters.program || ''}::${filters.barangay || ''}::${filters.age_bracket || ''}::${filters.sex || ''}`;
}

function setSelectOptions(selectElement, values, allLabel, selectedValue) {
    if (!selectElement) {
        return;
    }

    const normalizedValues = Array.isArray(values) ? values : [];
    const optionsHtml = [`<option value="">${escapeHtml(allLabel)}</option>`]
        .concat(normalizedValues.map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`))
        .join('');
    selectElement.innerHTML = optionsHtml;
    selectElement.value = selectedValue || '';
}

function syncFilterInputs(tabKey) {
    const filters = tabFilterState[tabKey] || { program: '', barangay: '', age_bracket: '', sex: '' };

    if (filterAgeBracketSelect) {
        filterAgeBracketSelect.value = filters.age_bracket || '';
    }

    if (filterSexSelect) {
        filterSexSelect.value = filters.sex || '';
    }

    if (filterProgramSelect) {
        filterProgramSelect.value = filters.program || '';
    }

    if (filterBarangaySelect) {
        filterBarangaySelect.value = filters.barangay || '';
    }
}

function syncFilterOptions(tabKey, options = {}) {
    const filters = tabFilterState[tabKey] || { program: '', barangay: '', age_bracket: '', sex: '' };
    setSelectOptions(filterProgramSelect, options.programs || [], 'All Programs', filters.program || '');
    setSelectOptions(filterBarangaySelect, options.barangays || [], 'All Barangays', filters.barangay || '');
    syncFilterInputs(tabKey);
}

function updatePaginationButtons(tabKey) {
    if (!prevButton || !nextButton) {
        return;
    }

    const pagination = tabPaginationState[tabKey] || { page: 1, totalPages: 1 };

    prevButton.disabled = pagination.page <= 1;
    nextButton.disabled = pagination.page >= pagination.totalPages;
}

async function loadClients(tabKey) {
    const pagination = tabPaginationState[tabKey] || { page: 1, totalPages: 1 };
    const currentPage = pagination.page;
    const searchTerm = tabSearchState[tabKey] || '';
    const filters = tabFilterState[tabKey] || { program: '', barangay: '', age_bracket: '', sex: '' };
    const cacheKey = getCacheKey(tabKey);

    if (clientDataCache[tabKey] && clientDataCache[tabKey][cacheKey]) {
        renderClients(tabKey, clientDataCache[tabKey][cacheKey]);
        syncFilterOptions(tabKey, tabFilterOptionsState[tabKey] || {});
        updatePaginationButtons(tabKey);
        return;
    }

    const panel = document.querySelector(`.tab-panel[data-tab-content="${tabKey}"]`);
    if (panel) {
        panel.innerHTML = '<tr><td colspan="10">Loading...</td></tr>';
    }

    try {
        const params = new URLSearchParams({
            tab: tabKey,
            page: String(currentPage),
            search: searchTerm,
            program: filters.program || '',
            barangay: filters.barangay || '',
            age_bracket: filters.age_bracket || '',
            sex: filters.sex || ''
        });
        const response = await fetch(`get_clients.php?${params.toString()}`);
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Unable to load clients.');
        }

        const clients = Array.isArray(payload.clients) ? payload.clients : [];
        const serverPagination = payload.pagination || {};

        tabPaginationState[tabKey] = {
            page: Number(serverPagination.page) > 0 ? Number(serverPagination.page) : currentPage,
            totalPages: Number(serverPagination.total_pages) > 0 ? Number(serverPagination.total_pages) : 1
        };

        if (!clientDataCache[tabKey]) {
            clientDataCache[tabKey] = {};
        }

        clientDataCache[tabKey][cacheKey] = clients;
        tabFilterOptionsState[tabKey] = payload.filter_options || { programs: [], barangays: [] };

        renderClients(tabKey, clients);
        syncFilterOptions(tabKey, tabFilterOptionsState[tabKey]);
        updatePaginationButtons(tabKey);
    } catch (error) {
        if (panel) {
            panel.innerHTML = '<tr><td colspan="10">Unable to load data.</td></tr>';
        }
        updatePaginationButtons(tabKey);
    }
}

function syncSearchInput(tabKey) {
    if (!searchInput) {
        return;
    }

    searchInput.value = tabSearchState[tabKey] || '';
}

function applySearch(tabKey, searchValue) {
    const normalizedSearchValue = String(searchValue ?? '').trim();
    tabSearchState[tabKey] = normalizedSearchValue;
    tabPaginationState[tabKey] = {
        page: 1,
        totalPages: 1
    };
    loadClients(tabKey);
}

function applyFilter(tabKey) {
    tabFilterState[tabKey] = {
        program: filterProgramSelect ? String(filterProgramSelect.value || '').trim() : '',
        barangay: filterBarangaySelect ? String(filterBarangaySelect.value || '').trim() : '',
        age_bracket: filterAgeBracketSelect ? String(filterAgeBracketSelect.value || '').trim() : '',
        sex: filterSexSelect ? String(filterSexSelect.value || '').trim() : ''
    };
    tabPaginationState[tabKey] = {
        page: 1,
        totalPages: 1
    };
    loadClients(tabKey);
}

function clearFilter(tabKey) {
    tabFilterState[tabKey] = { program: '', barangay: '', age_bracket: '', sex: '' };
    syncFilterInputs(tabKey);
    tabPaginationState[tabKey] = {
        page: 1,
        totalPages: 1
    };
    loadClients(tabKey);
}

let searchDebounceTimer = null;

if (searchInput) {
    searchInput.addEventListener('input', () => {
        const activeTabKey = getActiveTabKey();

        window.clearTimeout(searchDebounceTimer);
        searchDebounceTimer = window.setTimeout(() => {
            applySearch(activeTabKey, searchInput.value);
        }, 250);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(searchDebounceTimer);
            applySearch(getActiveTabKey(), searchInput.value);
        }
    });
}

if (searchButton) {
    searchButton.addEventListener('click', () => {
        window.clearTimeout(searchDebounceTimer);
        applySearch(getActiveTabKey(), searchInput ? searchInput.value : '');
    });
}

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.tab;

        tabs.forEach(button => {
            button.classList.remove('active');
            button.setAttribute('aria-selected', 'false');
        });

        tabPanels.forEach(panel => {
            panel.classList.toggle('active', panel.dataset.tabContent === target);
        });

        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        setAddClientRoute();
        syncSearchInput(target);
        syncFilterInputs(target);
        updatePaginationButtons(target);
        loadClients(target);
    });
});

function setActiveTabPanel(tabKey) {
    tabPanels.forEach((panel) => {
        panel.classList.toggle('active', panel.dataset.tabContent === tabKey);
    });
}

if (applyFilterButton) {
    applyFilterButton.addEventListener('click', () => {
        applyFilter(getActiveTabKey());
    });
}

if (clearFilterButton) {
    clearFilterButton.addEventListener('click', () => {
        clearFilter(getActiveTabKey());
    });
}

tabPanels.forEach((panel) => {
    panel.addEventListener('click', async (event) => {
        const showToggle = event.target.closest('[data-action="toggle-show-options"]');
        if (showToggle) {
            event.preventDefault();
            const dropdown = showToggle.closest('.show-dropdown');
            document.querySelectorAll('.show-dropdown.open').forEach((node) => {
                if (node !== dropdown) {
                    node.classList.remove('open');
                    const button = node.querySelector('.show-dropdown-toggle');
                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (dropdown) {
                const isOpen = dropdown.classList.toggle('open');
                showToggle.setAttribute('aria-expanded', String(isOpen));
            }
            return;
        }

        const printToggle = event.target.closest('[data-action="toggle-print-options"]');
        if (printToggle) {
            event.preventDefault();
            const dropdown = printToggle.closest('.print-dropdown');
            document.querySelectorAll('.print-dropdown.open').forEach((node) => {
                if (node !== dropdown) {
                    node.classList.remove('open');
                    const button = node.querySelector('.print-dropdown-toggle');
                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (dropdown) {
                const isOpen = dropdown.classList.toggle('open');
                printToggle.setAttribute('aria-expanded', String(isOpen));
            }
            return;
        }

        const scanToggle = event.target.closest('[data-action="toggle-scan-options"]');
        if (scanToggle) {
            event.preventDefault();
            const dropdown = scanToggle.closest('.scan-dropdown');
            document.querySelectorAll('.scan-dropdown.open').forEach((node) => {
                if (node !== dropdown) {
                    node.classList.remove('open');
                    const button = node.querySelector('.scan-dropdown-toggle');
                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (dropdown) {
                const isOpen = dropdown.classList.toggle('open');
                scanToggle.setAttribute('aria-expanded', String(isOpen));
            }
            return;
        }

        const trigger = event.target.closest('[data-action="scan-copy"]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        await renderScanModal({
            controlNumber: String(trigger.dataset.controlNumber || ''),
            program: String(trigger.dataset.program || ''),
            fullName: String(trigger.dataset.fullName || ''),
        });
    });
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('.show-dropdown')) {
        document.querySelectorAll('.show-dropdown.open').forEach((node) => {
            node.classList.remove('open');
            const button = node.querySelector('.show-dropdown-toggle');
            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (!event.target.closest('.print-dropdown')) {
        document.querySelectorAll('.print-dropdown.open').forEach((node) => {
            node.classList.remove('open');
            const button = node.querySelector('.print-dropdown-toggle');
            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (!event.target.closest('.scan-dropdown')) {
        document.querySelectorAll('.scan-dropdown.open').forEach((node) => {
            node.classList.remove('open');
            const button = node.querySelector('.scan-dropdown-toggle');
            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }
});

if (scanGenerateQrAgainButton) {
    scanGenerateQrAgainButton.addEventListener('click', async () => {
        if (!activeScanClient) {
            return;
        }
        await renderScanModal(activeScanClient, true);
    });
}

if (scanCloseModalButton) {
    scanCloseModalButton.addEventListener('click', closeScanModal);
}

if (scanCopyModal) {
    scanCopyModal.addEventListener('click', (event) => {
        if (event.target === scanCopyModal) {
            closeScanModal();
        }
    });
}

if (addClientBtn) {
    addClientBtn.addEventListener('click', () => {
        if (addClientDirectRoute && addClientDirectRoute !== '#') {
            window.location.href = addClientDirectRoute;
        }
    });
}

prevButton.addEventListener('click', () => {
    const activeTabKey = getActiveTabKey();
    const pagination = tabPaginationState[activeTabKey] || { page: 1, totalPages: 1 };

    if (pagination.page <= 1) {
        return;
    }

    tabPaginationState[activeTabKey] = {
        ...pagination,
        page: pagination.page - 1
    };

    loadClients(activeTabKey);
});

nextButton.addEventListener('click', () => {
    const activeTabKey = getActiveTabKey();
    const pagination = tabPaginationState[activeTabKey] || { page: 1, totalPages: 1 };

    if (pagination.page >= pagination.totalPages) {
        return;
    }

    tabPaginationState[activeTabKey] = {
        ...pagination,
        page: pagination.page + 1
    };

    loadClients(activeTabKey);
});

const initialTabKey = getActiveTabKey();
setActiveTabPanel(initialTabKey);
setAddClientRoute();
syncSearchInput(initialTabKey);
syncFilterInputs(initialTabKey);
updatePaginationButtons(initialTabKey);
loadClients(initialTabKey);
