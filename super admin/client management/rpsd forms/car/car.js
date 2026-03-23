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
document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', () => {
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

const treatmentModal = document.getElementById('treatmentModal');
const openTreatmentModalBtn = document.getElementById('openTreatmentModal');
const closeTreatmentModalBtn = document.getElementById('closeTreatmentModal');
const treatmentForm = document.getElementById('treatmentForm');
const treatmentTableBody = document.getElementById('treatmentTableBody');

const submitButton = document.querySelector('.final-submit-btn');
const formMessage = document.getElementById('carFormMessage');
const recordId = window.clientRecordMode?.getRecordId() ?? null;
const formPage = document.querySelector('.form-page');

const householdMembers = [];
const treatmentPlans = [];

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

function openTreatmentModal() {
    if (!treatmentModal) return;
    treatmentModal.style.display = 'flex';
    treatmentModal.setAttribute('aria-hidden', 'false');
}

function closeTreatmentModal() {
    if (!treatmentModal) return;
    treatmentModal.style.display = 'none';
    treatmentModal.setAttribute('aria-hidden', 'true');
    treatmentForm?.reset();
}

function setMessage(message, type) {
    if (!formMessage) {
        alert(message);
        return;
    }

    formMessage.textContent = message;
    formMessage.classList.remove('form-message--success', 'form-message--error');

    if (type === 'success') {
        formMessage.classList.add('form-message--success');
    }

    if (type === 'error') {
        formMessage.classList.add('form-message--error');
    }
}

function getFieldValue(id) {
    return document.getElementById(id)?.value.trim() || '';
}

function markFieldValidity(id, isValid) {
    const field = document.getElementById(id);
    if (!field) return;

    if (isValid) {
        field.classList.remove('invalid-input');
        return;
    }

    field.classList.add('invalid-input');
}

function getCheckedCheckboxLabels(selector) {
    const checkedInputs = document.querySelectorAll(`${selector} input[type="checkbox"]:checked`);
    return Array.from(checkedInputs).map((input) => {
        const label = input.closest('label');
        return label ? label.textContent.trim() : '';
    }).filter((label) => label !== '');
}

function getCheckedRadioValue(name) {
    return document.querySelector(`input[name="${name}"]:checked`)?.value || '';
}

function clearTableBody(tableBody, colspan) {
    if (!tableBody) return;
    tableBody.innerHTML = `<tr class="household-empty-row"><td colspan="${colspan}"></td></tr>`;
}

function setRadioValue(name, value) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
        input.checked = input.value === String(value ?? '');
    });
}

function setCheckboxGroupByLabel(selector, values) {
    const normalizedValues = new Set(
        String(values ?? '')
            .split(',')
            .map((value) => value.trim().toLowerCase())
            .filter((value) => value !== '')
    );

    document.querySelectorAll(`${selector} .check-option`).forEach((option) => {
        const checkbox = option.querySelector('input[type="checkbox"]');
        if (!checkbox) {
            return;
        }

        const labelText = option.textContent.replace(/\s+/g, ' ').trim().toLowerCase();
        checkbox.checked = normalizedValues.has(labelText);
    });
}

function renderHouseholdTable() {
    clearTableBody(householdTableBody, 8);

    if (!householdMembers.length || !householdTableBody) {
        return;
    }

    householdTableBody.innerHTML = '';
    householdMembers.forEach((rowData) => {
        const row = document.createElement('tr');
        [
            rowData.member_name,
            rowData.member_age,
            rowData.member_gender,
            rowData.member_status,
            rowData.member_relation,
            rowData.member_work,
            rowData.member_income,
            rowData.member_remarks ?? ''
        ].forEach((value) => {
            const cell = document.createElement('td');
            cell.textContent = value ?? '';
            row.appendChild(cell);
        });
        householdTableBody.appendChild(row);
    });
}

