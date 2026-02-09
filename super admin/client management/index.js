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
const addClientOptions = {
    dpsd: ['PYAP', 'Solo Parent', 'PWUD'],
    pssd: ['Senior Citizen', 'PWD'],
    rpsd: ['VAWC', 'CSAE', 'CAR', 'CICL', 'CIU']
};

const addClientRoutes = {
    dpsd: {
        'PYAP': 'dpsd forms/pyap.html',
        'Solo Parent': 'dpsd forms/soloparent.html',
        'PWUD': 'dpsd forms/pwud.php'
    },
    pssd: {
        'Senior Citizen': 'pssd forms/snrctizen.html',
        'PWD': 'pssd forms/pwd.html'
    },
    rpsd: {
        'VAWC': 'rpsd forms/vawc.html',
        'CSAE': 'rpsd forms/csae.html',
        'CAR': 'rpsd forms/car.html',
        'CICL': 'rpsd forms/cicl.html',
        'CIU': 'rpsd forms/ciu.html'
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
