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

const metricElements = {
    total_clients: document.getElementById('totalClientsValue'),
    served_this_week: document.getElementById('servedThisWeekValue'),
    served_this_month: document.getElementById('servedThisMonthValue'),
    served_today: document.getElementById('servedTodayValue')
};

function formatCount(value) {
    return Number(value || 0).toLocaleString();
}

function setMetricValues(data) {
    Object.entries(metricElements).forEach(([key, element]) => {
        if (!element) {
            return;
        }

        element.textContent = formatCount(data[key]);
    });
}

async function loadReportTotals() {
    try {
        const response = await fetch('get_report_totals.php');
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load report totals.');
        }

        setMetricValues(payload.data || {});
    } catch (error) {
        console.error('Failed to fetch report totals:', error);
        setMetricValues({});
    }
}

document.addEventListener('DOMContentLoaded', loadReportTotals);
