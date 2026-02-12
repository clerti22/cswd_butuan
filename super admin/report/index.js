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

const clientsChartTitle = document.getElementById('clientsChartTitle');
const barangayTab = document.getElementById('barangayTab');
const programTab = document.getElementById('programTab');

let clientsBreakdownChart = null;
let genderChart = null;
let chartPayload = null;

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

function createClientsChart(labels, values, chartLabel, barColor) {
    const chartCanvas = document.getElementById('clientsBreakdownChart');

    if (!chartCanvas) {
        return;
    }

    if (clientsBreakdownChart) {
        clientsBreakdownChart.destroy();
    }

    clientsBreakdownChart = new Chart(chartCanvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: chartLabel,
                data: values,
                backgroundColor: barColor,
                borderColor: barColor,
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 70,
                        minRotation: 45,
                        autoSkip: true
                    }
                }
            }
        }
    });
}

function createGenderChart(labels, values) {
    const chartCanvas = document.getElementById('genderChart');

    if (!chartCanvas) {
        return;
    }

    if (genderChart) {
        genderChart.destroy();
    }

    genderChart = new Chart(chartCanvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Total Served Clients by Gender',
                data: values,
                backgroundColor: ['#3b82f6', '#ec4899'],
                borderColor: ['#3b82f6', '#ec4899'],
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 70
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function switchClientsTab(mode) {
    if (!chartPayload) {
        return;
    }

    const isBarangay = mode === 'barangay';

    barangayTab.classList.toggle('active', isBarangay);
    programTab.classList.toggle('active', !isBarangay);

    if (isBarangay) {
        clientsChartTitle.textContent = 'Total Served Clients per Barangay chart';
        createClientsChart(
            chartPayload.barangay.labels,
            chartPayload.barangay.values,
            'Clients per Barangay',
            '#60a5fa'
        );
        return;
    }

    clientsChartTitle.textContent = 'Total Served Clients per Program chart';
    createClientsChart(
        chartPayload.program.labels,
        chartPayload.program.values,
        'Clients per Program',
        '#34d399'
    );
}

async function loadReportCharts() {
    try {
        const response = await fetch('get_report_chart_data.php');
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load chart data.');
        }

        chartPayload = payload.data;
        switchClientsTab('barangay');
        createGenderChart(chartPayload.gender.labels, chartPayload.gender.values);
    } catch (error) {
        console.error('Failed to fetch report chart data:', error);
    }
}

barangayTab?.addEventListener('click', () => switchClientsTab('barangay'));
programTab?.addEventListener('click', () => switchClientsTab('program'));

document.addEventListener('DOMContentLoaded', () => {
    loadReportTotals();
    loadReportCharts();
});
