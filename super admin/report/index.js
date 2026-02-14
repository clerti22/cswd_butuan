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

document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        if (window.innerWidth < 1024) {
            closeSidebar();
        }
    });
});

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

const mainChartTitle = document.getElementById('mainChartTitle');
const toggleFilterBtn = document.getElementById('toggleFilterBtn');
const chartFilterPanel = document.getElementById('chartFilterPanel');
const periodFilter = document.getElementById('periodFilter');
const referenceFilter = document.getElementById('referenceFilter');
const divisionFilter = document.getElementById('divisionFilter');
const applyFilterBtn = document.getElementById('applyFilterBtn');

let registrationChart = null;

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

function setReferenceFieldType(period) {
    if (period === 'week') {
        referenceFilter.type = 'date';
        referenceFilter.value = new Date().toISOString().split('T')[0];
    } else if (period === 'month') {
        referenceFilter.type = 'month';
        referenceFilter.value = new Date().toISOString().slice(0, 7);
    } else {
        referenceFilter.type = 'number';
        referenceFilter.min = '2000';
        referenceFilter.max = '2100';
        referenceFilter.step = '1';
        referenceFilter.value = String(new Date().getFullYear());
    }
}

function renderRegistrationChart(payload) {
    if (typeof CanvasJS === 'undefined') {
        return;
    }

    const dayLabels = payload.days || ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const divisionSeries = payload.divisions || {};
    const colors = {
        DPSD: '#f97316',
        PSSD: '#f59e0b',
        RPSD: '#16a34a'
    };

    const series = Object.entries(divisionSeries).map(([division, counts]) => ({
        type: 'line',
        name: division,
        showInLegend: true,
        lineThickness: 2,
        markerSize: 7,
        color: colors[division] || '#2563eb',
        dataPoints: dayLabels.map((day, index) => ({ label: day, y: Number(counts[index] || 0) }))
    }));

    if (!registrationChart) {
        registrationChart = new CanvasJS.Chart('mainChartContainer', {
            animationEnabled: true,
            theme: 'light1',
            axisY: {
                includeZero: true,
                title: 'Registered Clients'
            },
            axisX: {
                interval: 1,
                labelAngle: 0
            },
            legend: {
                verticalAlign: 'bottom',
                horizontalAlign: 'center'
            },
            data: []
        });
    }

    registrationChart.options.data = series;
    registrationChart.render();

    if (mainChartTitle) {
        mainChartTitle.textContent = payload.title || 'Weekly Client Registration';
    }
}

async function loadDivisionTrends() {
    const params = new URLSearchParams({
        period: periodFilter.value || 'week',
        reference: referenceFilter.value || '',
        division: divisionFilter.value || 'all'
    });

    try {
        const response = await fetch(`get_division_registration_trends.php?${params.toString()}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load weekly registration trends.');
        }

        renderRegistrationChart(payload.data || {});
    } catch (error) {
        console.error('Failed to fetch division trend data:', error);
        renderRegistrationChart({
            title: 'Weekly Client Registration',
            days: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            divisions: {
                DPSD: [0, 0, 0, 0, 0, 0, 0],
                PSSD: [0, 0, 0, 0, 0, 0, 0],
                RPSD: [0, 0, 0, 0, 0, 0, 0]
            }
        });
    }
}

toggleFilterBtn?.addEventListener('click', () => {
    chartFilterPanel.hidden = !chartFilterPanel.hidden;
});

periodFilter?.addEventListener('change', () => {
    setReferenceFieldType(periodFilter.value);
});

applyFilterBtn?.addEventListener('click', async () => {
    await loadDivisionTrends();
});

document.addEventListener('DOMContentLoaded', async () => {
    setReferenceFieldType('week');
    await loadReportTotals();
    await loadDivisionTrends();
});
