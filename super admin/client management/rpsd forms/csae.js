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

const familyModal = document.getElementById('familyModal');
const openFamilyModal = document.getElementById('openFamilyModal');
const closeFamilyModal = document.getElementById('closeFamilyModal');
const familyForm = document.getElementById('familyForm');
const familyTableBody = document.getElementById('familyTableBody');

const toggleFamilyModal = (isOpen) => {
    if (!familyModal) {
        return;
    }
    familyModal.classList.toggle('active', isOpen);
    familyModal.setAttribute('aria-hidden', String(!isOpen));
};

if (openFamilyModal) {
    openFamilyModal.addEventListener('click', () => toggleFamilyModal(true));
}

if (closeFamilyModal) {
    closeFamilyModal.addEventListener('click', () => toggleFamilyModal(false));
}

if (familyModal) {
    familyModal.addEventListener('click', (event) => {
        if (event.target === familyModal) {
            toggleFamilyModal(false);
        }
    });
}

if (familyForm) {
    familyForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(familyForm);
        const values = [
            formData.get('name'),
            formData.get('age'),
            formData.get('relationship'),
            formData.get('education'),
            formData.get('occupation'),
            formData.get('income')
        ];

        if (familyTableBody) {
            const emptyRow = familyTableBody.querySelector('.family-table__row--empty');
            if (emptyRow) {
                emptyRow.remove();
            }

            const row = document.createElement('div');
            row.className = 'family-table__row';
            values.forEach((value) => {
                const cell = document.createElement('span');
                cell.textContent = value || '';
                row.appendChild(cell);
            });
            familyTableBody.appendChild(row);
        }

        familyForm.reset();
        toggleFamilyModal(false);
    });
}
