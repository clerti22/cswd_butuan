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

const adminRegistrationForm = document.getElementById('admin-registration-form');
const registrationAlert = document.getElementById('registration-alert');
const registrationState = window.adminRegistrationState || null;

const roleField = document.getElementById('role');
const hiddenDivisionField = document.getElementById('division');
const adminDivisionFieldWrapper = document.getElementById('admin-division-field');
const adminDivisionField = document.getElementById('admin-division');
const encoderDivisionFieldWrapper = document.getElementById('encoder-division-field');
const encoderDivisionFields = document.querySelectorAll('input[name="encoder_division"]');
const programAssignedFieldWrapper = document.getElementById('program-assigned-field');
const programAssignedField = document.getElementById('program_assigned');

const divisionPrograms = {
    DPSD: ['PWUD', 'SOLO PARENT', 'PYAP'],
    PSSD: ['PWD', 'SENIOR CITIZEN'],
    RPSD: ['VAWC', 'CSAE', 'CICL', 'CAR', 'CIU'],
};
const allowedPositions = ['SWO I', 'SWO II', 'SWO III', 'SWO IV'];
const allowedGenders = ['Male', 'Female'];
const adminDivision = String(registrationState?.admin_division || '').trim().toUpperCase();
const divisionProgramsFromServer = Array.isArray(registrationState?.allowed_programs)
    ? registrationState.allowed_programs.map((program) => String(program || '').trim().toUpperCase()).filter(Boolean)
    : [];

function showRegistrationAlert(message, status) {
    if (!registrationAlert) {
        return;
    }

    registrationAlert.textContent = message;
    registrationAlert.classList.remove('success', 'error');

    if (status === 'success' || status === 'error') {
        registrationAlert.classList.add(status);
    }
}

function setFieldVisibility(fieldWrapper, isVisible) {
    if (!fieldWrapper) {
        return;
    }

    fieldWrapper.classList.toggle('hidden', !isVisible);
}

function resetEncoderDivisionSelection() {
    encoderDivisionFields.forEach((field) => {
        field.checked = false;
        field.required = false;
    });
}

function updateProgramAssignedOptions(divisionValue, selectedProgram = '') {
    if (!programAssignedField) {
        return;
    }

    const availablePrograms = divisionValue === adminDivision && divisionProgramsFromServer.length > 0
        ? divisionProgramsFromServer
        : (divisionPrograms[divisionValue] || []);
    programAssignedField.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Select program';
    programAssignedField.appendChild(defaultOption);

    availablePrograms.forEach((program) => {
        const option = document.createElement('option');
        option.value = program;
        option.textContent = program;
        option.selected = selectedProgram === program;
        programAssignedField.appendChild(option);
    });

    if (!availablePrograms.includes(selectedProgram)) {
        programAssignedField.value = '';
    }
}

function updateRoleBasedFields(selectedProgram = '') {
    if (!adminDivisionField || !hiddenDivisionField || !programAssignedField) {
        return;
    }

    setFieldVisibility(adminDivisionFieldWrapper, false);
    setFieldVisibility(encoderDivisionFieldWrapper, false);
    setFieldVisibility(programAssignedFieldWrapper, true);

    if (roleField) {
        roleField.value = 'encoder';
    }

    adminDivisionField.required = false;
    adminDivisionField.value = adminDivision;

    resetEncoderDivisionSelection();
    hiddenDivisionField.value = adminDivision;

    programAssignedField.required = true;
    updateProgramAssignedOptions(adminDivision, selectedProgram);
}

if (registrationState && registrationState.old && adminRegistrationForm) {
    Object.entries(registrationState.old).forEach(([fieldName, fieldValue]) => {
        const field = adminRegistrationForm.elements.namedItem(fieldName);
        if (field && typeof fieldValue === 'string') {
            field.value = fieldValue;
        }
    });

    if (registrationState.old.role === 'encoder') {
        const oldDivision = registrationState.old.division || '';
        encoderDivisionFields.forEach((field) => {
            field.checked = field.value === oldDivision;
        });
    } else if (registrationState.old.role === 'admin') {
        adminDivisionField.value = registrationState.old.division || '';
    }

    updateRoleBasedFields(registrationState.old.program_assigned || '');

    if (registrationState.message && registrationState.status) {
        showRegistrationAlert(registrationState.message, registrationState.status);
    }
}

