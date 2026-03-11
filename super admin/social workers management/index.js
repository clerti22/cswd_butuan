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
document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', (event) => {
        event.preventDefault();
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

const roleTabs = document.querySelectorAll('.role-tab');
const usersTableBody = document.getElementById('usersTableBody');
const usersTableMessage = document.getElementById('usersTableMessage');
const assignmentHeader = document.getElementById('assignmentHeader');
const addUserButton = document.getElementById('addUserButton');
const addUserDropdown = document.getElementById('addUserDropdown');

async function loadUsers(role) {
    usersTableMessage.textContent = `Loading ${role}s...`;

    try {
        const response = await fetch(`get_users.php?role=${encodeURIComponent(role)}`);

        if (!response.ok) {
            throw new Error('Unable to fetch users.');
        }

        const payload = await response.json();
        usersTableBody.innerHTML = '';

        if (!Array.isArray(payload.users) || payload.users.length === 0) {
            usersTableMessage.textContent = `No ${role} records found.`;
            return;
        }

        payload.users.forEach((user) => {
            const normalizedStatus = String(user.statuz ?? '').toLowerCase();
            let statusClass = 'disabled';

            if (normalizedStatus.includes('active')) {
                statusClass = 'active';
            } else if (normalizedStatus.includes('offline')) {
                statusClass = 'offline';
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.full_name ?? ''}</td>
                <td>${user.role ?? ''}</td>
                <td>${user.assignment ?? ''}</td>
                <td>
                    <span class="status-cell">
                        <span class="status-dot ${statusClass}"></span>
                        <span>${user.statuz ?? ''}</span>
                    </span>
                </td>
            `;
            usersTableBody.appendChild(row);
        });

        usersTableMessage.textContent = '';

        const placeholderRows = Math.max(0, 8 - payload.users.length);
        for (let index = 0; index < placeholderRows; index += 1) {
            const row = document.createElement('tr');
            row.innerHTML = '<td>&nbsp;</td><td></td><td></td><td></td>';
            usersTableBody.appendChild(row);
        }
    } catch (error) {
        usersTableBody.innerHTML = '';
        usersTableMessage.textContent = 'Failed to load user records right now.';
    }
}

function setActiveTab(selectedRole) {
    roleTabs.forEach((tab) => {
        const isSelected = tab.dataset.role === selectedRole;
        tab.classList.toggle('is-active', isSelected);
        tab.setAttribute('aria-selected', String(isSelected));
    });

    if (assignmentHeader) {
        assignmentHeader.textContent = selectedRole === 'admin' ? 'Division' : 'Program';
    }

    loadUsers(selectedRole);
}

roleTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        setActiveTab(tab.dataset.role);
    });
});

if (addUserButton && addUserDropdown) {
    addUserButton.addEventListener('click', () => {
        const expanded = addUserButton.getAttribute('aria-expanded') === 'true';
        addUserButton.setAttribute('aria-expanded', String(!expanded));
        addUserDropdown.hidden = expanded;
    });

    document.addEventListener('click', (event) => {
        if (!addUserButton.contains(event.target) && !addUserDropdown.contains(event.target)) {
            addUserButton.setAttribute('aria-expanded', 'false');
            addUserDropdown.hidden = true;
        }
    });
}

setActiveTab('admin');

