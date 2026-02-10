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

const pyapForm = document.getElementById('pyapForm');
const formMessage = document.getElementById('formMessage');
const familyTableBody = document.getElementById('familyTableBody');
const membershipTableBody = document.getElementById('membershipTableBody');
const familyInput = document.getElementById('familyCompositionInput');
const membershipInput = document.getElementById('membershipsInput');
const saveFamilyBtn = document.getElementById('saveFamilyBtn');
const saveMembershipBtn = document.getElementById('saveMembershipBtn');

const familyFields = {
    name: document.getElementById('family_name'),
    relationship: document.getElementById('family_relationship'),
    gender: document.getElementById('family_gender'),
    age: document.getElementById('family_age'),
    grade_year: document.getElementById('family_grade_year'),
    isy: document.getElementById('family_isy'),
    osy: document.getElementById('family_osy'),
    work_labor_experience: document.getElementById('family_work_labor_experience'),
    income: document.getElementById('family_income')
};

const membershipFields = {
    organization_name: document.getElementById('membership_organization_name'),
    position_held: document.getElementById('membership_position_held'),
    year: document.getElementById('membership_year')
};

const familyCompositions = [];
const memberships = [];

function renderFamilyRows() {
    if (!familyCompositions.length) {
        familyTableBody.innerHTML = '<tr class="placeholder-row"><td colspan="9">No family member added yet.</td></tr>';
        return;
    }

    familyTableBody.innerHTML = familyCompositions.map((member) => `
        <tr>
            <td>${member.name}</td>
            <td>${member.relationship}</td>
            <td>${member.gender}</td>
            <td>${member.age}</td>
            <td>${member.grade_year}</td>
            <td>${member.isy}</td>
            <td>${member.osy}</td>
            <td>${member.work_labor_experience}</td>
            <td>${member.income}</td>
        </tr>
    `).join('');
}

function renderMembershipRows() {
    if (!memberships.length) {
        membershipTableBody.innerHTML = '<tr class="placeholder-row"><td colspan="3">No membership added yet.</td></tr>';
        return;
    }

    membershipTableBody.innerHTML = memberships.map((member) => `
        <tr>
            <td>${member.organization_name}</td>
            <td>${member.position_held}</td>
            <td>${member.year}</td>
        </tr>
    `).join('');
}

function clearFields(fields) {
    Object.values(fields).forEach((field) => {
        field.value = '';
        field.classList.remove('invalid-input');
    });
}

function updateJsonInputs() {
    familyInput.value = JSON.stringify(familyCompositions);
    membershipInput.value = JSON.stringify(memberships);
}

function validateFieldGroup(fields, allowAllEmpty = false) {
    const values = Object.values(fields).map((field) => field.value.trim());

    if (allowAllEmpty && values.every((value) => value === '')) {
        clearFields(fields);
        return 'all-empty';
    }

    let valid = true;
    Object.values(fields).forEach((field) => {
        if (!field.value.trim()) {
            field.classList.add('invalid-input');
            valid = false;
        } else {
            field.classList.remove('invalid-input');
        }
    });

    return valid ? 'valid' : 'invalid';
}

saveFamilyBtn.addEventListener('click', () => {
    const result = validateFieldGroup(familyFields);
    if (result !== 'valid') {
        alert('All family composition fields are required.');
        return;
    }

    familyCompositions.push(Object.fromEntries(
        Object.entries(familyFields).map(([key, field]) => [key, field.value.trim()])
    ));

    updateJsonInputs();
    renderFamilyRows();
    clearFields(familyFields);
    document.getElementById('family-modal-toggle').checked = false;
});

saveMembershipBtn.addEventListener('click', () => {
    const result = validateFieldGroup(membershipFields, true);

    if (result === 'all-empty') {
        document.getElementById('membership-modal-toggle').checked = false;
        return;
    }

    if (result !== 'valid') {
        alert('Complete all membership fields, or leave them all blank.');
        return;
    }

    memberships.push(Object.fromEntries(
        Object.entries(membershipFields).map(([key, field]) => [key, field.value.trim()])
    ));

    updateJsonInputs();
    renderMembershipRows();
    clearFields(membershipFields);
    document.getElementById('membership-modal-toggle').checked = false;
});

function validateCheckboxGroup(name) {
    const checkboxes = pyapForm.querySelectorAll(`input[name="${name}"]`);
    return Array.from(checkboxes).some((checkbox) => checkbox.checked);
}

function validateMainForm() {
    const optionalFields = new Set(['middle_name', 'suffix', 'ips']);
    let valid = true;

    pyapForm.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
        if (field.type === 'hidden' || field.type === 'checkbox' || optionalFields.has(field.name)) {
            field.classList.remove('invalid-input');
            return;
        }

        if (!field.value.trim()) {
            field.classList.add('invalid-input');
            valid = false;
        } else {
            field.classList.remove('invalid-input');
        }
    });

    if (!familyCompositions.length) {
        valid = false;
        formMessage.textContent = 'Please add at least one complete family composition entry.';
        formMessage.className = 'form-message error-message';
        return false;
    }

    if (!validateCheckboxGroup('assistance_received[]')) {
        valid = false;
        formMessage.textContent = 'Please select at least one Assistance & Intervention Received option.';
        formMessage.className = 'form-message error-message';
        return false;
    }

    if (!validateCheckboxGroup('intervention_needed[]')) {
        valid = false;
        formMessage.textContent = 'Please select at least one Intervention needed option.';
        formMessage.className = 'form-message error-message';
        return false;
    }

    if (!valid) {
        formMessage.textContent = 'Please complete all required fields before submitting.';
        formMessage.className = 'form-message error-message';
    }

    return valid;
}

pyapForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    updateJsonInputs();

    if (!validateMainForm()) {
        return;
    }

    formMessage.textContent = 'Submitting...';
    formMessage.className = 'form-message';

    const formData = new FormData(pyapForm);

    try {
        const response = await fetch('pyap_submit.php', {
            method: 'POST',
            body: formData
        });

        const responseType = response.headers.get('content-type') || '';
        const responseBody = await response.text();
        const result = responseType.includes('application/json') && responseBody ? JSON.parse(responseBody) : null;

        if (response.status === 405) {
            throw new Error('Submit endpoint is not accepting POST requests. Run this page in a PHP-enabled server.');
        }

        if (!response.ok || !result || !result.success) {
            throw new Error((result && result.message) || 'Failed to save PYAP record.');
        }

        formMessage.textContent = 'PYAP record saved successfully.';
        formMessage.className = 'form-message success-message';
        pyapForm.reset();
        familyCompositions.length = 0;
        memberships.length = 0;
        updateJsonInputs();
        renderFamilyRows();
        renderMembershipRows();
    } catch (error) {
        formMessage.textContent = error.message;
        formMessage.className = 'form-message error-message';
    }
});

updateJsonInputs();
renderFamilyRows();
renderMembershipRows();