if (adminRegistrationForm) {
    updateRoleBasedFields();

    adminRegistrationForm.addEventListener('submit', (event) => {
        const commonRequiredFields = [
            'full_name',
            'username',
            'email',
            'phone_number',
            'gender',
            'position',
            'age',
            'role',
            'password',
            'confirm_password',
        ];

        const hasMissingCommonField = commonRequiredFields.some((fieldName) => {
            const field = adminRegistrationForm.elements.namedItem(fieldName);
            return !field || !field.value.trim();
        });

        if (hasMissingCommonField) {
            event.preventDefault();
            showRegistrationAlert('Please complete all fields before submitting.', 'error');
            return;
        }

        const roleValue = 'encoder';
        const divisionValue = hiddenDivisionField ? hiddenDivisionField.value.trim().toUpperCase() : '';

        if (roleValue === 'encoder') {
            const programValue = programAssignedField ? programAssignedField.value.trim().toUpperCase() : '';

            if (!divisionValue) {
                event.preventDefault();
                showRegistrationAlert('Division is required for encoder accounts.', 'error');
                return;
            }

            if (!programValue) {
                event.preventDefault();
                showRegistrationAlert('Program assigned is required for encoder accounts.', 'error');
                return;
            }

            const validPrograms = divisionProgramsFromServer.length > 0
                ? divisionProgramsFromServer
                : (divisionPrograms[divisionValue] || []);
            if (!validPrograms.includes(programValue)) {
                event.preventDefault();
                showRegistrationAlert('Program assigned is invalid for the selected division.', 'error');
                return;
            }
        }

        const emailField = adminRegistrationForm.elements.namedItem('email');
        const emailValue = emailField ? emailField.value.trim() : '';
        const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue);
        const positionField = adminRegistrationForm.elements.namedItem('position');
        const positionValue = positionField ? positionField.value.trim() : '';
        const genderField = adminRegistrationForm.elements.namedItem('gender');
        const genderValue = genderField ? genderField.value.trim() : '';

        if (!isValidEmail) {
            event.preventDefault();
            showRegistrationAlert('Please provide a valid email address.', 'error');
            return;
        }

        if (!allowedPositions.includes(positionValue)) {
            event.preventDefault();
            showRegistrationAlert('Position must be SWO I, SWO II, SWO III, or SWO IV.', 'error');
            return;
        }

        if (!allowedGenders.includes(genderValue)) {
            event.preventDefault();
            showRegistrationAlert('Gender must be Male or Female.', 'error');
            return;
        }

        const validDivisions = ['DPSD', 'PSSD', 'RPSD'];
        if (divisionValue && !validDivisions.includes(divisionValue)) {
            event.preventDefault();
            showRegistrationAlert('Division must be DPSD, PSSD, or RPSD only.', 'error');
            return;
        }

        const passwordField = adminRegistrationForm.elements.namedItem('password');
        const confirmPasswordField = adminRegistrationForm.elements.namedItem('confirm_password');

        if (passwordField.value !== confirmPasswordField.value) {
            event.preventDefault();
            showRegistrationAlert('Password and confirm password do not match.', 'error');
        }
    });
}

// Admin module restriction: encoder-only and admin division-scoped.
if (window.adminRegistrationState) {
    const state = window.adminRegistrationState;
    const roleField = document.getElementById('role');
    const hiddenDivisionField = document.getElementById('division');
    if (roleField) {
        roleField.value = 'encoder';
        roleField.disabled = true;
    }
    if (hiddenDivisionField) {
        hiddenDivisionField.value = state.admin_division || '';
    }
}
