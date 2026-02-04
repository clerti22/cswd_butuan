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
const openFamilyModalBtn = document.getElementById('openFamilyModal');
const closeFamilyModalBtn = document.getElementById('closeFamilyModal');
const familyForm = document.getElementById('familyForm');
const familyTableBody = document.querySelector('.family-table__body');

function openFamilyModal() {
    familyModal.classList.add('active');
    familyModal.setAttribute('aria-hidden', 'false');
}

function closeFamilyModal() {
    familyModal.classList.remove('active');
    familyModal.setAttribute('aria-hidden', 'true');
    familyForm.reset();
}

openFamilyModalBtn.addEventListener('click', openFamilyModal);
closeFamilyModalBtn.addEventListener('click', closeFamilyModal);

familyModal.addEventListener('click', (event) => {
    if (event.target === familyModal) {
        closeFamilyModal();
    }
});

familyForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const data = new FormData(familyForm);
    const row = document.createElement('div');
    row.className = 'family-table__row';
    row.innerHTML = `
        <span>${data.get('name')}</span>
        <span>${data.get('age')}</span>
        <span>${data.get('relationship')}</span>
        <span>${data.get('education')}</span>
        <span>${data.get('occupation')}</span>
        <span>${data.get('income')}</span>
    `;
    const emptyRow = familyTableBody.querySelector('.family-table__row--empty');
    if (emptyRow) {
        emptyRow.remove();
    }
    familyTableBody.appendChild(row);
    closeFamilyModal();
});
