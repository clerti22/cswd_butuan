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

document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', (event) => {
        event.preventDefault();
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
const submitButton = document.querySelector('.form-submit-btn');

const familyMembers = [];
const treatmentPlans = [];

function toggleModal(modal, isOpen) {
    if (!modal) {
        return;
    }

    modal.classList.toggle('active', isOpen);
    modal.setAttribute('aria-hidden', String(!isOpen));
}

function getFieldValue(id) {
    return document.getElementById(id)?.value.trim() || '';
}

function getTextareaValue(name) {
    return document.querySelector(`textarea[name="${name}"]`)?.value.trim() || '';
}

function getCheckedRadioValue(name) {
    return document.querySelector(`input[name="${name}"]:checked`)?.value || '';
}

function markFieldValidity(id, isValid) {
    const field = document.getElementById(id);
    if (!field) {
        return;
    }

    field.classList.toggle('invalid-input', !isValid);
}

function setRadioValidity(name, isValid) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
        const option = input.closest('.radio-option');
        if (option) {
            option.classList.toggle('invalid-input', !isValid);
        }
    });
}

function showMessage(message) {
    alert(message);
}

function resetTableBody(tableBody, rowClass, columnCount) {
    if (!tableBody) {
        return;
    }

    const emptyCells = Array.from({ length: columnCount }, () => '<span></span>').join('');
    tableBody.innerHTML = `<div class="${rowClass} ${rowClass}--empty">${emptyCells}</div>`;
}

function validateMainForm() {
    let isValid = true;

    const requiredFieldIds = [
        'first-name',
        'last-name',
        'birth-place',
        'birth-date',
        'age',
        'education',
        'religion',
        'address',
        'barangay',
        'occupation',
        'income',
        'contact',
        'civil-status',
        'family-environment',
        'community',
        'family-history'
    ];

    requiredFieldIds.forEach((id) => {
        const valid = getFieldValue(id) !== '';
        markFieldValidity(id, valid);
        if (!valid) {
            isValid = false;
        }
    });

    const presentingProblem = getTextareaValue('presenting-problem');
    if (!presentingProblem) {
        isValid = false;
    }

    const fourPs = getCheckedRadioValue('4ps');
    const uct = getCheckedRadioValue('uct');
    const nhts = getCheckedRadioValue('nhts');

    setRadioValidity('4ps', fourPs !== '');
    setRadioValidity('uct', uct !== '');
    setRadioValidity('nhts', nhts !== '');

    if (!fourPs || !uct || !nhts) {
        isValid = false;
    }

    if (!familyMembers.length) {
        isValid = false;
    }

    return {
        isValid,
        fourPs,
        uct,
        nhts,
        presentingProblem
    };
}

function resetMainForm() {
    document.querySelectorAll('input, select, textarea').forEach((field) => {
        if (field.type === 'radio') {
            field.checked = false;
        } else {
            field.value = '';
        }

        field.classList.remove('invalid-input');
    });

    document.querySelectorAll('.radio-option').forEach((option) => {
        option.classList.remove('invalid-input');
    });

    familyMembers.length = 0;
    treatmentPlans.length = 0;
    resetTableBody(familyTableBody, 'family-table__row', 6);
    resetTableBody(treatmentTableBody, 'treatment-table__row', 5);
}

openFamilyModalBtn?.addEventListener('click', () => toggleModal(familyModal, true));
closeFamilyModalBtn?.addEventListener('click', () => toggleModal(familyModal, false));
openTreatmentModalBtn?.addEventListener('click', () => toggleModal(treatmentModal, true));
closeTreatmentModalBtn?.addEventListener('click', () => toggleModal(treatmentModal, false));

familyModal?.addEventListener('click', (event) => {
    if (event.target === familyModal) {
        toggleModal(familyModal, false);
    }
});

treatmentModal?.addEventListener('click', (event) => {
    if (event.target === treatmentModal) {
        toggleModal(treatmentModal, false);
    }
});

familyForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const formData = new FormData(familyForm);
    const rowData = {
        name: (formData.get('name') || '').toString().trim(),
        age: (formData.get('age') || '').toString().trim(),
        relationship: (formData.get('relationship') || '').toString().trim(),
        education: (formData.get('education') || '').toString().trim(),
        occupation: (formData.get('occupation') || '').toString().trim(),
        income: (formData.get('income') || '').toString().trim()
    };

    if (Object.values(rowData).some((value) => value === '')) {
        showMessage('Please complete all required family information fields.');
        return;
    }

    familyMembers.push(rowData);

    const emptyRow = familyTableBody?.querySelector('.family-table__row--empty');
    if (emptyRow) {
        emptyRow.remove();
    }

    if (familyTableBody) {
        const row = document.createElement('div');
        row.className = 'family-table__row';
        Object.values(rowData).forEach((value) => {
            const cell = document.createElement('span');
            cell.textContent = value;
            row.appendChild(cell);
        });
        familyTableBody.appendChild(row);
    }

    familyForm.reset();
    toggleModal(familyModal, false);
});

treatmentForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const formData = new FormData(treatmentForm);
    const rowData = {
        objectives: (formData.get('objectives') || '').toString().trim(),
        activities: (formData.get('activities') || '').toString().trim(),
        responsible: (formData.get('responsible') || '').toString().trim(),
        resources: (formData.get('resources') || '').toString().trim(),
        output: (formData.get('output') || '').toString().trim()
    };

    if (Object.values(rowData).every((value) => value === '')) {
        toggleModal(treatmentModal, false);
        return;
    }

    if (Object.values(rowData).some((value) => value === '')) {
        showMessage('If you add an initial treatment row, please complete all treatment fields.');
        return;
    }

    treatmentPlans.push(rowData);

    const emptyRow = treatmentTableBody?.querySelector('.treatment-table__row--empty');
    if (emptyRow) {
        emptyRow.remove();
    }

    if (treatmentTableBody) {
        const row = document.createElement('div');
        row.className = 'treatment-table__row';
        Object.values(rowData).forEach((value) => {
            const cell = document.createElement('span');
            cell.textContent = value;
            row.appendChild(cell);
        });
        treatmentTableBody.appendChild(row);
    }

    treatmentForm.reset();
    toggleModal(treatmentModal, false);
});

submitButton?.addEventListener('click', async () => {
    const { isValid, fourPs, uct, nhts, presentingProblem } = validateMainForm();

    if (!isValid) {
        showMessage('Please complete all required VAWC fields and add at least one family information row.');
        return;
    }

    const confirmed = await window.clientFormSubmitConfirmation?.confirm();
    if (!confirmed) {
        return;
    }

    submitButton.disabled = true;

    const formData = new FormData();
    formData.append('first_name', getFieldValue('first-name'));
    formData.append('middle_name', getFieldValue('middle-name'));
    formData.append('last_name', getFieldValue('last-name'));
    formData.append('suffix', getFieldValue('suffix'));
    formData.append('birth_place', getFieldValue('birth-place'));
    formData.append('birth_date', getFieldValue('birth-date'));
    formData.append('age', getFieldValue('age'));
    formData.append('education', getFieldValue('education'));
    formData.append('religion', getFieldValue('religion'));
    formData.append('address', getFieldValue('address'));
    formData.append('barangay', getFieldValue('barangay'));
    formData.append('occupation', getFieldValue('occupation'));
    formData.append('monthly_income', getFieldValue('income'));
    formData.append('contact_number', getFieldValue('contact'));
    formData.append('civil_status', getFieldValue('civil-status'));
    formData.append('is_4ps', fourPs);
    formData.append('is_uct', uct);
    formData.append('is_nhts', nhts);
    formData.append('presenting_problem', presentingProblem);
    formData.append('family_environment', getFieldValue('family-environment'));
    formData.append('community', getFieldValue('community'));
    formData.append('family_history', getFieldValue('family-history'));
    formData.append('recommendation', getTextareaValue('recommendation'));
    formData.append('family_information', JSON.stringify(familyMembers));
    formData.append('initial_treatment_plan', JSON.stringify(treatmentPlans));

    try {
        const response = await fetch('vawc_submit.php', {
            method: 'POST',
            body: formData
        });

        const contentType = response.headers.get('content-type') || '';
        const responseText = await response.text();
        const result = contentType.includes('application/json') && responseText ? JSON.parse(responseText) : null;

        if (response.status === 405) {
            throw new Error('Submit endpoint is not accepting POST requests. Please run this page in a PHP-enabled server (e.g., XAMPP/Apache or php -S).');
        }

        if (!response.ok || !result || !result.success) {
            throw new Error((result && result.message) || 'Failed to save VAWC record.');
        }

        showMessage('VAWC record saved successfully.');
        resetMainForm();
    } catch (error) {
        showMessage(error.message);
    } finally {
        submitButton.disabled = false;
    }
});
