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
    item.addEventListener('click', (e) => {
        e.preventDefault();
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
const openFamilyModal = document.getElementById('openFamilyModal');
const closeFamilyModal = document.getElementById('closeFamilyModal');
const familyForm = document.getElementById('familyForm');
const familyTableBody = document.getElementById('familyTableBody');

const treatmentModal = document.getElementById('treatmentModal');
const openTreatmentModal = document.getElementById('openTreatmentModal');
const closeTreatmentModal = document.getElementById('closeTreatmentModal');
const treatmentForm = document.getElementById('treatmentForm');
const treatmentTableBody = document.getElementById('treatmentTableBody');

const submitButton = document.querySelector('.form-submit-btn');

const familyMembers = [];
const treatmentPlans = [];

const toggleModal = (modal, isOpen) => {
    if (!modal) {
        return;
    }

    modal.classList.toggle('active', isOpen);
    modal.setAttribute('aria-hidden', String(!isOpen));
};

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

function setMessage(message, type) {
    if (!message) {
        return;
    }

    if (type === 'error') {
        alert(message);
        return;
    }

    alert(message);
}

function resetTableBody(tableBody, rowClass, columns) {
    if (!tableBody) {
        return;
    }

    const emptyCells = Array.from({ length: columns }, () => '<span></span>').join('');
    tableBody.innerHTML = `<div class="${rowClass} ${rowClass}--empty">${emptyCells}</div>`;
}

function validateForm() {
    let isValid = true;

    const requiredFieldIds = [
        'first-name',
        'last-name',
        'sex',
        'birth-date',
        'age',
        'birth-place',
        'present-address',
        'permanent-address',
        'education',
        'school-last',
        'school-address',
        'contact-number',
        'offense-type',
        'incident-date-place',
        'perpetrator-name',
        'referral-date',
        'referring-party',
        'case-status',
        'criminal-case',
        'info-source',
        'complainant',
        'background-child',
        'background-family',
        'background-community'
    ];

    requiredFieldIds.forEach((id) => {
        const fieldIsValid = getFieldValue(id) !== '';
        markFieldValidity(id, fieldIsValid);
        if (!fieldIsValid) {
            isValid = false;
        }
    });

    const presentingProblem = getTextareaValue('presenting-problem');
    if (!presentingProblem) {
        isValid = false;
    }

    const fourPs = getCheckedRadioValue('4ps');
    const philhealth = getCheckedRadioValue('philhealth');

    setRadioValidity('4ps', fourPs !== '');
    setRadioValidity('philhealth', philhealth !== '');

    if (!fourPs || !philhealth) {
        isValid = false;
    }

    if (!familyMembers.length) {
        isValid = false;
    }

    return {
        isValid,
        fourPs,
        philhealth,
        presentingProblem
    };
}

function resetMainForm() {
    document.querySelectorAll('input, select, textarea').forEach((field) => {
        if (field.closest('.modal')) {
            field.classList.remove('invalid-input');
            field.value = '';
            return;
        }

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
        const rowData = {
            name: (formData.get('name') || '').toString().trim(),
            age: (formData.get('age') || '').toString().trim(),
            relationship: (formData.get('relationship') || '').toString().trim(),
            education: (formData.get('education') || '').toString().trim(),
            occupation: (formData.get('occupation') || '').toString().trim(),
            income: (formData.get('income') || '').toString().trim()
        };

        if (Object.values(rowData).some((value) => value === '')) {
            setMessage('Please complete all required family information fields.', 'error');
            return;
        }

        familyMembers.push(rowData);

        if (familyTableBody) {
            const emptyRow = familyTableBody.querySelector('.family-table__row--empty');
            if (emptyRow) {
                emptyRow.remove();
            }

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
        const rowData = {
            objectives: (formData.get('objectives') || '').toString().trim(),
            activities: (formData.get('activities') || '').toString().trim(),
            timeframe: (formData.get('timeframe') || '').toString().trim(),
            responsible: (formData.get('responsible') || '').toString().trim(),
            remarks: (formData.get('remarks') || '').toString().trim()
        };

        treatmentPlans.push(rowData);

        if (treatmentTableBody) {
            const emptyRow = treatmentTableBody.querySelector('.treatment-table__row--empty');
            if (emptyRow) {
                emptyRow.remove();
            }

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
}

submitButton?.addEventListener('click', async () => {
    const { isValid, fourPs, philhealth, presentingProblem } = validateForm();

    if (!isValid) {
        setMessage('Please complete all required CSAE fields and add at least one family member before submitting.', 'error');
        return;
    }

    submitButton.disabled = true;

    const formData = new FormData();
    formData.append('first_name', getFieldValue('first-name'));
    formData.append('middle_name', getFieldValue('middle-name'));
    formData.append('last_name', getFieldValue('last-name'));
    formData.append('suffix', getFieldValue('suffix'));
    formData.append('nickname', getFieldValue('nickname'));
    formData.append('sex', getFieldValue('sex'));
    formData.append('birth_date', getFieldValue('birth-date'));
    formData.append('age', getFieldValue('age'));
    formData.append('birth_place', getFieldValue('birth-place'));
    formData.append('present_address', getFieldValue('present-address'));
    formData.append('permanent_address', getFieldValue('permanent-address'));
    formData.append('education', getFieldValue('education'));
    formData.append('school_last_attended', getFieldValue('school-last'));
    formData.append('school_address', getFieldValue('school-address'));
    formData.append('health_condition', getFieldValue('health-condition'));
    formData.append('contact_number', getFieldValue('contact-number'));
    formData.append('is_4ps', fourPs);
    formData.append('philhealth', philhealth);

    formData.append('offense_type', getFieldValue('offense-type'));
    formData.append('incident_date', getFieldValue('incident-date-place'));
    formData.append('perpetrator_name', getFieldValue('perpetrator-name'));
    formData.append('referral_date', getFieldValue('referral-date'));
    formData.append('referring_party', getFieldValue('referring-party'));
    formData.append('case_status', getFieldValue('case-status'));
    formData.append('criminal_case_no', getFieldValue('criminal-case'));
    formData.append('info_source', getFieldValue('info-source'));
    formData.append('complainant', getFieldValue('complainant'));

    formData.append('presenting_problem', presentingProblem);
    formData.append('background_child', getFieldValue('background-child'));
    formData.append('background_family', getFieldValue('background-family'));
    formData.append('background_community', getFieldValue('background-community'));
    formData.append('assessment', getTextareaValue('assessment'));
    formData.append('recommendation', getTextareaValue('recommendation'));

    formData.append('family_information', JSON.stringify(familyMembers));
    formData.append('treatment_plan', JSON.stringify(treatmentPlans));

    try {
        const response = await fetch('csae_submit.php', {
            method: 'POST',
            body: formData
        });

        const responseType = response.headers.get('content-type') || '';
        const responseText = await response.text();
        const result = responseType.includes('application/json') && responseText ? JSON.parse(responseText) : null;

        if (response.status === 405) {
            throw new Error('Submit endpoint is not accepting POST requests. Please run this page in a PHP-enabled server (e.g., XAMPP/Apache or php -S).');
        }

        if (!response.ok || !result || !result.success) {
            throw new Error((result && result.message) || 'Failed to save CSAE record.');
        }

        setMessage('CSAE record saved successfully.', 'success');
        resetMainForm();
    } catch (error) {
        setMessage(error.message, 'error');
    } finally {
        submitButton.disabled = false;
    }
});
