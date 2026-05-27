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

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth < 1024) closeSidebar();
  });
});

window.addEventListener('resize', () => {
  if (window.innerWidth >= 1024) closeSidebar();
});

const notificationGroupsContainer = document.getElementById('notificationGroups');
const notificationTotalBadge = document.getElementById('notificationTotal');

function sanitizeText(value, fallback = 'N/A') {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text === '' ? fallback : text;
}

function renderEmptyState(message = 'No notifications available yet.') {
  if (!notificationGroupsContainer) return;
  notificationGroupsContainer.innerHTML = `<div class="empty-state">${message}</div>`;
}

function renderNotificationGroups(groups) {
  if (!notificationGroupsContainer) return;

  notificationGroupsContainer.innerHTML = '';

  if (!Array.isArray(groups) || !groups.length) {
    renderEmptyState();
    return;
  }

  groups.forEach(group => {
    const groupWrap = document.createElement('section');
    groupWrap.className = 'notification-group';

    const label = document.createElement('h3');
    label.className = 'group-label';
    label.textContent = sanitizeText(group?.label, 'Older');
    groupWrap.appendChild(label);

    const items = Array.isArray(group?.items) ? group.items : [];

    items.forEach(item => {
      const card = document.createElement('article');
      card.className = 'notification-item';
      card.innerHTML = `
        <div class="notification-icon"><i class="bi bi-bell-fill"></i></div>
        <div class="notification-main">
          <div class="notification-title"><strong>${sanitizeText(item?.name, 'Unknown User')}</strong> ${sanitizeText(item?.activity, 'performed an activity')}</div>
          <div class="notification-meta">
            <span>Role: ${sanitizeText(item?.role)}</span>
            <span>Program: ${sanitizeText(item?.program)}</span>
            <span>Division: ${sanitizeText(item?.division)}</span>
          </div>
          <div class="notification-time">Time: ${sanitizeText(item?.formatted_created_at, sanitizeText(item?.created_at, '--'))}</div>
        </div>
      `;
      groupWrap.appendChild(card);
    });

    notificationGroupsContainer.appendChild(groupWrap);
  });
}

async function loadNotifications() {
  try {
    const response = await fetch('./get_notifications.php', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch notifications: ${response.status}`);
    }

    const payload = await response.json();
    const total = Number(payload?.total) || 0;
    if (notificationTotalBadge) {
      notificationTotalBadge.textContent = `${total} total`;
    }

    renderNotificationGroups(payload?.groups);
  } catch (error) {
    console.error(error);
    if (notificationTotalBadge) {
      notificationTotalBadge.textContent = '0 total';
    }
    renderEmptyState('Unable to load notifications right now.');
  }
}

window.addEventListener('load', loadNotifications);
