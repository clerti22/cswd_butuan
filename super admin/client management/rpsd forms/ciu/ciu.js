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
const familyForm = document.querySelector('#familyModal .modal-form');
const saveFamilyEntryButton = document.getElementById('saveFamilyEntry');
const familyTableBody = document.querySelector('.family-table tbody');
const ciuForm = document.querySelector('.ciu-body');
const formVariant = String(ciuForm?.dataset.formVariant || 'ciu').toLowerCase() === 'scsr' ? 'scsr' : 'ciu';
const isScsrForm = formVariant === 'scsr';
const recordId = window.clientRecordMode?.getRecordId() ?? null;

const familyMembers = [];
let originalRecordSnapshot = null;
let recordControls = null;

function toggleModal(modal, isOpen) {
    if (!modal) {
        return;
    }

    modal.classList.toggle('active', isOpen);
    modal.setAttribute('aria-hidden', String(!isOpen));
}

function getInputValue(name) {
    return ciuForm?.querySelector(`[name="${name}"]`)?.value.trim() || '';
}

function getRadioValue(name) {
    return ciuForm?.querySelector(`input[name="${name}"]:checked`)?.value || '';
}

function isChecked(name) {
    return Boolean(ciuForm?.querySelector(`input[name="${name}"]:checked`));
}

function setFieldValidity(name, isValid) {
    const field = ciuForm?.querySelector(`[name="${name}"]`);
    if (field) {
        field.classList.toggle('invalid-input', !isValid);
    }
}

function setRadioValidity(name, isValid) {
    ciuForm?.querySelectorAll(`input[name="${name}"]`).forEach((item) => {
        const label = item.closest('label');
        if (label) {
            label.classList.toggle('invalid-input', !isValid);
        }
    });
}

function setCheckboxValidity(name, isValid) {
    const checkbox = ciuForm?.querySelector(`input[name="${name}"]`);
    const label = checkbox?.closest('label');
    if (label) {
        label.classList.toggle('invalid-input', !isValid);
    }
}

function setFieldErrorMessage(field, message) {
    if (!field) {
        return;
    }

    const container = field.closest('.field') || field.parentElement;
    if (!container) {
        return;
    }

    let errorNode = container.querySelector('.field-error');
    if (!message) {
        if (errorNode) {
            errorNode.remove();
        }
        return;
    }

    if (!errorNode) {
        errorNode = document.createElement('small');
        errorNode.className = 'field-error';
        container.appendChild(errorNode);
    }

    errorNode.textContent = message;
}

function setFieldValidationState(name, isValid, message = 'This field is required.') {
    const field = ciuForm?.querySelector(`[name="${name}"]`);
    setFieldValidity(name, isValid);
    setFieldErrorMessage(field, isValid ? '' : message);
}

function setGroupValidationState(selector, isValid, message) {
    const group = ciuForm?.querySelector(selector);
    if (!group) {
        return;
    }

    group.classList.toggle('invalid-group', !isValid);
    setFieldErrorMessage(group, isValid ? '' : message);
}

function setFamilyCompositionValidation(isValid) {
    const section = ciuForm?.querySelector('.family-section');
    const table = section?.querySelector('.family-table');

    if (!table) {
        return;
    }

    table.classList.toggle('invalid-input', !isValid);
    setFieldErrorMessage(table, isValid ? '' : 'Please add at least one family composition row.');
}

function clearFamilyTable() {
    if (!familyTableBody) {
        return;
    }

    familyTableBody.innerHTML = '<tr><td colspan="7"></td></tr>';
}

