const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');
const userSearchInput = document.getElementById('userSearchInput');
const userSearchButton = document.getElementById('userSearchButton');

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
    item.addEventListener('click', () => {
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

const roleTabs = document.querySelectorAll('.role-tab');
const usersTableBody = document.getElementById('usersTableBody');
const usersTableMessage = document.getElementById('usersTableMessage');
const assignmentHeader = document.getElementById('assignmentHeader');
const usersPagination = document.getElementById('usersPagination');
const prevPageButton = document.getElementById('prevPageButton');
const nextPageButton = document.getElementById('nextPageButton');
const paginationInfo = document.getElementById('paginationInfo');
const disableCodeModal = document.getElementById('disableCodeModal');
const disableConfirmModal = document.getElementById('disableConfirmModal');
const showUserModal = document.getElementById('showUserModal');
const secretCodeInput = document.getElementById('secretCodeInput');
const secretCodeError = document.getElementById('secretCodeError');
const verifySecretCodeButton = document.getElementById('verifySecretCodeButton');
const confirmDisableButton = document.getElementById('confirmDisableButton');
const disableConfirmMessage = document.getElementById('disableConfirmMessage');
const disableCodeTitle = document.getElementById('disableCodeTitle');
const disableConfirmTitle = document.getElementById('disableConfirmTitle');
const showFullName = document.getElementById('showFullName');
const showUsername = document.getElementById('showUsername');
const showAssignmentLabel = document.getElementById('showAssignmentLabel');
const showAssignment = document.getElementById('showAssignment');
const showPhoneNumber = document.getElementById('showPhoneNumber');
const showGender = document.getElementById('showGender');
const showAge = document.getElementById('showAge');
const showRole = document.getElementById('showRole');
const showEmail = document.getElementById('showEmail');

const SECRET_CODE = 'cswd2026';

let activeRole = 'admin';
let currentPage = 1;
let totalPages = 1;
let searchDebounceTimer = null;
let selectedUser = null;
let selectedAction = 'disable';

const roleSearchState = {
    admin: '',
    encoder: '',
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (char) => {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;',
        };
        return map[char] ?? char;
    });
}

function openModal(modal) {
    if (!modal) {
        return;
    }

    modal.hidden = false;
    document.body.classList.add('has-modal-open');
}

function closeModal(modal) {
    if (!modal) {
        return;
    }

    modal.hidden = true;

    if (modal === disableCodeModal && secretCodeInput && secretCodeError) {
        secretCodeInput.value = '';
        secretCodeError.textContent = '';
    }

    if (!document.querySelector('.custom-modal:not([hidden])')) {
        document.body.classList.remove('has-modal-open');
    }
}

function updatePagination(meta) {
    if (!usersPagination || !prevPageButton || !nextPageButton || !paginationInfo) {
        return;
    }

    const pages = Number(meta?.total_pages ?? 1);
    const safeTotalPages = pages > 0 ? pages : 1;
    totalPages = safeTotalPages;

    usersPagination.hidden = safeTotalPages <= 1;

    paginationInfo.textContent = `Page ${currentPage} of ${safeTotalPages}`;
    prevPageButton.disabled = currentPage <= 1;
    nextPageButton.disabled = currentPage >= safeTotalPages;
}

function buildUserDetails(user) {
    const role = String(user.role ?? '').toLowerCase();
    const assignmentLabel = role === 'admin' ? 'Division Assigned:' : 'Program Assigned:';

    if (showAssignmentLabel) {
        showAssignmentLabel.textContent = assignmentLabel;
    }

    if (showFullName) {
        showFullName.textContent = String(user.full_name ?? '');
    }

    if (showUsername) {
        showUsername.textContent = String(user.username ?? '');
    }

    if (showAssignment) {
        showAssignment.textContent = String(user.assignment ?? '');
    }

    if (showPhoneNumber) {
        showPhoneNumber.textContent = String(user.phone_number ?? '');
    }

    if (showGender) {
        showGender.textContent = String(user.gender ?? '');
    }

    if (showAge) {
        showAge.textContent = String(user.age ?? '');
    }

    if (showRole) {
        showRole.textContent = String(user.role ?? '');
    }

    if (showEmail) {
        showEmail.textContent = String(user.email ?? '');
    }
}


