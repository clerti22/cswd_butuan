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
const recordId = window.clientRecordMode?.getRecordId() ?? null;
const formPage = document.querySelector('.form-page');

const familyMembers = [];
const treatmentPlans = [];
let originalRecordSnapshot = null;
let recordControls = null;

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

function removeInlineError(target) {
    if (!target) {
        return;
    }

    const fieldContainer = target.closest('.field, .description-field') || target.parentElement;
    const existingError = fieldContainer?.querySelector('.inline-error');
    if (existingError) {
        existingError.remove();
    }
}

function setInlineError(target, message) {
    if (!target) {
        return;
    }

    removeInlineError(target);

    const fieldContainer = target.closest('.field, .description-field') || target.parentElement;
    if (!fieldContainer) {
        return;
    }

    const errorMessage = document.createElement('small');
    errorMessage.className = 'inline-error';
    errorMessage.textContent = message;
    fieldContainer.appendChild(errorMessage);
}

function setFieldError(id, message, isValid) {
    const field = document.getElementById(id);
    if (!field) {
        return;
    }

    markFieldValidity(id, isValid);
    if (isValid) {
        removeInlineError(field);
        return;
    }

    setInlineError(field, message);
}

function setTextareaError(name, message, isValid) {
    const field = document.querySelector(`textarea[name="${name}"]`);
    if (!field) {
        return;
    }

    field.classList.toggle('invalid-input', !isValid);
    if (isValid) {
        removeInlineError(field);
        return;
    }

    setInlineError(field, message);
}

function setFamilySectionValidity(isValid, message = 'Please add at least one family composition row.') {
    const table = document.querySelector('.family-table');
    if (!table) {
        return;
    }

    table.classList.toggle('invalid-input', !isValid);
    removeInlineError(table);
    if (!isValid) {
        setInlineError(table, message);
    }
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

function setRadioValue(name, value) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
        input.checked = input.value === String(value ?? '');
    });
}

function renderFamilyRows() {
    resetTableBody(familyTableBody, 'family-table__row', 6);

    if (!familyMembers.length || !familyTableBody) {
        return;
    }

    familyTableBody.innerHTML = '';
    familyMembers.forEach((member) => {
        const row = document.createElement('div');
        row.className = 'family-table__row';
        [member.name, member.age, member.relationship, member.education, member.occupation, member.income].forEach((value) => {
            const cell = document.createElement('span');
            cell.textContent = value ?? '';
            row.appendChild(cell);
        });
        familyTableBody.appendChild(row);
    });
}

function renderTreatmentRows() {
    resetTableBody(treatmentTableBody, 'treatment-table__row', 5);

    if (!treatmentPlans.length || !treatmentTableBody) {
        return;
    }

    treatmentTableBody.innerHTML = '';
    treatmentPlans.forEach((plan) => {
        const row = document.createElement('div');
        row.className = 'treatment-table__row';
        [plan.objectives, plan.activities, plan.timeframe, plan.responsible, plan.remarks].forEach((value) => {
            const cell = document.createElement('span');
            cell.textContent = value ?? '';
            row.appendChild(cell);
        });
        treatmentTableBody.appendChild(row);
    });
}

function populateRecord(record, related = {}) {
    const fieldMappings = {
        'first-name': 'first_name',
        'middle-name': 'middle_name',
        'last-name': 'last_name',
        suffix: 'suffix',
        nickname: 'nickname',
        sex: 'sex',
        'birth-date': 'birth_date',
        age: 'age',
        'birth-place': 'birth_place',
        'present-address': 'present_address',
        'permanent-address': 'permanent_address',
        barangay: 'barangay',
        education: 'education',
        'school-last': 'school_last_attended',
        'school-address': 'school_address',
        'health-condition': 'health_condition',
        'contact-number': 'contact_number',
        'offense-type': 'offense_type',
        'incident-date-place': 'incident_date',
        'perpetrator-name': 'perpetrator_name',
        'referral-date': 'referral_date',
        'referring-party': 'referring_party',
        'case-status': 'case_status',
        'criminal-case': 'criminal_case_no',
        'info-source': 'info_source',
        complainant: 'complainant',
        'background-child': 'background_child',
        'background-family': 'background_family',
        'background-community': 'background_community'
    };

    Object.entries(fieldMappings).forEach(([fieldId, recordKey]) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = record?.[recordKey] ?? '';
        }
    });

    document.querySelector('textarea[name="presenting-problem"]').value = record?.presenting_problem ?? '';
    document.querySelector('textarea[name="assessment"]').value = record?.assessment ?? '';
    document.querySelector('textarea[name="recommendation"]').value = record?.recommendation ?? '';

    setRadioValue('4ps', record?.is_4ps ?? '');
    setRadioValue('philhealth', record?.philhealth ?? '');

    familyMembers.length = 0;
    (related.family_information || []).forEach((member) => familyMembers.push({ ...member }));
    renderFamilyRows();

    treatmentPlans.length = 0;
    (related.treatment_plan || []).forEach((plan) => treatmentPlans.push({ ...plan }));
    renderTreatmentRows();
}

