// ===== SIDEBAR (your original) =====
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

// Close sidebar when clicking nav items on mobile
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth < 1024) closeSidebar();
  });
});

// Handle window resize
window.addEventListener('resize', () => {
  if (window.innerWidth >= 1024) closeSidebar();
});


// ===== DASHBOARD DATA =====
let clientsData = [];
let socialWorkersData = [];
let rankingDivisions = [];
let recentActivities = [];
let totalNotifications = 0;

// ===== TABLE TOGGLE + RENDER =====
const btnClients = document.getElementById('btnClients');
const btnSW = document.getElementById('btnSW');
const tableHeadRow = document.getElementById('tableHeadRow');
const tableBody = document.getElementById('tableBody');
const searchInput = document.getElementById('searchInput');
const btnSearch = document.getElementById('btnSearch');
const recentActivitiesContainer = document.getElementById('recentActivities');
const seeAllNotificationsLink = document.getElementById('seeAllNotifications');
const rightStack = document.querySelector('.right-stack');
const chartContainer = document.getElementById('chartContainer');
let dashboardChart = null;

let currentTable = 'clients';

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

function setActiveTable(next) {
  currentTable = next;

  btnClients.classList.toggle('active', next === 'clients');
  btnSW.classList.toggle('active', next === 'sw');

  searchInput.value = '';
  searchInput.placeholder = '';

  renderTable();
}

function renderTable() {
  if (!tableHeadRow || !tableBody) return;

  tableHeadRow.innerHTML = '';
  tableBody.innerHTML = '';

  const query = (searchInput.value || '').trim().toLowerCase();

  if (currentTable === 'clients') {
    const headers = ['Control Number', 'Full Name', 'Age', 'Sex', 'Barangay', 'Contact Details'];
    headers.forEach(h => {
      const th = document.createElement('th');
      th.textContent = h;
      tableHeadRow.appendChild(th);
    });

    const filtered = clientsData.filter(r =>
      toLower(r.control_number).includes(query) ||
      toLower(r.full_name).includes(query) ||
      toLower(r.barangay).includes(query) ||
      toLower(r.contact_details).includes(query)
    );

    filtered.forEach(row => {
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
  } else {
    const headers = ['Full Name', 'Email', 'Phone Number'];
    headers.forEach(h => {
      const th = document.createElement('th');
      th.textContent = h;
      tableHeadRow.appendChild(th);
    });

    const filtered = socialWorkersData.filter(r =>
      toLower(r.full_name).includes(query) ||
      toLower(r.email).includes(query) ||
      toLower(r.phone_number).includes(query)
    );

    filtered.forEach(row => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${sanitizeText(row.full_name)}</td>
        <td>${sanitizeText(row.email)}</td>
        <td>${sanitizeText(row.phone_number)}</td>
      `;
      tableBody.appendChild(tr);
    });
  }
}

btnClients?.addEventListener('click', () => setActiveTable('clients'));
btnSW?.addEventListener('click', () => setActiveTable('sw'));
btnSearch?.addEventListener('click', renderTable);
searchInput?.addEventListener('input', renderTable);


// ===== RANKINGS TOGGLE =====
const tabDivisions = document.getElementById('tabDivisions');
const rankList = document.getElementById('rankList');

function renderRankings() {
  if (!rankList) return;
  rankList.innerHTML = '';

  rankingDivisions.forEach(item => {
    const div = document.createElement('div');
    div.className = 'rank-item';
    div.innerHTML = `
      <div class="rank-icon"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="rank-name">${sanitizeText(item.name, 'Unknown Division')}</div>
        <div class="rank-sub">${Number(item.total) || 0} Clients</div>
      </div>
    `;
    rankList.appendChild(div);
  });
}

function renderRecentActivities() {
  if (!recentActivitiesContainer) return;

  recentActivitiesContainer.innerHTML = '';
  if (seeAllNotificationsLink) {
    seeAllNotificationsLink.hidden = totalNotifications <= 3;
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
    const notificationCard = document.createElement('article');
    notificationCard.className = 'notification-card compact';
    notificationCard.innerHTML = `
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
    recentActivitiesContainer.appendChild(notificationCard);
  });

}

function renderChart() {
  if (!chartContainer) return;

  const rightStackHeight = rightStack?.offsetHeight || 0;
  if (rightStackHeight > 0) {
    chartContainer.style.height = `${Math.max(370, rightStackHeight - 16)}px`;
  }

  const dpsd = Number(document.getElementById('dpsdTotal')?.textContent || 0);
  const pssd = Number(document.getElementById('pssdTotal')?.textContent || 0);
  const rpsd = Number(document.getElementById('rpsdTotal')?.textContent || 0);
  const cod = Number(document.getElementById('codTotal')?.textContent || 0);

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
      dataPoints: [
        { label: 'RPSD', y: rpsd, indexLabel: String(rpsd) },
        { label: 'COD', y: cod, indexLabel: String(cod) },
        { label: 'PSSD', y: pssd, indexLabel: String(pssd) },
        { label: 'DPSD', y: dpsd, indexLabel: String(dpsd) },
      ]
    }]
  });

  dashboardChart.render();
}

async function loadDashboardData() {
  try {
    const response = await fetch('./get_dashboard_data.php', {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Unable to fetch dashboard data: ${response.status}`);
    }

    const payload = await response.json();

    const totals = payload?.totals || {};
    setStatusValue('dpsdTotal', totals.dpsd || 0);
    setStatusValue('pssdTotal', totals.pssd || 0);
    setStatusValue('rpsdTotal', totals.rpsd || 0);
    setStatusValue('codTotal', totals.cod || 0);
    setStatusValue('activeSW', payload?.active_social_workers || 0);
    setStatusValue('todayTotal', payload?.today_total || 0);

    clientsData = Array.isArray(payload?.clients) ? payload.clients.slice(0, 5) : [];
    socialWorkersData = Array.isArray(payload?.social_workers) ? payload.social_workers.slice(0, 5) : [];
    recentActivities = Array.isArray(payload?.recent_activities) ? payload.recent_activities : [];
    totalNotifications = Number(payload?.notification_count) || 0;

    rankingDivisions = [
      { name: 'DPSD Division', total: Number(totals.dpsd) || 0 },
      { name: 'RPSD Division', total: Number(totals.rpsd) || 0 },
      { name: 'PSSD Division', total: Number(totals.pssd) || 0 },
      { name: 'COD Division', total: Number(totals.cod) || 0 },
    ];

    renderTable();
    renderRankings();
    renderRecentActivities();
    renderChart();
  } catch (error) {
    console.error(error);
    renderTable();
    renderRankings();
    renderRecentActivities();
    renderChart();
  }
}

tabDivisions?.addEventListener('click', () => {
  tabDivisions.classList.add('active');
  renderRankings();
});

window.addEventListener('load', () => {
  setActiveTable('clients');
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
