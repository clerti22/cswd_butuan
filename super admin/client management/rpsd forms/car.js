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

const householdModal = document.getElementById('householdModal');
const openHouseholdModalBtn = document.getElementById('openHouseholdModal');
const closeHouseholdModalBtn = document.getElementById('closeHouseholdModal');
const householdForm = document.getElementById('householdForm');
const householdTableBody = document.getElementById('householdTableBody');

function openHouseholdModal() {
    if (!householdModal) return;
    householdModal.style.display = 'flex';
    householdModal.setAttribute('aria-hidden', 'false');
}

function closeHouseholdModal() {
    if (!householdModal) return;
    householdModal.style.display = 'none';
    householdModal.setAttribute('aria-hidden', 'true');
    householdForm?.reset();
}

openHouseholdModalBtn?.addEventListener('click', openHouseholdModal);
closeHouseholdModalBtn?.addEventListener('click', closeHouseholdModal);

householdModal?.addEventListener('click', (event) => {
    if (event.target === householdModal) {
        closeHouseholdModal();
    }
});

householdForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const values = [
        document.getElementById('hm-name')?.value.trim(),
        document.getElementById('hm-age')?.value.trim(),
        document.getElementById('hm-gender')?.value.trim(),
        document.getElementById('hm-status')?.value.trim(),
        document.getElementById('hm-relation')?.value.trim(),
        document.getElementById('hm-work')?.value.trim(),
        document.getElementById('hm-income')?.value.trim(),
        document.getElementById('hm-remarks')?.value.trim()
    ];

    const emptyRow = householdTableBody?.querySelector('.household-empty-row');
    if (emptyRow) {
        emptyRow.remove();
    }

    const row = document.createElement('tr');
    values.forEach(value => {
        const cell = document.createElement('td');
        cell.textContent = value || '';
        row.appendChild(cell);
    });

    householdTableBody?.appendChild(row);
    closeHouseholdModal();
});
