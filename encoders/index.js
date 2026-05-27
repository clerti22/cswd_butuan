const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');

function openSidebar() {
  sidebar?.classList.add('open');
  overlay?.classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  sidebar?.classList.remove('open');
  overlay?.classList.remove('active');
  document.body.style.overflow = '';
}
openBtn?.addEventListener('click', openSidebar);
closeBtn?.addEventListener('click', closeSidebar);
overlay?.addEventListener('click', closeSidebar);

document.querySelectorAll('.nav-item').forEach((item) => {
  item.addEventListener('click', () => {
    if (window.innerWidth < 1024) closeSidebar();
  });
});
window.addEventListener('resize', () => {
  if (window.innerWidth >= 1024) closeSidebar();
});

const tableHeadRow = document.getElementById('tableHeadRow');
const tableBody = document.getElementById('tableBody');
const searchInput = document.getElementById('searchInput');
const btnSearch = document.getElementById('btnSearch');
const recentActivitiesContainer = document.getElementById('recentActivities');
const seeAllNotificationsLink = document.getElementById('seeAllNotifications');
const rightStack = document.querySelector('.right-stack');
const chartContainer = document.getElementById('chartContainer');

let clientsData = [];
let recentActivities = [];
let totalNotifications = 0;
let dashboardChart = null;
let barangayChart = [];

function sanitizeText(value, fallback = 'N/A') {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text === '' ? fallback : text;
}

function setStatusValue(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = Number.isFinite(Number(value)) ? String(Number(value)) : '0';
}

function renderTable() {
  if (!tableHeadRow || !tableBody) return;
  tableHeadRow.innerHTML = '';
  tableBody.innerHTML = '';

  ['Control Number', 'Full Name', 'Age', 'Sex', 'Barangay', 'Contact Details'].forEach((h) => {
    const th = document.createElement('th');
    th.textContent = h;
    tableHeadRow.appendChild(th);
  });

  const query = (searchInput?.value || '').trim().toLowerCase();
  const filtered = clientsData.filter((r) =>
    [r.control_number, r.full_name, r.barangay, r.contact_details].some((value) => sanitizeText(value, '').toLowerCase().includes(query))
  );

  filtered.forEach((row) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${sanitizeText(row.control_number)}</td><td>${sanitizeText(row.full_name)}</td><td>${sanitizeText(row.age)}</td><td>${sanitizeText(row.sex)}</td><td>${sanitizeText(row.barangay)}</td><td>${sanitizeText(row.contact_details)}</td>`;
    tableBody.appendChild(tr);
  });
}

function renderRecentActivities() {
  if (!recentActivitiesContainer) return;
  recentActivitiesContainer.innerHTML = '';
  if (seeAllNotificationsLink) seeAllNotificationsLink.hidden = totalNotifications <= 3;

  if (!recentActivities.length) {
    recentActivitiesContainer.innerHTML = '<div class="notification-card empty"><div class="notification-icon blue"><i class="bi bi-info-circle"></i></div><div class="notification-content"><div class="notification-title">No notifications available.</div><div class="notification-subline">System · --</div></div></div>';
    return;
  }

  recentActivities.forEach((item, index) => {
    const color = ['blue', 'red', 'green'][index % 3];
    const card = document.createElement('article');
    card.className = 'notification-card compact';
    card.innerHTML = `<div class="notification-icon ${color}"><i class="bi bi-clock-history"></i></div><div class="notification-content"><div class="notification-title"><strong>${sanitizeText(item.name, 'Unknown User')}</strong> ${sanitizeText(item.activity, 'performed an activity')}</div><div class="notification-meta"><span>Role: ${sanitizeText(item.role)}</span><span>Program: ${sanitizeText(item.program)}</span><span>Division: ${sanitizeText(item.division)}</span></div><div class="notification-subline">Time: ${sanitizeText(item.formatted_created_at, sanitizeText(item.created_at, '--'))}</div></div>`;
    recentActivitiesContainer.appendChild(card);
  });
}

function renderChart() {
  if (!chartContainer) return;
  const rightStackHeight = rightStack?.offsetHeight || 0;
  if (rightStackHeight > 0) chartContainer.style.height = `${Math.max(370, rightStackHeight - 16)}px`;

  dashboardChart = new CanvasJS.Chart('chartContainer', {
    animationEnabled: true,
    exportEnabled: true,
    theme: 'light1',
    axisY: { includeZero: true },
    data: [{ type: 'column', indexLabelFontColor: '#5A5757', indexLabelFontSize: 14, indexLabelPlacement: 'outside', dataPoints: barangayChart }],
  });
  dashboardChart.render();
}

async function loadDashboardData() {
  try {
    const response = await fetch('./get_dashboard_data.php', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error(`Unable to fetch dashboard data: ${response.status}`);
    const payload = await response.json();

    setStatusValue('todayTotal', payload?.today_total || 0);
    setStatusValue('programTotal', payload?.program_total || 0);
    const label = document.getElementById('programLabel');
    if (label) label.textContent = sanitizeText(payload?.program, 'Program');

    clientsData = Array.isArray(payload?.clients) ? payload.clients.slice(0, 5) : [];
    barangayChart = Array.isArray(payload?.barangay_chart) && payload.barangay_chart.length ? payload.barangay_chart : [{ label: 'No Data', y: 0 }];
    recentActivities = Array.isArray(payload?.recent_activities) ? payload.recent_activities : [];
    totalNotifications = Number(payload?.notification_count) || 0;

    renderTable();
    renderRecentActivities();
    renderChart();
  } catch (error) {
    console.error(error);
    renderTable();
    renderRecentActivities();
    renderChart();
  }
}

btnSearch?.addEventListener('click', renderTable);
searchInput?.addEventListener('input', renderTable);
window.addEventListener('load', loadDashboardData);
window.addEventListener('resize', () => dashboardChart?.render());
