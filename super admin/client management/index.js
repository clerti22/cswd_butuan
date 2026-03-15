const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');

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
    dpsd: null,
    pssd: null,
    rpsd: null
};

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

function createClientRow(client) {
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
                <button class="btn btn-sm" type="button">Show</button>
                <button class="btn btn-sm" type="button">Edit</button>
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

async function loadClients(tabKey) {
    if (clientDataCache[tabKey] !== null) {
        renderClients(tabKey, clientDataCache[tabKey]);
        return;
    }

    const panel = document.querySelector(`.tab-panel[data-tab-content="${tabKey}"]`);
    if (panel) {
        panel.innerHTML = '<tr><td colspan="10">Loading...</td></tr>';
    }

    try {
        const response = await fetch(`get_clients.php?tab=${encodeURIComponent(tabKey)}`);
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Unable to load clients.');
        }

        clientDataCache[tabKey] = Array.isArray(payload.clients) ? payload.clients : [];
        renderClients(tabKey, clientDataCache[tabKey]);
    } catch (error) {
        if (panel) {
            panel.innerHTML = '<tr><td colspan="10">Unable to load data.</td></tr>';
        }
    }
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
        loadClients(target);
    });
});

addClientBtn.addEventListener('click', () => {
    const isOpen = addClientMenu.classList.toggle('open');
    addClientBtn.setAttribute('aria-expanded', String(isOpen));
});

document.addEventListener('click', event => {
    if (!addClientMenu.contains(event.target) && !addClientBtn.contains(event.target)) {
        addClientMenu.classList.remove('open');
        addClientBtn.setAttribute('aria-expanded', 'false');
    }
});

addClientMenu.addEventListener('click', event => {
    const menuItem = event.target.closest('.menu-item');

    if (!menuItem) {
        return;
    }

    const route = menuItem.dataset.route;

    if (!route || route === '#') {
        return;
    }

    window.location.href = route;
});

setAddClientMenu('dpsd');
loadClients('dpsd');