function renderTreatmentTable() {
    clearTableBody(treatmentTableBody, 5);

    if (!treatmentPlans.length || !treatmentTableBody) {
        return;
    }

    treatmentTableBody.innerHTML = '';
    treatmentPlans.forEach((rowData) => {
        const row = document.createElement('tr');
        [
            rowData.objectives,
            rowData.activities,
            rowData.responsible_person,
            rowData.resources_needed,
            rowData.expected_output
        ].forEach((value) => {
            const cell = document.createElement('td');
            cell.textContent = value ?? '';
            row.appendChild(cell);
        });
        treatmentTableBody.appendChild(row);
    });
}

function populateRecord(record, related = {}) {
    const fieldMappings = {
        'interview-date': 'interview_date',
        'first-name': 'first_name',
        'middle-name': 'middle_name',
        'last-name': 'last_name',
        suffix: 'suffix',
        nickname: 'nickname',
        'birth-date': 'birth_date',
        'birth-place': 'birth_place',
        religion: 'religion',
        'civil-status': 'civil_status',
        age: 'age',
        sex: 'sex',
        education: 'education',
        'present-address': 'present_address',
        'permanent-address': 'permanent_address',
        barangay: 'barangay',
        'spouse-first-name': 'spouse_first_name',
        'spouse-middle-name': 'spouse_middle_name',
        'spouse-last-name': 'spouse_last_name',
        'spouse-suffix': 'spouse_suffix',
        'years-together': 'monthly_income_estimate',
        'number-children': 'income_source',
        'food-expense': 'food_expense',
        'house-lot-expense': 'house_lot_expense',
        'education-expense': 'education_expense',
        'transport-expense': 'transport_expense',
        'misc-expense': 'misc_expense',
        'total-expenses': 'total_expenses',
        'total-income': 'total_income',
        'net-income': 'net_income',
        'classification-other': 'classification_other',
        'abuse-other': 'abuse_other',
        'type-offense': 'type_of_offense',
        'date-apprehension': 'date_of_apprehension',
        'by-whom': 'by_whom',
        'date-offense': 'date_of_offense',
        'source-info': 'source_of_information',
        complainant: 'complainant',
        'presented-problem': 'presented_problem',
        'family-environment': 'family_environment',
        'community-description': 'community_description',
        'family-history-background': 'family_history_background',
        recommendation: 'recommendation'
    };

    Object.entries(fieldMappings).forEach(([fieldId, recordKey]) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = record?.[recordKey] ?? '';
        }
    });

    setRadioValue('assistance-status', record?.assistance_status ?? '');
    setCheckboxGroupByLabel('.classification-grid', record?.classification_tags ?? '');
    setCheckboxGroupByLabel('.abuse-classification-form', record?.abuse_tags ?? '');

    householdMembers.length = 0;
    (related.family_composition || []).forEach((member) => householdMembers.push({ ...member }));
    renderHouseholdTable();

    treatmentPlans.length = 0;
    (related.initial_treatment_plan || []).forEach((plan) => treatmentPlans.push({ ...plan }));
    renderTreatmentTable();
}

function enableViewMode() {
    if (formPage) {
        window.clientRecordMode?.toggleContainerEditState(formPage, false);
    }

    [
        openHouseholdModalBtn,
        closeHouseholdModalBtn,
        openTreatmentModalBtn,
        closeTreatmentModalBtn
    ].forEach((button) => {
        if (button) {
            button.disabled = true;
        }
    });

    householdForm?.querySelectorAll('button').forEach((button) => {
        button.disabled = true;
    });
    treatmentForm?.querySelectorAll('button').forEach((button) => {
        button.disabled = true;
    });

    if (submitButton) {
        submitButton.hidden = true;
        submitButton.disabled = true;
    }
}

