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
const openHouseholdModal = document.getElementById('openHouseholdModal');
const closeHouseholdModal = document.getElementById('closeHouseholdModal');
const saveHouseholdMemberButton = document.getElementById('saveHouseholdMember');
const householdMembersBody = document.getElementById('householdMembersBody');
const householdMembersInput = document.getElementById('householdMembersInput');
const formMessage = document.getElementById('formMessage');
const pwudForm = document.getElementById('pwudForm');
const submitPwudFormButton = document.getElementById('submitPwudForm');
const recordId = window.clientRecordMode?.getRecordId() ?? null;

const householdFields = {
    household_name: document.getElementById('householdName'),
    household_age: document.getElementById('householdAge'),
    household_gender: document.getElementById('householdGender'),
    household_relationship: document.getElementById('householdRelationship'),
    household_occupation: document.getElementById('householdOccupation')
};

const householdMembers = [];
let originalRecordSnapshot = null;
let recordControls = null;

function showHouseholdModal() {
    householdModal.classList.add('show');
    householdModal.setAttribute('aria-hidden', 'false');
}

function hideHouseholdModal() {
    householdModal.classList.remove('show');
    householdModal.setAttribute('aria-hidden', 'true');
}

function clearModalInputs() {
    Object.values(householdFields).forEach((field) => {
        field.value = '';
        field.classList.remove('invalid-input');
    });
}

function validateModalFields() {
    let isValid = true;

    Object.values(householdFields).forEach((field) => {
        if (!field.value.trim()) {
            field.classList.add('invalid-input');
            isValid = false;
        } else {
            field.classList.remove('invalid-input');
        }
    });

    return isValid;
}

function renderHouseholdMembers() {
    if (!householdMembers.length) {
        householdMembersBody.innerHTML = `
            <tr class="placeholder-row">
                <td colspan="5">No household member added yet.</td>
            </tr>
        `;
        return;
    }

    householdMembersBody.innerHTML = householdMembers.map((member) => `
        <tr>
            <td>${member.household_name}</td>
            <td>${member.household_age}</td>
            <td>${member.household_gender}</td>
            <td>${member.household_relationship}</td>
            <td>${member.household_occupation}</td>
        </tr>
    `).join('');
}

function updateHouseholdInput() {
    householdMembersInput.value = JSON.stringify(householdMembers);
}

function clearFormMessage() {
    formMessage.textContent = '';
    formMessage.classList.remove('error-message');
    formMessage.classList.remove('success-message');
}

function populateForm(record, related = {}) {
    const fieldMappings = {
        first_name: 'firstName',
        middle_name: 'middleName',
        last_name: 'lastName',
        suffix: 'suffix',
        date_of_birth: 'dateOfBirth',
        place_of_birth: 'placeOfBirth',
        age: 'age',
        address: 'address',
        barangay: 'barangay',
        religion: 'religion',
        sex: 'sex',
        educational_attainment: 'educationalAttainment',
        occupation: 'occupation',
        monthly_income: 'monthlyIncome',
        civil_status: 'civilStatus',
        contact_number: 'contactNumber',
        services_needed: 'servicesNeeded'
    };

    Object.entries(fieldMappings).forEach(([name, id]) => {
        const field = document.getElementById(id);
        if (!field) {
            return;
        }

        field.value = record?.[name] ?? '';
    });

    householdMembers.length = 0;
    (related.household_members || []).forEach((member) => {
        householdMembers.push({
            household_name: member.household_name ?? '',
            household_age: member.household_age ?? '',
            household_gender: member.household_gender ?? '',
            household_relationship: member.household_relationship ?? '',
            household_occupation: member.household_occupation ?? ''
        });
    });

    renderHouseholdMembers();
    updateHouseholdInput();
    clearFormMessage();
}

function setEditMode(isEditable) {
    window.clientRecordMode?.toggleContainerEditState(pwudForm, isEditable, ['#openHouseholdModal', '#saveHouseholdMember', '#closeHouseholdModal']);

    if (openHouseholdModal) {
        openHouseholdModal.disabled = !isEditable;
    }

    if (saveHouseholdMemberButton) {
        saveHouseholdMemberButton.disabled = !isEditable;
    }
}