function getAccountAction(user) {
    const status = String(user?.statuz ?? '').trim().toLowerCase();
    return status === 'disabled account' ? 'enable' : 'disable';
}

function applyAccountActionModalCopy(action, fullName = '') {
    const normalizedAction = action === 'enable' ? 'enable' : 'disable';
    const titleText = normalizedAction === 'enable' ? 'Enable Account' : 'Disable Account';

    if (disableCodeTitle) {
        disableCodeTitle.textContent = titleText;
    }

    if (disableConfirmTitle) {
        disableConfirmTitle.textContent = `Confirm ${titleText}`;
    }

    if (confirmDisableButton) {
        confirmDisableButton.textContent = normalizedAction === 'enable' ? 'Yes, Enable' : 'Yes, Disable';
    }

    if (disableConfirmMessage) {
        const safeName = fullName || 'this user';
        disableConfirmMessage.textContent = normalizedAction === 'enable'
            ? `Are you sure you want to enable ${safeName}'s account?`
            : `Are you sure you want to disable ${safeName}'s account?`;
    }
}

function attachActionEvents(button, user) {
    const showButton = button.querySelector('[data-action="show"]');
    const accountActionButton = button.querySelector('[data-action="account-toggle"]');

    if (showButton) {
        showButton.addEventListener('click', () => {
            buildUserDetails(user);
            openModal(showUserModal);
        });
    }

    if (accountActionButton) {
        accountActionButton.addEventListener('click', () => {
            selectedUser = user;
            selectedAction = getAccountAction(user);
            applyAccountActionModalCopy(selectedAction, String(user.full_name ?? 'this user'));
            openModal(disableCodeModal);
            if (secretCodeInput) {
                secretCodeInput.focus();
            }
        });
    }
}

