const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');
const searchInput = document.getElementById('clientSearchInput');
const searchButton = document.getElementById('clientSearchButton');

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
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
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
const addClientMenu = document.getElementById('addClientMenu');
const clientDataCache = {
    dpsd: {},
    pssd: {},
    rpsd: {}
};

const tabPaginationState = {
    dpsd: { page: 1, totalPages: 1 },
    pssd: { page: 1, totalPages: 1 },
    rpsd: { page: 1, totalPages: 1 }
};

const tabSearchState = {
    dpsd: '',
    pssd: '',
    rpsd: ''
};

const prevButton = document.querySelector('.pagination .pager-btn:first-child');
const nextButton = document.querySelector('.pagination .pager-btn:last-child');

const addClientOptions = {
    dpsd: ['PYAP', 'Solo Parent', 'PWUD'],
    pssd: ['Senior Citizen', 'PWD'],
    rpsd: ['VAWC', 'CSAE', 'CAR', 'CICL', 'CIU']
};

const addClientRoutes = {
    dpsd: {
        'PYAP': 'dpsd forms/pyap/index.php',
        'Solo Parent': 'dpsd forms/solo parent/index.php',
        'PWUD': 'dpsd forms/pwud/index.php'
    },
    pssd: {
        'Senior Citizen': 'pssd forms/senior citizen/index.php',
        'PWD': 'pssd forms/pwd/index.php'
    },
    rpsd: {
        'VAWC': 'rpsd forms/vawc/index.php',
        'CSAE': 'rpsd forms/csae/index.php',
        'CAR': 'rpsd forms/car/index.php',
        'CICL': 'rpsd forms/cicl/index.php',
        'CIU': 'rpsd forms/ciu/index.php'
    }
};

function setAddClientMenu(tabKey) {
    const options = addClientOptions[tabKey] || [];
    const routes = addClientRoutes[tabKey] || {};

    addClientMenu.innerHTML = options
        .map(option => {
            const route = routes[option] || '#';
            return `<button type="button" class="menu-item" role="menuitem" data-route="${route}">${option}</button>`;
        })
        .join('');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getShowRoute(client) {
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

    const baseRoute = routesByTable[sourceTable];
    if (!baseRoute) {
        return '#';
    }

    return `${baseRoute}?id=${encodeURIComponent(String(keyId))}`;
}

function createClientRow(client) {
    const showRoute = getShowRoute(client);
    const showButton = showRoute === '#'
        ? '<button class="btn btn-sm btn-show" type="button" disabled title="Show is currently available for PWUD, PYAP, Solo Parent, PWD, Senior Citizen, CAR, CICL, CSAE, and VAWC records only.">Show</button>'
        : `<a class="btn btn-sm btn-show" href="${escapeHtml(showRoute)}">Print</a>`;

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
                ${showButton}
                <button class="btn btn-sm" type="button">Show</button>
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

function getActiveTabKey() {
    const activeTab = document.querySelector('.tab.active');
    return activeTab ? activeTab.dataset.tab : 'dpsd';
}

function getCacheKey(tabKey) {
    const pagination = tabPaginationState[tabKey] || { page: 1 };
    const searchTerm = tabSearchState[tabKey] || '';
    return `${pagination.page}::${searchTerm}`;
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
    const cacheKey = getCacheKey(tabKey);

    if (clientDataCache[tabKey] && clientDataCache[tabKey][cacheKey]) {
        renderClients(tabKey, clientDataCache[tabKey][cacheKey]);
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
            search: searchTerm
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

        renderClients(tabKey, clients);
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
        setAddClientMenu(target);
        addClientMenu.classList.remove('open');
        addClientBtn.setAttribute('aria-expanded', 'false');
        syncSearchInput(target);
        updatePaginationButtons(target);
        loadClients(target);
    });
});

addClientBtn.addEventListener('click', () => {
    const isOpen = addClientMenu.classList.toggle('open');
    addClientBtn.setAttribute('aria-expanded', String(isOpen));
    addClientMenu.setAttribute('aria-hidden', String(!isOpen));
});

document.addEventListener('click', (event) => {
    if (!addClientBtn.contains(event.target) && !addClientMenu.contains(event.target)) {
        addClientMenu.classList.remove('open');
        addClientBtn.setAttribute('aria-expanded', 'false');
        addClientMenu.setAttribute('aria-hidden', 'true');
    }
});

addClientMenu.addEventListener('click', (event) => {
    const menuItem = event.target.closest('.menu-item');
    if (!menuItem) {
        return;
    }

    const route = menuItem.dataset.route || '#';
    addClientMenu.classList.remove('open');
    addClientBtn.setAttribute('aria-expanded', 'false');
    addClientMenu.setAttribute('aria-hidden', 'true');

    if (route !== '#') {
        window.location.href = route;
    }
});

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

setAddClientMenu('dpsd');
syncSearchInput('dpsd');
updatePaginationButtons('dpsd');
loadClients('dpsd');
