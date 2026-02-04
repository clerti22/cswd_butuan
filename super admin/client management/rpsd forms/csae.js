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
const treatmentModal = document.getElementById('treatmentModal');
const openTreatmentModal = document.getElementById('openTreatmentModal');
const closeTreatmentModal = document.getElementById('closeTreatmentModal');
const treatmentForm = document.getElementById('treatmentForm');
const treatmentTableBody = document.getElementById('treatmentTableBody');

const toggleModal = (modal, isOpen) => {
    if (!modal) {
        return;
    }
    modal.classList.toggle('active', isOpen);
    modal.setAttribute('aria-hidden', String(!isOpen));
};

if (openFamilyModal) {
    openFamilyModal.addEventListener('click', () => toggleModal(familyModal, true));
}

if (closeFamilyModal) {
    closeFamilyModal.addEventListener('click', () => toggleModal(familyModal, false));
}

if (familyModal) {
    familyModal.addEventListener('click', (event) => {
        if (event.target === familyModal) {
            toggleModal(familyModal, false);
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
        toggleModal(familyModal, false);
    });
}

if (openTreatmentModal) {
    openTreatmentModal.addEventListener('click', () => toggleModal(treatmentModal, true));
}

if (closeTreatmentModal) {
    closeTreatmentModal.addEventListener('click', () => toggleModal(treatmentModal, false));
}

if (treatmentModal) {
    treatmentModal.addEventListener('click', (event) => {
        if (event.target === treatmentModal) {
            toggleModal(treatmentModal, false);
        }
    });
}

if (treatmentForm) {
    treatmentForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(treatmentForm);
        const values = [
            formData.get('objectives'),
            formData.get('activities'),
            formData.get('timeframe'),
            formData.get('responsible'),
            formData.get('remarks')
        ];

        if (treatmentTableBody) {
            const emptyRow = treatmentTableBody.querySelector('.treatment-table__row--empty');
            if (emptyRow) {
                emptyRow.remove();
            }

            const row = document.createElement('div');
            row.className = 'treatment-table__row';
            values.forEach((value) => {
                const cell = document.createElement('span');
                cell.textContent = value || '';
                row.appendChild(cell);
            });
            treatmentTableBody.appendChild(row);
        }

        treatmentForm.reset();
        toggleModal(treatmentModal, false);
    });
}
