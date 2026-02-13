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

openBtn?.addEventListener('click', openSidebar);
closeBtn?.addEventListener('click', closeSidebar);
overlay?.addEventListener('click', closeSidebar);

document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', (event) => {
        event.preventDefault();
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

const clientsChartTitle = document.getElementById('clientsChartTitle');
const barangayTab = document.getElementById('barangayTab');
const programTab = document.getElementById('programTab');
const genderLegend = document.getElementById('genderLegend');

let clientsChart = null;
let genderChart = null;
let chartData = null;

function formatNumber(value) {
    return Number(value || 0).toLocaleString();
}

function setMetricValues(data) {
    Object.entries(metricElements).forEach(([key, element]) => {
        if (!element) {
            return;
        }

        element.textContent = formatNumber(data[key]);
    });
}

async function loadReportTotals() {
    try {
        const response = await fetch('get_report_totals.php');
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load totals.');
        }

        setMetricValues(payload.data || {});
    } catch (error) {
        console.error('Failed to load totals:', error);
        setMetricValues({});
    }
}

function renderClientsChart(labels, values, color) {
    const canvas = document.getElementById('clientsBreakdownChart');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    if (clientsChart) {
        clientsChart.destroy();
    }

    clientsChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: color,
                borderColor: color,
                borderWidth: 1,
                borderRadius: 2
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
                    },
                    grid: {
                        color: '#d2d2d2'
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 70,
                        minRotation: 45,
                        autoSkip: true
                    },
                    grid: {
                        color: '#d2d2d2'
                    }
                }
            }
        }
    });
}

function renderGenderLegend(labels, values, colors) {
    if (!genderLegend) {
        return;
    }

    genderLegend.innerHTML = labels.map((label, index) => (
        `<span class="legend-item"><span class="legend-dot" style="background:${colors[index]}"></span>${label}</span>`
    )).join('');

    const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
    if (!total) {
        return;
    }

    genderLegend.innerHTML += labels.map((label, index) => {
        const percentage = ((Number(values[index]) / total) * 100).toFixed(1);
        return `<span class="legend-item">${percentage}%</span>`;
    }).join('');
}

function renderGenderChart(labels, values) {
    const canvas = document.getElementById('genderChart');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const colors = ['#1f3ff2', '#ec16d7'];

    if (genderChart) {
        genderChart.destroy();
    }

    genderChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderColor: '#ffffff',
                borderWidth: 1,
                cutout: '76%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    renderGenderLegend(labels, values, colors);
}

function activateTab(mode) {
    if (!chartData) {
        return;
    }

    const isBarangay = mode === 'barangay';
    barangayTab?.classList.toggle('active', isBarangay);
    programTab?.classList.toggle('active', !isBarangay);

    if (isBarangay) {
        clientsChartTitle.textContent = 'Total Served Clients per Barangay Chart';
        renderClientsChart(chartData.barangay.labels, chartData.barangay.values, '#6f2ad4');
        return;
    }

    clientsChartTitle.textContent = 'Total Served Clients per Program chart';
    renderClientsChart(chartData.program.labels, chartData.program.values, '#6f2ad4');
}

async function loadChartData() {
    try {
        const response = await fetch('get_report_chart_data.php');
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load chart data.');
        }

        chartData = payload.data;
        activateTab('barangay');
        renderGenderChart(chartData.gender.labels, chartData.gender.values);
    } catch (error) {
        console.error('Failed to load chart data:', error);
    }
}

barangayTab?.addEventListener('click', () => activateTab('barangay'));
programTab?.addEventListener('click', () => activateTab('program'));

document.addEventListener('DOMContentLoaded', () => {
    loadReportTotals();
    loadChartData();
});
