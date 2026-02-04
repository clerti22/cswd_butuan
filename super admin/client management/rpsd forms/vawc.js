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
const treatmentModal = document.getElementById('treatmentModal');
const openTreatmentModalBtn = document.getElementById('openTreatmentModal');
const closeTreatmentModalBtn = document.getElementById('closeTreatmentModal');
const treatmentForm = document.getElementById('treatmentForm');
const treatmentTableBody = document.querySelector('.treatment-table__body');

function openFamilyModal() {
    familyModal.classList.add('active');
    familyModal.setAttribute('aria-hidden', 'false');
}

function closeFamilyModal() {
    familyModal.classList.remove('active');
    familyModal.setAttribute('aria-hidden', 'true');
    familyForm.reset();
}

function openTreatmentModal() {
    treatmentModal.classList.add('active');
    treatmentModal.setAttribute('aria-hidden', 'false');
}

function closeTreatmentModal() {
    treatmentModal.classList.remove('active');
    treatmentModal.setAttribute('aria-hidden', 'true');
    treatmentForm.reset();
}

openFamilyModalBtn.addEventListener('click', openFamilyModal);
closeFamilyModalBtn.addEventListener('click', closeFamilyModal);
openTreatmentModalBtn.addEventListener('click', openTreatmentModal);
closeTreatmentModalBtn.addEventListener('click', closeTreatmentModal);

familyModal.addEventListener('click', (event) => {
    if (event.target === familyModal) {
        closeFamilyModal();
    }
});

treatmentModal.addEventListener('click', (event) => {
    if (event.target === treatmentModal) {
        closeTreatmentModal();
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

treatmentForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const data = new FormData(treatmentForm);
    const row = document.createElement('div');
    row.className = 'treatment-table__row';
    row.innerHTML = `
        <span>${data.get('objectives')}</span>
        <span>${data.get('activities')}</span>
        <span>${data.get('responsible')}</span>
        <span>${data.get('resources')}</span>
        <span>${data.get('output')}</span>
    `;
    const emptyRow = treatmentTableBody.querySelector('.treatment-table__row--empty');
    if (emptyRow) {
        emptyRow.remove();
    }
    treatmentTableBody.appendChild(row);
    closeTreatmentModal();
});