async function loadRecordForViewMode() {
    if (!recordId) {
        return;
    }

    setMessage('Loading record...', '');

    try {
        const response = await fetch(`../../get_client_record.php?program=car&id=${encodeURIComponent(String(recordId))}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load record.');
        }

        populateRecord(payload.record, payload.related || {});
        enableViewMode();
        setMessage('', '');
    } catch (error) {
        setMessage(error.message, 'error');
    }
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

    const rowData = {
        member_name: getFieldValue('hm-name'),
        member_age: getFieldValue('hm-age'),
        member_gender: getFieldValue('hm-gender'),
        member_status: getFieldValue('hm-status'),
        member_relation: getFieldValue('hm-relation'),
        member_work: getFieldValue('hm-work'),
        member_income: getFieldValue('hm-income'),
        member_remarks: getFieldValue('hm-remarks')
    };

    householdMembers.push(rowData);

    const emptyRow = householdTableBody?.querySelector('.household-empty-row');
    if (emptyRow) {
        emptyRow.remove();
    }

    const row = document.createElement('tr');
    Object.values(rowData).forEach((value) => {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
    });

    householdTableBody?.appendChild(row);
    closeHouseholdModal();
});

openTreatmentModalBtn?.addEventListener('click', openTreatmentModal);
closeTreatmentModalBtn?.addEventListener('click', closeTreatmentModal);

treatmentModal?.addEventListener('click', (event) => {
    if (event.target === treatmentModal) {
        closeTreatmentModal();
    }
});

treatmentForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const rowData = {
        objectives: getFieldValue('tm-objectives'),
        activities: getFieldValue('tm-activities'),
        responsible_person: getFieldValue('tm-responsible'),
        resources_needed: getFieldValue('tm-resources'),
        expected_output: getFieldValue('tm-output')
    };

    treatmentPlans.push(rowData);

    const emptyRow = treatmentTableBody?.querySelector('.household-empty-row');
    if (emptyRow) {
        emptyRow.remove();
    }

    const row = document.createElement('tr');
    Object.values(rowData).forEach((value) => {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
    });

    treatmentTableBody?.appendChild(row);
    closeTreatmentModal();
});

function validateForm() {
    let isValid = true;

    const requiredFieldIds = [
        'interview-date',
        'first-name',
        'last-name',
        'nickname',
        'birth-date',
        'birth-place',
        'religion',
        'civil-status',
        'age',
        'sex',
        'education',
        'present-address',
        'permanent-address',
        'barangay',
        'years-together',
        'number-children',
        'food-expense',
        'house-lot-expense',
        'education-expense',
        'transport-expense',
        'misc-expense',
        'total-expenses',
        'total-income',
        'net-income',
        'type-offense',
        'date-apprehension',
        'by-whom',
        'date-offense',
        'source-info',
        'complainant',
        'presented-problem',
        'family-environment',
        'community-description',
        'family-history-background'
    ];

    requiredFieldIds.forEach((id) => {
        const value = getFieldValue(id);
        const fieldIsValid = value !== '';
        markFieldValidity(id, fieldIsValid);
        if (!fieldIsValid) {
            isValid = false;
        }
    });

    const assistanceStatus = getCheckedRadioValue('assistance-status');
    if (!assistanceStatus) {
        isValid = false;
    }

    const classificationSelections = getCheckedCheckboxLabels('.classification-grid');
    if (!classificationSelections.length) {
        isValid = false;
    }

    const abuseSelections = getCheckedCheckboxLabels('.abuse-classification-form');
    if (!abuseSelections.length) {
        isValid = false;
    }

    if (!householdMembers.length) {
        isValid = false;
    }

    return {
        isValid,
        assistanceStatus,
        classificationSelections,
        abuseSelections
    };
}

function resetMainForm() {
    document.querySelectorAll('input, select, textarea').forEach((field) => {
        if (field.closest('.modal')) {
            return;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = false;
        } else {
            field.value = '';
        }

        field.classList.remove('invalid-input');
    });

    householdMembers.length = 0;
    treatmentPlans.length = 0;
    clearTableBody(householdTableBody, 8);
    clearTableBody(treatmentTableBody, 5);
}

submitButton?.addEventListener('click', async () => {
    setMessage('', '');

    const { isValid, assistanceStatus, classificationSelections, abuseSelections } = validateForm();
    if (!isValid) {
        setMessage('Please complete all required fields, options, and radio selections before submitting.', 'error');
        return;
    }

    const confirmed = await window.clientFormSubmitConfirmation?.confirm();
    if (!confirmed) {
        return;
    }

    submitButton.disabled = true;
    setMessage('Submitting...', '');

    const formData = new FormData();
    formData.append('interview_date', getFieldValue('interview-date'));
    formData.append('first_name', getFieldValue('first-name'));
    formData.append('middle_name', getFieldValue('middle-name'));
    formData.append('last_name', getFieldValue('last-name'));
    formData.append('suffix', getFieldValue('suffix'));
    formData.append('nickname', getFieldValue('nickname'));
    formData.append('birth_date', getFieldValue('birth-date'));
    formData.append('birth_place', getFieldValue('birth-place'));
    formData.append('religion', getFieldValue('religion'));
    formData.append('civil_status', getFieldValue('civil-status'));
    formData.append('age', getFieldValue('age'));
    formData.append('sex', getFieldValue('sex'));
    formData.append('education', getFieldValue('education'));
    formData.append('present_address', getFieldValue('present-address'));
    formData.append('barangay', getFieldValue('barangay'));
    formData.append('permanent_address', getFieldValue('permanent-address'));
    formData.append('spouse_first_name', getFieldValue('spouse-first-name'));
    formData.append('spouse_middle_name', getFieldValue('spouse-middle-name'));
    formData.append('spouse_last_name', getFieldValue('spouse-last-name'));
    formData.append('spouse_suffix', getFieldValue('spouse-suffix'));
    formData.append('monthly_income_estimate', getFieldValue('years-together'));
    formData.append('income_source', getFieldValue('number-children'));
    formData.append('assistance_status', assistanceStatus);

    formData.append('food_expense', getFieldValue('food-expense'));
    formData.append('house_lot_expense', getFieldValue('house-lot-expense'));
    formData.append('education_expense', getFieldValue('education-expense'));
    formData.append('transport_expense', getFieldValue('transport-expense'));
    formData.append('misc_expense', getFieldValue('misc-expense'));
    formData.append('total_expenses', getFieldValue('total-expenses'));
    formData.append('total_income', getFieldValue('total-income'));
    formData.append('net_income', getFieldValue('net-income'));

    formData.append('classification_tags', JSON.stringify(classificationSelections));
    formData.append('classification_other', getFieldValue('classification-other'));
    formData.append('abuse_tags', JSON.stringify(abuseSelections));
    formData.append('abuse_other', getFieldValue('abuse-other'));

    formData.append('type_of_offense', getFieldValue('type-offense'));
    formData.append('date_of_apprehension', getFieldValue('date-apprehension'));
    formData.append('by_whom', getFieldValue('by-whom'));
    formData.append('date_of_offense', getFieldValue('date-offense'));
    formData.append('source_of_information', getFieldValue('source-info'));
    formData.append('complainant', getFieldValue('complainant'));

    formData.append('presented_problem', getFieldValue('presented-problem'));
    formData.append('family_environment', getFieldValue('family-environment'));
    formData.append('community_description', getFieldValue('community-description'));
    formData.append('family_history_background', getFieldValue('family-history-background'));

    formData.append('recommendation', getFieldValue('recommendation'));
    formData.append('family_composition', JSON.stringify(householdMembers));
    formData.append('initial_treatment_plan', JSON.stringify(treatmentPlans));

    try {
        const response = await fetch('car_submit.php', {
            method: 'POST',
            body: formData
        });

        const responseType = response.headers.get('content-type') || '';
        const responseBody = await response.text();
        const result = responseType.includes('application/json') && responseBody
            ? JSON.parse(responseBody)
            : null;

        if (response.status === 405) {
            throw new Error('Submit endpoint is not accepting POST requests. Please run this page in a PHP-enabled server (e.g., XAMPP/Apache or php -S).');
        }

        if (!response.ok || !result || !result.success) {
            throw new Error((result && result.message) || 'Failed to save CAR record.');
        }

        setMessage('CAR record saved successfully.', 'success');
        resetMainForm();
    } catch (error) {
        setMessage(error.message, 'error');
    } finally {
        submitButton.disabled = false;
    }
});

loadRecordForViewMode();