function renderFamilyTable() {
    if (!familyTableBody) {
        return;
    }

    familyTableBody.innerHTML = '';

    if (familyMembers.length === 0) {
        clearFamilyTable();
        return;
    }

    familyMembers.forEach((member) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${member.family_name}</td>
            <td>${member.family_age}</td>
            <td>${member.family_gender}</td>
            <td>${member.family_relationship}</td>
            <td>${member.family_education}</td>
            <td>${member.family_occupation}</td>
            <td>${member.family_income}</td>
        `;

        familyTableBody.appendChild(row);
    });
}

function readFamilyModalRow() {
    return {
        family_name: familyForm?.querySelector('[name="family_name"]')?.value.trim() || '',
        family_age: familyForm?.querySelector('[name="family_age"]')?.value.trim() || '',
        family_gender: familyForm?.querySelector('[name="family_gender"]')?.value || '',
        family_relationship: familyForm?.querySelector('[name="family_relationship"]')?.value.trim() || '',
        family_education: familyForm?.querySelector('[name="family_education"]')?.value.trim() || '',
        family_occupation: familyForm?.querySelector('[name="family_occupation"]')?.value.trim() || '',
        family_income: familyForm?.querySelector('[name="family_income"]')?.value.trim() || ''
    };
}

function validateFamilyRow(row) {
    return Object.values(row).every((value) => value !== '');
}

function setRadioValue(name, value) {
    ciuForm?.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
        input.checked = input.value === String(value ?? '');
    });
}

function setCheckboxValue(name, value) {
    const checkbox = ciuForm?.querySelector(`input[name="${name}"]`);
    if (checkbox) {
        checkbox.checked = String(value ?? '').trim().toLowerCase() === 'yes';
    }
}

function populateRecord(record, related = {}) {
    ciuForm?.querySelectorAll('input[name]:not([type="checkbox"]):not([type="radio"]), select[name], textarea[name]').forEach((field) => {
        field.value = record?.[field.name] ?? '';
    });

    setRadioValue('sex', record?.sex ?? '');

    [
        'member_senior',
        'member_solo_parent',
        'member_pwd',
        'member_4ps',
        'service_prescribed_medicine',
        'service_certificate_minor',
        'service_hospital_bill',
        'service_tesda_certificate',
        'service_burial',
        'service_laboratory',
        'service_legal_assistance'
    ].forEach((name) => setCheckboxValue(name, record?.[name] ?? 'No'));

    familyMembers.length = 0;
    (related.family_composition || []).forEach((member) => familyMembers.push({ ...member }));
    renderFamilyTable();
}

function setEditMode(isEditable) {
    window.clientRecordMode?.toggleContainerEditState(ciuForm, isEditable);
    [openFamilyModalBtn, closeFamilyModalBtn, saveFamilyEntryButton].forEach((button) => {
        if (button) {
            button.disabled = !isEditable;
        }
    });

    if (familyForm) {
        familyForm.querySelectorAll('button').forEach((button) => {
            button.disabled = !isEditable;
        });
    }

    const submitButton = ciuForm?.querySelector('.submit-btn');
    if (submitButton && recordId) {
        submitButton.disabled = !isEditable;
    }

    if (!isEditable) {
        toggleModal(familyModal, false);
    }
}

async function loadRecordForViewMode() {
    if (!recordId) {
        return;
    }

    try {
        const response = await fetch(`../../get_client_record.php?program=ciu&id=${encodeURIComponent(String(recordId))}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load CIU record.');
        }

        originalRecordSnapshot = payload;
        populateRecord(payload.record, payload.related || {});
        setEditMode(false);

        recordControls = window.clientRecordMode?.mountControls({
            anchor: ciuForm.closest('.form-page') || ciuForm,
            submitButton: ciuForm?.querySelector('.submit-btn'),
            onEnableEdit: () => setEditMode(true),
            onCancelEdit: () => {
                populateRecord(originalRecordSnapshot.record, originalRecordSnapshot.related || {});
                setEditMode(false);
            },
            onSaveStateChange: () => {}
        }) || null;
    } catch (error) {
        alert(error.message);
    }
}

