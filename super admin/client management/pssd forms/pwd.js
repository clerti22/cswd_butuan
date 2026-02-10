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

const householdModal = document.getElementById('householdModal');
const openHouseholdModal = document.getElementById('openHouseholdModal');
const closeHouseholdModal = document.getElementById('closeHouseholdModal');
const saveHouseholdMemberButton = document.getElementById('saveHouseholdMember');
const householdMembersBody = document.getElementById('householdMembersBody');
const householdMembersInput = document.getElementById('householdMembersInput');
const formMessage = document.getElementById('formMessage');
const pwudForm = document.getElementById('pwudForm');

const householdFields = {
    household_name: document.getElementById('householdName'),
    household_age: document.getElementById('householdAge'),
    household_gender: document.getElementById('householdGender'),
    household_relationship: document.getElementById('householdRelationship'),
    household_occupation: document.getElementById('householdOccupation')
};

const householdMembers = [];

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

    formMessage.textContent = 'Submitting...';
    formMessage.classList.remove('error-message');
    formMessage.classList.remove('success-message');

    const formData = new FormData(pwudForm);

    try {
        const response = await fetch('pwd_submit.php', {
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
            throw new Error((result && result.message) || 'Failed to save PWD record.');
        }

        formMessage.textContent = 'PWD record saved successfully.';
        formMessage.classList.add('success-message');
        pwudForm.reset();
        householdMembers.length = 0;
        renderHouseholdMembers();
        updateHouseholdInput();
    } catch (error) {
        formMessage.textContent = error.message;
        formMessage.classList.add('error-message');
    }
});
