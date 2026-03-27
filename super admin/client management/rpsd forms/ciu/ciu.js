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

    const requiredInputs = [
        'first_name',
        'last_name',
        'birth_date',
        'birth_place',
        'age',
        'civil_status',
        'contact_number',
        'monthly_income',
        'religion',
        'occupation',
        'education',
        'address',
        'barangay'
    ];

    requiredInputs.forEach((name) => {
        const valid = getInputValue(name) !== '';
        setFieldValidity(name, valid);
        if (!valid) {
            isValid = false;
        }
    });

    const sexValue = getRadioValue('sex');
    setRadioValidity('sex', sexValue !== '');
    if (!sexValue) {
        isValid = false;
    }

    const memberCheckboxes = [
        'member_senior',
        'member_solo_parent',
        'member_pwd',
        'member_4ps'
    ];

    const serviceCheckboxes = [
        'service_prescribed_medicine',
        'service_certificate_minor',
        'service_hospital_bill',
        'service_tesda_certificate',
        'service_burial',
        'service_laboratory',
        'service_legal_assistance'
    ];

    const hasMemberSelection = memberCheckboxes.some((name) => isChecked(name));
    memberCheckboxes.forEach((name) => {
        setCheckboxValidity(name, hasMemberSelection);
    });
    if (!hasMemberSelection) {
        isValid = false;
    }

    let hasServiceSelection = false;
    if (!isScsrForm) {
        hasServiceSelection = serviceCheckboxes.some((name) => isChecked(name));
        serviceCheckboxes.forEach((name) => {
            setCheckboxValidity(name, hasServiceSelection);
        });
        if (!hasServiceSelection) {
            isValid = false;
        }
    }

    if (isScsrForm) {
        const hasPresentProblem = getInputValue('present_problem') !== '';
        const hasRecommendation = getInputValue('recommendation') !== '';
        const hasPrivacyConsent = isChecked('data_privacy_consent');

        setFieldValidity('present_problem', hasPresentProblem);
        setFieldValidity('recommendation', hasRecommendation);
        setCheckboxValidity('data_privacy_consent', hasPrivacyConsent);

        if (!hasPresentProblem || !hasRecommendation || !hasPrivacyConsent) {
            isValid = false;
        }
    }

    if (familyMembers.length === 0) {
        isValid = false;
    }

    return {
        isValid,
        sexValue,
        hasMemberSelection,
        hasServiceSelection
    };
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
        data_privacy_consent: isChecked('data_privacy_consent') ? 'Yes' : 'No',
        form_variant: formVariant,
        ...memberFlags,
        ...serviceFlags
    };
}

function resetForm() {
    ciuForm?.reset();

    ciuForm?.querySelectorAll('.invalid-input').forEach((node) => {
        node.classList.remove('invalid-input');
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
    familyForm?.reset();
    toggleModal(familyModal, false);
});

ciuForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const { isValid } = validateMainForm();

    if (!isValid) {
        alert(isScsrForm ? 'Please complete all required SCSR fields, confirm data privacy consent, and add at least one family composition row.' : 'Please complete all required CIU fields and add at least one family composition row.');
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

renderFamilyTable();

loadRecordForViewMode();