async function loadRecordForViewMode() {
    if (!recordId) {
        return;
    }

    formMessage.textContent = 'Loading record...';

    try {
        const response = await fetch(`../../get_client_record.php?program=pwud&id=${encodeURIComponent(String(recordId))}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load PWUD record.');
        }

        originalRecordSnapshot = payload;
        populateForm(payload.record, payload.related || {});
        setEditMode(false);

        recordControls = window.clientRecordMode?.mountControls({
            anchor: document.querySelector('.form-page'),
            submitButton: submitPwudFormButton,
            onEnableEdit: () => {
                setEditMode(true);
                clearFormMessage();
            },
            onCancelEdit: () => {
                populateForm(originalRecordSnapshot.record, originalRecordSnapshot.related || {});
                setEditMode(false);
            },
            onSaveStateChange: () => {}
        }) || null;
    } catch (error) {
        formMessage.textContent = error.message;
        formMessage.classList.add('error-message');
    }
}

if (openHouseholdModal && closeHouseholdModal && householdModal) {
    openHouseholdModal.addEventListener('click', showHouseholdModal);
    closeHouseholdModal.addEventListener('click', hideHouseholdModal);
    householdModal.addEventListener('click', (event) => {
        if (event.target === householdModal) {
            hideHouseholdModal();
        }
    });
}

if (saveHouseholdMemberButton) {
    saveHouseholdMemberButton.addEventListener('click', () => {
        if (!validateModalFields()) {
            alert('Please complete all household member fields before saving.');
            return;
        }

        householdMembers.push({
            household_name: householdFields.household_name.value.trim(),
            household_age: householdFields.household_age.value.trim(),
            household_gender: householdFields.household_gender.value.trim(),
            household_relationship: householdFields.household_relationship.value.trim(),
            household_occupation: householdFields.household_occupation.value.trim()
        });

        renderHouseholdMembers();
        updateHouseholdInput();
        clearModalInputs();
        hideHouseholdModal();
    });
}

function validateMainForm() {
    let isValid = true;
    const optionalFieldNames = new Set(['middle_name', 'suffix']);
    const requiredFields = pwudForm.querySelectorAll('input[name], select[name], textarea[name]');

    requiredFields.forEach((field) => {
        if (optionalFieldNames.has(field.name) || field.type === 'hidden') {
            field.classList.remove('invalid-input');
            return;
        }

        if (!field.value.trim()) {
            field.classList.add('invalid-input');
            isValid = false;
        } else {
            field.classList.remove('invalid-input');
        }
    });

    if (!householdMembers.length) {
        isValid = false;
        formMessage.textContent = 'Please add at least one complete household member in the modal before submitting.';
        formMessage.classList.add('error-message');
    } else {
        formMessage.textContent = '';
        formMessage.classList.remove('error-message');
    }

    return isValid;
}

pwudForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!validateMainForm()) {
        if (!formMessage.textContent) {
            formMessage.textContent = 'Please fill in all required fields before submitting.';
            formMessage.classList.add('error-message');
        }
        return;
    }

    const confirmed = await window.clientFormSubmitConfirmation?.confirm();
    if (!confirmed) {
        return;
    }

    formMessage.textContent = 'Submitting...';
    formMessage.classList.remove('error-message');
    formMessage.classList.remove('success-message');

    const formData = new FormData(pwudForm);
    if (recordId) {
        formData.append('record_id', String(recordId));
    }

    try {
        const response = await fetch('pwud_submit.php', {
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
            throw new Error((result && result.message) || 'Failed to save PWUD record.');
        }

        formMessage.textContent = recordId ? 'PWUD record updated successfully.' : 'PWUD record saved successfully.';
        formMessage.classList.add('success-message');

        if (recordId) {
            originalRecordSnapshot = {
                record: Object.fromEntries(formData.entries()),
                related: {
                    household_members: householdMembers.map((member) => ({ ...member }))
                }
            };
            setEditMode(false);
            recordControls?.setEditing(false);
        } else {
            pwudForm.reset();
            householdMembers.length = 0;
            renderHouseholdMembers();
            updateHouseholdInput();
        }
    } catch (error) {
        formMessage.textContent = error.message;
        formMessage.classList.add('error-message');
    }
});

loadRecordForViewMode();