function validateMainForm() {
    let isValid = true;

    const requiredFields = [
        { name: 'first_name' },
        { name: 'last_name' },
        { name: 'birth_date' },
        { name: 'birth_place' },
        {
            name: 'age',
            validator: (value) => /^\d+$/.test(value) && Number(value) >= 0,
            invalidMessage: 'Please enter a valid age.'
        },
        { name: 'civil_status' },
        { name: 'contact_number' },
        { name: 'monthly_income' },
        { name: 'religion' },
        { name: 'occupation' },
        { name: 'education' },
        { name: 'address' },
        { name: 'barangay' }
    ];

    requiredFields.forEach(({ name, validator, invalidMessage }) => {
        const value = getInputValue(name);
        const hasValue = value !== '';
        const valid = hasValue && (validator ? validator(value) : true);
        const message = !hasValue ? 'This field is required.' : (invalidMessage || 'This field is required.');
        setFieldValidationState(name, valid, message);
        if (!valid) {
            isValid = false;
        }
    });

    const sexValue = getRadioValue('sex');
    const sexValid = sexValue !== '';
    setRadioValidity('sex', sexValid);
    setGroupValidationState('.radio-group', sexValid, 'This field is required.');
    if (!sexValid) {
        isValid = false;
    }

    const memberCheckboxes = ['member_senior', 'member_solo_parent', 'member_pwd', 'member_4ps'];
    const serviceCheckboxes = ['service_prescribed_medicine', 'service_certificate_minor', 'service_hospital_bill', 'service_tesda_certificate', 'service_burial', 'service_laboratory', 'service_legal_assistance'];

    const hasMemberSelection = memberCheckboxes.some((name) => isChecked(name));
    memberCheckboxes.forEach((name) => setCheckboxValidity(name, hasMemberSelection));
    setGroupValidationState('.checkbox-grid', hasMemberSelection, 'Please select at least one client category.');
    if (!hasMemberSelection) {
        isValid = false;
    }

    let hasServiceSelection = false;
    if (!isScsrForm) {
        hasServiceSelection = serviceCheckboxes.some((name) => isChecked(name));
        serviceCheckboxes.forEach((name) => setCheckboxValidity(name, hasServiceSelection));
        setGroupValidationState('.services-grid', hasServiceSelection, 'Please select a service needed.');
        if (!hasServiceSelection) {
            isValid = false;
        }
    }

    if (isScsrForm) {
        const hasPresentProblem = getInputValue('present_problem') !== '';
        const hasRecommendation = getInputValue('recommendation') !== '';
        setFieldValidationState('present_problem', hasPresentProblem);
        setFieldValidationState('recommendation', hasRecommendation);

        if (!hasPresentProblem || !hasRecommendation) {
            isValid = false;
        }
    }

    const hasFamilyComposition = familyMembers.length > 0;
    setFamilyCompositionValidation(hasFamilyComposition);
    if (!hasFamilyComposition) {
        isValid = false;
    }

    return { isValid, sexValue, hasMemberSelection, hasServiceSelection };
}

function collectMainFormData() {
    const memberFlags = {
        member_senior: isChecked('member_senior') ? 'Yes' : 'No',
        member_solo_parent: isChecked('member_solo_parent') ? 'Yes' : 'No',
        member_pwd: isChecked('member_pwd') ? 'Yes' : 'No',
        member_4ps: isChecked('member_4ps') ? 'Yes' : 'No'
    };

    const serviceFlags = isScsrForm
        ? {
            service_prescribed_medicine: 'No',
            service_certificate_minor: 'No',
            service_hospital_bill: 'No',
            service_tesda_certificate: 'No',
            service_burial: 'No',
            service_laboratory: 'No',
            service_legal_assistance: 'No'
        }
        : {
            service_prescribed_medicine: isChecked('service_prescribed_medicine') ? 'Yes' : 'No',
            service_certificate_minor: isChecked('service_certificate_minor') ? 'Yes' : 'No',
            service_hospital_bill: isChecked('service_hospital_bill') ? 'Yes' : 'No',
            service_tesda_certificate: isChecked('service_tesda_certificate') ? 'Yes' : 'No',
            service_burial: isChecked('service_burial') ? 'Yes' : 'No',
            service_laboratory: isChecked('service_laboratory') ? 'Yes' : 'No',
            service_legal_assistance: isChecked('service_legal_assistance') ? 'Yes' : 'No'
        };

    return {
        first_name: getInputValue('first_name'),
        middle_name: getInputValue('middle_name'),
        last_name: getInputValue('last_name'),
        suffix: getInputValue('suffix'),
        birth_date: getInputValue('birth_date'),
        birth_place: getInputValue('birth_place'),
        age: getInputValue('age'),
        sex: getRadioValue('sex'),
        civil_status: getInputValue('civil_status'),
        contact_number: getInputValue('contact_number'),
        monthly_income: getInputValue('monthly_income'),
        religion: getInputValue('religion'),
        occupation: getInputValue('occupation'),
        education: getInputValue('education'),
        address: getInputValue('address'),
        barangay: getInputValue('barangay'),
        member_others: getInputValue('member_others'),
        service_others: isScsrForm ? '' : getInputValue('service_others'),
        present_problem: getInputValue('present_problem'),
        recommendation: getInputValue('recommendation'),
        form_variant: formVariant,
        ...memberFlags,
        ...serviceFlags
    };
}

