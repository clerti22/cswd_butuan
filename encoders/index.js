const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');

openBtn?.addEventListener('click', () => { sidebar?.classList.add('open'); overlay?.classList.add('active'); });
closeBtn?.addEventListener('click', () => { sidebar?.classList.remove('open'); overlay?.classList.remove('active'); });
overlay?.addEventListener('click', () => { sidebar?.classList.remove('open'); overlay?.classList.remove('active'); });

let clientsData = [];
let chartBarangays = [];
let recentActivities = [];
let dashboardChart = null;

const tableHeadRow = document.getElementById('tableHeadRow');
const tableBody = document.getElementById('tableBody');
const searchInput = document.getElementById('searchInput');
const btnSearch = document.getElementById('btnSearch');
const chartContainer = document.getElementById('chartContainer');
const recentActivitiesContainer = document.getElementById('recentActivities');

function sanitizeText(value, fallback = 'N/A') {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text === '' ? fallback : text;
}

function setStatusValue(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = Number(value || 0).toString();
}

function renderTable() {
  const query = (searchInput?.value || '').trim().toLowerCase();
  tableHeadRow.innerHTML = '';
  tableBody.innerHTML = '';
  ['Control Number', 'Full Name', 'Age', 'Sex', 'Barangay', 'Contact Details'].forEach((h) => {
    const th = document.createElement('th');
    th.textContent = h;
    tableHeadRow.appendChild(th);
  });

  clientsData
    .filter((row) =>
      String(row.control_number || '').toLowerCase().includes(query)
      || String(row.full_name || '').toLowerCase().includes(query)
      || String(row.barangay || '').toLowerCase().includes(query)
      || String(row.contact_details || '').toLowerCase().includes(query)
    )
    .forEach((row) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${sanitizeText(row.control_number)}</td><td>${sanitizeText(row.full_name)}</td><td>${sanitizeText(row.age)}</td><td>${sanitizeText(row.sex)}</td><td>${sanitizeText(row.barangay)}</td><td>${sanitizeText(row.contact_details)}</td>`;
      tableBody.appendChild(tr);
    });
}

function renderNotifications() {
  recentActivitiesContainer.innerHTML = '';
  if (!recentActivities.length) {
    recentActivitiesContainer.innerHTML = '<div class="notification-card empty"><div class="notification-content"><div class="notification-title">No notifications available.</div></div></div>';
    return;
  }

  recentActivities.forEach((item) => {
    const card = document.createElement('article');
    card.className = 'notification-card compact';
    card.innerHTML = `<div class="notification-content"><div class="notification-title"><strong>${sanitizeText(item.name, 'Unknown User')}</strong> ${sanitizeText(item.activity, 'performed an activity')}</div><div class="notification-subline">Program: ${sanitizeText(item.program)} · ${sanitizeText(item.formatted_created_at, '--')}</div></div>`;
    recentActivitiesContainer.appendChild(card);
  });
}

function renderChart() {
  dashboardChart = new CanvasJS.Chart('chartContainer', {
    animationEnabled: true,
    theme: 'light1',
    axisY: { includeZero: true },
    data: [{
      type: 'column',
      dataPoints: chartBarangays.map((item) => ({ label: item.name, y: Number(item.total || 0) })),
    }],
  });
  dashboardChart.render();
}

async function loadDashboardData() {
  const response = await fetch('./get_dashboard_data.php', { credentials: 'same-origin' });
  const payload = await response.json();

  setStatusValue('beneficiariesTotal', payload.total_beneficiaries || 0);
  setStatusValue('activeSW', payload.active_social_workers || 0);
  setStatusValue('todayTotal', payload.today_total || 0);

  clientsData = Array.isArray(payload.clients) ? payload.clients : [];
  recentActivities = Array.isArray(payload.recent_activities) ? payload.recent_activities : [];
  chartBarangays = Object.entries(payload.barangay_totals || {}).map(([name, total]) => ({ name, total }));

  renderTable();
  renderNotifications();
  renderChart();
}

btnSearch?.addEventListener('click', renderTable);
searchInput?.addEventListener('input', renderTable);
window.addEventListener('load', () => { loadDashboardData().catch(console.error); });