async function loadUsers(role, page = 1) {
    const searchTerm = roleSearchState[role] || '';
    usersTableMessage.textContent = `Loading ${role}s...`;

    try {
        const params = new URLSearchParams({
            role,
            page: String(page),
            search: searchTerm,
        });
        const response = await fetch(`get_users.php?${params.toString()}`);

        if (!response.ok) {
            throw new Error('Unable to fetch users.');
        }

        const payload = await response.json();
        usersTableBody.innerHTML = '';

        const users = Array.isArray(payload.users) ? payload.users : [];

        if (users.length === 0) {
            usersTableMessage.textContent = `No ${role} records found.`;
            updatePagination(payload.pagination ?? { total_pages: 1 });
            return;
        }

        users.forEach((user) => {
            const normalizedStatus = String(user.statuz ?? '').toLowerCase();
            let statusClass = 'disabled';

            if (normalizedStatus.includes('active')) {
                statusClass = 'active';
            } else if (normalizedStatus.includes('offline')) {
                statusClass = 'offline';
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(user.full_name)}</td>
                <td>${escapeHtml(user.role)}</td>
                <td>${escapeHtml(user.assignment)}</td>
                <td>
                    <span class="status-cell">
                        <span class="status-dot ${statusClass}"></span>
                        <span>${escapeHtml(user.statuz)}</span>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        ${getAccountAction(user) === 'enable' ? `<button type="button" class="table-action-btn table-action-success" data-action="account-toggle">Enable Account</button>` : `<button type="button" class="table-action-btn table-action-danger" data-action="account-toggle">Disable Account</button>`}
                        <button type="button" class="table-action-btn table-action-primary" data-action="show">Show</button>
                    </div>
                </td>
            `;
            usersTableBody.appendChild(row);
            attachActionEvents(row, user);
        });

        usersTableMessage.textContent = '';

        const placeholderRows = Math.max(0, 10 - users.length);
        for (let index = 0; index < placeholderRows; index += 1) {
            const row = document.createElement('tr');
            row.innerHTML = '<td>&nbsp;</td><td></td><td></td><td></td><td></td>';
            usersTableBody.appendChild(row);
        }

        updatePagination(payload.pagination ?? { total_pages: 1 });
    } catch (error) {
        usersTableBody.innerHTML = '';
        usersTableMessage.textContent = 'Failed to load user records right now.';

        if (usersPagination) {
            usersPagination.hidden = true;
        }
    }
}

function syncSearchInput(role) {
    if (!userSearchInput) {
        return;
    }

    userSearchInput.value = roleSearchState[role] || '';
}

function applySearch(role, searchValue) {
    roleSearchState[role] = String(searchValue ?? '').trim();
    currentPage = 1;
    loadUsers(role, currentPage);
}

function setActiveTab(selectedRole) {
    activeRole = selectedRole;
    currentPage = 1;

    roleTabs.forEach((tab) => {
        const isSelected = tab.dataset.role === selectedRole;
        tab.classList.toggle('is-active', isSelected);
        tab.setAttribute('aria-selected', String(isSelected));
    });

    if (assignmentHeader) {
        assignmentHeader.textContent = selectedRole === 'admin' ? 'Division' : 'Program';
    }

    syncSearchInput(selectedRole);
    loadUsers(selectedRole, currentPage);
}

roleTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        setActiveTab(tab.dataset.role);
    });
});

if (userSearchInput) {
    userSearchInput.addEventListener('input', () => {
        window.clearTimeout(searchDebounceTimer);
        searchDebounceTimer = window.setTimeout(() => {
            applySearch(activeRole, userSearchInput.value);
        }, 250);
    });

    userSearchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(searchDebounceTimer);
            applySearch(activeRole, userSearchInput.value);
        }
    });
}

if (userSearchButton) {
    userSearchButton.addEventListener('click', () => {
        window.clearTimeout(searchDebounceTimer);
        applySearch(activeRole, userSearchInput ? userSearchInput.value : '');
    });
}

if (prevPageButton) {
    prevPageButton.addEventListener('click', () => {
        if (currentPage <= 1) {
            return;
        }

        currentPage -= 1;
        loadUsers(activeRole, currentPage);
    });
}

if (nextPageButton) {
    nextPageButton.addEventListener('click', () => {
        if (currentPage >= totalPages) {
            return;
        }

        currentPage += 1;
        loadUsers(activeRole, currentPage);
    });
}

document.querySelectorAll('[data-close-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        const modalId = button.getAttribute('data-close-modal');
        if (modalId) {
            closeModal(document.getElementById(modalId));
        }
    });
});

document.querySelectorAll('.custom-modal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal(modal);
        }
    });
});

if (verifySecretCodeButton) {
    verifySecretCodeButton.addEventListener('click', () => {
        if (!secretCodeInput || !secretCodeError) {
            return;
        }

        if (secretCodeInput.value.trim() !== SECRET_CODE) {
            secretCodeError.textContent = 'Incorrect secret code. Please try again.';
            return;
        }

        secretCodeError.textContent = '';

        if (selectedUser) {
            applyAccountActionModalCopy(selectedAction, String(selectedUser.full_name ?? 'this user'));
        }

        closeModal(disableCodeModal);
        openModal(disableConfirmModal);
    });
}

if (confirmDisableButton) {
    confirmDisableButton.addEventListener('click', async () => {
        if (!selectedUser) {
            closeModal(disableConfirmModal);
            return;
        }

        confirmDisableButton.disabled = true;

        try {
            const response = await fetch('disable_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: selectedUser.username,
                    role: String(selectedUser.role ?? '').toLowerCase(),
                    action: selectedAction,
                }),
            });

            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.message || 'Unable to disable account.');
            }

            closeModal(disableConfirmModal);
            selectedUser = null;
            selectedAction = 'disable';
            await loadUsers(activeRole, currentPage);
        } catch (error) {
            alert(error.message || 'Unable to disable account right now.');
        } finally {
            confirmDisableButton.disabled = false;
        }
    });
}

setActiveTab('admin');