function setEditMode(isEditable) {
    if (formPage) {
        window.clientRecordMode?.toggleContainerEditState(formPage, isEditable);
    }

    [openFamilyModal, closeFamilyModal, openTreatmentModal, closeTreatmentModal].forEach((button) => {
        if (button) {
            button.disabled = !isEditable;
        }
    });

    familyForm?.querySelectorAll('button').forEach((button) => {
        button.disabled = !isEditable;
    });
    treatmentForm?.querySelectorAll('button').forEach((button) => {
        button.disabled = !isEditable;
    });

    if (submitButton && recordId) {
        submitButton.disabled = !isEditable;
    }

    if (!isEditable) {
        toggleModal(familyModal, false);
        toggleModal(treatmentModal, false);
    }
}

async function loadRecordForViewMode() {
    if (!recordId) {
        return;
    }

    try {
        const response = await fetch(`../../get_client_record.php?program=csae&id=${encodeURIComponent(String(recordId))}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load CSAE record.');
        }

        originalRecordSnapshot = payload;
        populateRecord(payload.record, payload.related || {});
        setEditMode(false);

        recordControls = window.clientRecordMode?.mountControls({
            anchor: document.querySelector('.form-page'),
            submitButton,
            onEnableEdit: () => {
                setEditMode(true);
                setMessage('', '');
            },
            onCancelEdit: () => {
                populateRecord(originalRecordSnapshot.record, originalRecordSnapshot.related || {});
                setEditMode(false);
                setMessage('', '');
            },
            onSaveStateChange: () => {}
        }) || null;
    } catch (error) {
        setMessage(error.message, 'error');
    }
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
        'barangay',
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
        const value = getFieldValue(id);
        let fieldIsValid = value !== '';
        let errorMessage = 'This field is required.';

        if (fieldIsValid && id === 'age') {
            fieldIsValid = Number.isFinite(Number(value)) && Number(value) > 0;
            errorMessage = 'Please enter a valid age.';
        }

        if (fieldIsValid && id === 'contact-number') {
            const digitsOnly = value.replace(/\D/g, '');
            fieldIsValid = digitsOnly.length >= 7;
            errorMessage = 'Please provide a valid value.';
        }

        if (fieldIsValid && ['sex'].includes(id) && !value) {
            fieldIsValid = false;
        }

        setFieldError(id, errorMessage, fieldIsValid);
        if (!fieldIsValid) {
            isValid = false;
        }
    });

    const presentingProblem = getTextareaValue('presenting-problem');
    const hasPresentingProblem = presentingProblem !== '';
    setTextareaError('presenting-problem', 'This field is required.', hasPresentingProblem);
    if (!hasPresentingProblem) {
        isValid = false;
    }

    const fourPs = getCheckedRadioValue('4ps');
    const philhealth = getCheckedRadioValue('philhealth');

    setRadioValidity('4ps', fourPs !== '');
    setRadioValidity('philhealth', philhealth !== '');

    if (!fourPs) {
        setInlineError(document.querySelector('input[name="4ps"]')?.closest('.field'), 'Please provide a valid value.');
    } else {
        removeInlineError(document.querySelector('input[name="4ps"]')?.closest('.field'));
    }

    if (!philhealth) {
        setInlineError(document.querySelector('input[name="philhealth"]')?.closest('.field'), 'Please provide a valid value.');
    } else {
        removeInlineError(document.querySelector('input[name="philhealth"]')?.closest('.field'));
    }

    if (!fourPs || !philhealth) {
        isValid = false;
    }

    const hasFamilyRows = familyMembers.length > 0;
    setFamilySectionValidity(hasFamilyRows);
    if (!hasFamilyRows) {
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
        removeInlineError(field);
    });

    document.querySelectorAll('.radio-option').forEach((option) => {
        option.classList.remove('invalid-input');
    });
    document.querySelectorAll('.inline-error').forEach((error) => error.remove());
    setFamilySectionValidity(true);

    familyMembers.length = 0;
    treatmentPlans.length = 0;
    resetTableBody(familyTableBody, 'family-table__row', 6);
    resetTableBody(treatmentTableBody, 'treatment-table__row', 5);
}

document.querySelectorAll('input, select, textarea').forEach((field) => {
    field.addEventListener('input', () => {
        if (field.type === 'radio') {
            return;
        }

        const hasValue = field.value.trim() !== '';
        if (hasValue) {
            field.classList.remove('invalid-input');
            removeInlineError(field);
        }
    });

    field.addEventListener('change', () => {
        if (field.type === 'radio') {
            const name = field.name;
            setRadioValidity(name, true);
            removeInlineError(field.closest('.field'));
            return;
        }

        const hasValue = field.value.trim() !== '';
        if (hasValue) {
            field.classList.remove('invalid-input');
            removeInlineError(field);
        }
    });
});

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

    const confirmed = await window.clientFormSubmitConfirmation?.confirm();
    if (!confirmed) {
        return;
    }

    submitButton.disabled = true;

    const formData = new FormData();
    if (recordId) {
        formData.append('record_id', String(recordId));
    }
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
    formData.append('barangay', getFieldValue('barangay'));
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

        setMessage(recordId ? 'CSAE record updated successfully.' : 'CSAE record saved successfully.', 'success');

        if (recordId) {
            window.location.href = `${window.location.pathname}?id=${encodeURIComponent(String(recordId))}`;
            return;
        }

        resetMainForm();
    } catch (error) {
        setMessage(error.message, 'error');
    } finally {
        submitButton.disabled = false;
    }
});

loadRecordForViewMode();
