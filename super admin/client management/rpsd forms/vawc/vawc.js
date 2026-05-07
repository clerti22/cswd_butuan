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
const recordId = window.clientRecordMode?.getRecordId() ?? null;
const formPage = document.querySelector('.form-page');

const familyMembers = [];
const treatmentPlans = [];
let originalRecordSnapshot = null;
let recordControls = null;

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

function getErrorNodeKey(target, key = 'fieldErrorNode') {
    const datasetKey = `__${key}`;
    return target[datasetKey] || null;
}

function setErrorNodeKey(target, node, key = 'fieldErrorNode') {
    const datasetKey = `__${key}`;
    target[datasetKey] = node;
}

function setFieldErrorMessage(field, message) {
    if (!field) {
        return;
    }

    const isInvalid = Boolean(message);
    field.classList.toggle('invalid-input', isInvalid);

    let errorNode = getErrorNodeKey(field);
    if (!isInvalid) {
        if (errorNode) {
            errorNode.remove();
            setErrorNodeKey(field, null);
        }
        return;
    }

    if (!errorNode) {
        errorNode = document.createElement('small');
        errorNode.className = 'field-error-message';
        field.insertAdjacentElement('afterend', errorNode);
        setErrorNodeKey(field, errorNode);
    }
    errorNode.textContent = message;
}

function setRadioErrorMessage(name, message) {
    const radioOptions = document.querySelectorAll(`input[name="${name}"]`);
    if (!radioOptions.length) {
        return;
    }

    setRadioValidity(name, !message);
    const radioGroup = radioOptions[0].closest('.clientele-item');
    if (!radioGroup) {
        return;
    }

    let errorNode = getErrorNodeKey(radioGroup, 'radioErrorNode');
    if (!message) {
        if (errorNode) {
            errorNode.remove();
            setErrorNodeKey(radioGroup, null, 'radioErrorNode');
        }
        return;
    }

    if (!errorNode) {
        errorNode = document.createElement('small');
        errorNode.className = 'field-error-message';
        radioGroup.appendChild(errorNode);
        setErrorNodeKey(radioGroup, errorNode, 'radioErrorNode');
    }
    errorNode.textContent = message;
}

function setSectionErrorMessage(section, message) {
    if (!section) {
        return;
    }

    section.classList.toggle('invalid-section', Boolean(message));
    const parent = section.parentElement;
    let errorNode = getErrorNodeKey(section, 'sectionErrorNode');
    if (!message) {
        if (errorNode) {
            errorNode.remove();
            setErrorNodeKey(section, null, 'sectionErrorNode');
        }
        return;
    }

    if (!errorNode) {
        errorNode = document.createElement('small');
        errorNode.className = 'field-error-message section-error-message';
        parent?.insertBefore(errorNode, section.nextSibling);
        setErrorNodeKey(section, errorNode, 'sectionErrorNode');
    }
    errorNode.textContent = message;
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
        [plan.objectives, plan.activities, plan.responsible, plan.resources, plan.output].forEach((value) => {
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
        'birth-place': 'birth_place',
        'birth-date': 'birth_date',
        age: 'age',
        education: 'education',
        religion: 'religion',
        address: 'address',
        barangay: 'barangay',
        occupation: 'occupation',
        income: 'monthly_income',
        contact: 'contact_number',
        'civil-status': 'civil_status',
        'family-environment': 'family_environment',
        community: 'community',
        'family-history': 'family_history'
    };

    Object.entries(fieldMappings).forEach(([fieldId, recordKey]) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = record?.[recordKey] ?? '';
        }
    });

    document.querySelector('textarea[name="presenting-problem"]').value = record?.presenting_problem ?? '';
    document.querySelector('textarea[name="recommendation"]').value = record?.recommendation ?? '';

    setRadioValue('4ps', record?.is_4ps ?? '');
    setRadioValue('uct', record?.is_uct ?? '');
    setRadioValue('nhts', record?.is_nhts ?? '');

    familyMembers.length = 0;
    (related.family_information || []).forEach((member) => familyMembers.push({ ...member }));
    renderFamilyRows();

    treatmentPlans.length = 0;
    (related.initial_treatment_plan || []).forEach((plan) => treatmentPlans.push({ ...plan }));
    renderTreatmentRows();
}

