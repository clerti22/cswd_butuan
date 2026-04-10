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

document.querySelectorAll('.nav-item').forEach((item) => {
  item.addEventListener('click', () => {
    if (window.innerWidth < 1024) closeSidebar();
  });
});

window.addEventListener('resize', () => {
  if (window.innerWidth >= 1024) closeSidebar();
});

const topbarTitle = document.querySelector('.topbar-title');
if (topbarTitle && window.adminDivision) {
  topbarTitle.textContent = `Dashboard (${window.adminDivision})`;
}

let clientsData = [];
let chartBarangays = [];
let recentActivities = [];
let totalNotifications = 0;

const btnClients = document.getElementById('btnClients');
const tableHeadRow = document.getElementById('tableHeadRow');
const tableBody = document.getElementById('tableBody');
const searchInput = document.getElementById('searchInput');
const btnSearch = document.getElementById('btnSearch');
const recentActivitiesContainer = document.getElementById('recentActivities');
const seeAllNotificationsLink = document.getElementById('seeAllNotifications');
const rightStack = document.querySelector('.right-stack');
const chartContainer = document.getElementById('chartContainer');

let dashboardChart = null;

function sanitizeText(value, fallback = 'N/A') {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text === '' ? fallback : text;
}

function toLower(value) {
  return sanitizeText(value, '').toLowerCase();
}

function setStatusValue(id, value) {
  const element = document.getElementById(id);
  if (!element) return;
  element.textContent = Number.isFinite(Number(value)) ? String(Number(value)) : '0';
}

function setActiveTable() {
  btnClients?.classList.add('active');
  if (searchInput) {
    searchInput.value = '';
    searchInput.placeholder = '';
  }
  renderTable();
}

function renderTable() {
  if (!tableHeadRow || !tableBody) return;
  tableHeadRow.innerHTML = '';
  tableBody.innerHTML = '';

  const query = (searchInput?.value || '').trim().toLowerCase();

  ['Control Number', 'Full Name', 'Age', 'Sex', 'Barangay', 'Contact Details'].forEach((h) => {
    const th = document.createElement('th');
    th.textContent = h;
    tableHeadRow.appendChild(th);
  });

  clientsData
    .filter((r) =>
      toLower(r.control_number).includes(query)
      || toLower(r.full_name).includes(query)
      || toLower(r.barangay).includes(query)
      || toLower(r.contact_details).includes(query)
    )
    .forEach((row) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${sanitizeText(row.control_number)}</td>
        <td>${sanitizeText(row.full_name)}</td>
        <td>${sanitizeText(row.age)}</td>
        <td>${sanitizeText(row.sex)}</td>
        <td>${sanitizeText(row.barangay)}</td>
        <td>${sanitizeText(row.contact_details)}</td>
      `;
      tableBody.appendChild(tr);
    });
}

function renderRecentActivities() {
  if (!recentActivitiesContainer) return;
  recentActivitiesContainer.innerHTML = '';

  if (seeAllNotificationsLink) {
    seeAllNotificationsLink.hidden = true;
  }

  if (!recentActivities.length) {
    const empty = document.createElement('div');
    empty.className = 'notification-card empty';
    empty.innerHTML = `
      <div class="notification-icon blue"><i class="bi bi-info-circle"></i></div>
      <div class="notification-content">
        <div class="notification-title">No notifications available.</div>
        <div class="notification-subline">System · --</div>
      </div>
    `;
    recentActivitiesContainer.appendChild(empty);
    return;
  }

  const iconClasses = ['blue', 'red', 'green'];
  recentActivities.forEach((item, index) => {
    const card = document.createElement('article');
    card.className = 'notification-card compact';
    card.innerHTML = `
      <div class="notification-icon ${iconClasses[index % iconClasses.length]}"><i class="bi bi-clock-history"></i></div>
      <div class="notification-content">
        <div class="notification-title"><strong>${sanitizeText(item.name, 'Unknown User')}</strong> ${sanitizeText(item.activity, 'performed an activity')}</div>
        <div class="notification-meta">
          <span>Role: ${sanitizeText(item.role)}</span>
          <span>Program: ${sanitizeText(item.program)}</span>
          <span>Division: ${sanitizeText(item.division)}</span>
        </div>
        <div class="notification-subline">Time: ${sanitizeText(item.formatted_created_at, sanitizeText(item.created_at, '--'))}</div>
      </div>
    `;
    recentActivitiesContainer.appendChild(card);
  });
}

function renderChart() {
  if (!chartContainer) return;

  const rightStackHeight = rightStack?.offsetHeight || 0;
  if (rightStackHeight > 0) {
    chartContainer.style.height = `${Math.max(370, rightStackHeight - 16)}px`;
  }

  dashboardChart = new CanvasJS.Chart('chartContainer', {
    animationEnabled: true,
    exportEnabled: true,
    theme: 'light1',
    axisY: { includeZero: true },
    data: [{
      type: 'column',
      indexLabelFontColor: '#5A5757',
      indexLabelFontSize: 14,
      indexLabelPlacement: 'outside',
      dataPoints: chartBarangays.map((item) => ({
        label: item.name,
        y: Number(item.total) || 0,
        indexLabel: String(Number(item.total) || 0),
      })),
    }],
  });

  dashboardChart.render();
}

function renderBeneficiaryStatus(total, label) {
  const title = document.getElementById('beneficiaryCardTitle');
  if (title) {
    title.textContent = `Total Beneficiaries (${label || 'Assigned Scope'})`;
  }
  setStatusValue('beneficiariesTotal', total);
}

async function loadDashboardData() {
  try {
    const response = await fetch('./get_dashboard_data.php', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      throw new Error(`Unable to fetch dashboard data: ${response.status}`);
    }

    const payload = await response.json();
    const totalBeneficiaries = Number(payload?.total_beneficiaries || 0);
    renderBeneficiaryStatus(totalBeneficiaries, payload?.beneficiary_scope_label || 'Assigned Scope');
    setStatusValue('activeSW', payload?.active_social_workers || 0);
    setStatusValue('todayTotal', payload?.today_total || 0);

    clientsData = Array.isArray(payload?.clients) ? payload.clients.slice(0, 5) : [];
    recentActivities = Array.isArray(payload?.recent_activities) ? payload.recent_activities : [];
    totalNotifications = Number(payload?.notification_count) || 0;

    chartBarangays = Object.entries(payload?.barangay_totals || {})
      .map(([name, total]) => ({ name, total: Number(total) || 0 }))
      .sort((a, b) => b.total - a.total);

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

btnClients?.addEventListener('click', () => setActiveTable());
btnSearch?.addEventListener('click', renderTable);
searchInput?.addEventListener('input', renderTable);

window.addEventListener('load', () => {
  setActiveTable();
  loadDashboardData();
});

window.addEventListener('resize', () => {
  if (!dashboardChart || !chartContainer) return;
  const rightStackHeight = rightStack?.offsetHeight || 0;
  if (rightStackHeight > 0) {
    chartContainer.style.height = `${Math.max(370, rightStackHeight - 16)}px`;
  }
  dashboardChart.render();
});