function resetForm() {
    ciuForm?.reset();

    ciuForm?.querySelectorAll('.invalid-input, .invalid-group').forEach((node) => {
        node.classList.remove('invalid-input', 'invalid-group');
    });

    ciuForm?.querySelectorAll('.field-error').forEach((node) => {
        node.remove();
    });

    familyMembers.length = 0;
    renderFamilyTable();

    familyForm?.reset();
}

openFamilyModalBtn?.addEventListener('click', () => toggleModal(familyModal, true));
closeFamilyModalBtn?.addEventListener('click', () => toggleModal(familyModal, false));

familyModal?.addEventListener('click', (event) => {
    if (event.target === familyModal) {
        toggleModal(familyModal, false);
    }
});

saveFamilyEntryButton?.addEventListener('click', () => {
    const row = readFamilyModalRow();

    if (!validateFamilyRow(row)) {
        alert('Please complete all family composition fields before saving.');
        return;
    }

    familyMembers.push(row);
    renderFamilyTable();
    setFamilyCompositionValidation(true);
    familyForm?.reset();
    toggleModal(familyModal, false);
});

ciuForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const { isValid } = validateMainForm();

    if (!isValid) {
        return;
    }

    const confirmed = await window.clientFormSubmitConfirmation?.confirm();
    if (!confirmed) {
        return;
    }

    const submitButton = ciuForm.querySelector('.submit-btn');
    if (submitButton) {
        submitButton.disabled = true;
    }

    const payload = collectMainFormData();
    const formData = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        formData.append(key, value);
    });

    if (recordId) {
        formData.append('record_id', String(recordId));
    }

    formData.append('family_composition', JSON.stringify(familyMembers));

    try {
        const response = await fetch('ciu_submit.php', {
            method: 'POST',
            body: formData
        });

        const contentType = response.headers.get('content-type') || '';
        const responseText = await response.text();
        const result = contentType.includes('application/json') && responseText ? JSON.parse(responseText) : null;

        if (response.status === 405) {
            throw new Error('Submit endpoint is not accepting POST requests. Please run this page in a PHP-enabled server.');
        }

        if (!response.ok || !result || !result.success) {
            throw new Error((result && result.message) || `Failed to save ${isScsrForm ? 'SCSR' : 'CIU'} record.`);
        }

        alert(recordId ? `${isScsrForm ? 'SCSR' : 'CIU'} record updated successfully.` : `${isScsrForm ? 'SCSR' : 'CIU'} record saved successfully.`);

        if (recordId) {
            const viewParams = new URLSearchParams({ id: String(recordId) });
            if (isScsrForm) {
                viewParams.set('variant', 'scsr');
            }
            window.location.href = `${window.location.pathname}?${viewParams.toString()}`;
            return;
        }

        resetForm();
    } catch (error) {
        alert(error.message);
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
        }
    }
});

ciuForm?.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (target.matches('input[name], select[name], textarea[name]')) {
        target.classList.remove('invalid-input');
        setFieldErrorMessage(target, '');
    }

    if (target.matches('input[name="sex"]')) {
        const valid = getRadioValue('sex') !== '';
        setRadioValidity('sex', valid);
        setGroupValidationState('.radio-group', valid, 'This field is required.');
    }

    if (target.matches('input[name^="member_"]')) {
        const valid = ['member_senior', 'member_solo_parent', 'member_pwd', 'member_4ps'].some((name) => isChecked(name));
        setGroupValidationState('.checkbox-grid', valid, 'Please select at least one client category.');
    }

    if (target.matches('input[name^="service_"]')) {
        const valid = ['service_prescribed_medicine', 'service_certificate_minor', 'service_hospital_bill', 'service_tesda_certificate', 'service_burial', 'service_laboratory', 'service_legal_assistance'].some((name) => isChecked(name));
        setGroupValidationState('.services-grid', valid, 'Please select a service needed.');
    }
});

renderFamilyTable();

loadRecordForViewMode();