function setEditMode(isEditable) {
    if (formPage) {
        window.clientRecordMode?.toggleContainerEditState(formPage, isEditable);
    }

    [openFamilyModalBtn, closeFamilyModalBtn, openTreatmentModalBtn, closeTreatmentModalBtn].forEach((button) => {
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
        const response = await fetch(`../../get_client_record.php?program=vawc&id=${encodeURIComponent(String(recordId))}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load VAWC record.');
        }

        originalRecordSnapshot = payload;
        populateRecord(payload.record, payload.related || {});
        setEditMode(false);

        recordControls = window.clientRecordMode?.mountControls({
            anchor: document.querySelector('.form-page'),
            submitButton,
            onEnableEdit: () => setEditMode(true),
            onCancelEdit: () => {
                populateRecord(originalRecordSnapshot.record, originalRecordSnapshot.related || {});
                setEditMode(false);
            },
            onSaveStateChange: () => {}
        }) || null;
    } catch (error) {
        showMessage(error.message);
    }
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
        const field = document.getElementById(id);
        const value = getFieldValue(id);
        let message = '';

        if (!value) {
            if (id === 'civil-status') {
                message = 'Please select this field.';
            } else if (id === 'age') {
                message = 'Please enter a valid age.';
            } else if (id === 'contact') {
                message = 'Please complete contact information.';
            } else {
                message = 'This field is required.';
            }
        } else if (id === 'age' && (!/^\d+$/.test(value) || Number(value) <= 0)) {
            message = 'Please enter a valid age.';
        }

        setFieldErrorMessage(field, message);
        if (message) {
            isValid = false;
        }
    });

    const presentingProblemField = document.querySelector('textarea[name="presenting-problem"]');
    const presentingProblem = getTextareaValue('presenting-problem');
    const presentingProblemMessage = presentingProblem ? '' : 'Please complete this field.';
    setFieldErrorMessage(presentingProblemField, presentingProblemMessage);
    if (presentingProblemMessage) {
        isValid = false;
    }

    const fourPs = getCheckedRadioValue('4ps');
    const uct = getCheckedRadioValue('uct');
    const nhts = getCheckedRadioValue('nhts');

    setRadioErrorMessage('4ps', fourPs ? '' : 'Please select a value.');
    setRadioErrorMessage('uct', uct ? '' : 'Please select a value.');
    setRadioErrorMessage('nhts', nhts ? '' : 'Please select a value.');

    if (!fourPs || !uct || !nhts) {
        isValid = false;
    }

    const familyTable = document.querySelector('.family-table');
    const familyErrorMessage = familyMembers.length ? '' : 'Please add at least one family information row.';
    setSectionErrorMessage(familyTable, familyErrorMessage);
    if (familyErrorMessage) {
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
        setFieldErrorMessage(field, '');
    });

    document.querySelectorAll('.radio-option').forEach((option) => {
        option.classList.remove('invalid-input');
    });
    setRadioErrorMessage('4ps', '');
    setRadioErrorMessage('uct', '');
    setRadioErrorMessage('nhts', '');
    setSectionErrorMessage(document.querySelector('.family-table'), '');

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
    setSectionErrorMessage(document.querySelector('.family-table'), '');
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
        showMessage('Please review the highlighted fields and correct the errors before submitting.');
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

        showMessage(recordId ? 'VAWC record updated successfully.' : 'VAWC record saved successfully.');

        if (recordId) {
            window.location.href = `${window.location.pathname}?id=${encodeURIComponent(String(recordId))}`;
            return;
        }

        resetMainForm();
    } catch (error) {
        showMessage(error.message);
    } finally {
        submitButton.disabled = false;
    }
});

loadRecordForViewMode();

document.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (target.matches('input, textarea, select')) {
        const value = target.value?.trim?.() ?? '';
        if (value) {
            setFieldErrorMessage(target, '');
        }
    }
});

document.addEventListener('change', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
        return;
    }

    if (target.type === 'radio' && target.name) {
        setRadioErrorMessage(target.name, '');
    }
});
