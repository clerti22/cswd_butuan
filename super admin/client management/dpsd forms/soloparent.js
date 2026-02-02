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
})

const pantawidInput = document.getElementById('pantawid-household');
const pantawidRadios = document.querySelectorAll('input[name="pantawid-beneficiary"]');
const indigenousInput = document.getElementById('indigenous-affiliation');
const indigenousRadios = document.querySelectorAll('input[name="indigenous-person"]');

const toggleRadioInput = (radios, input) => {
    if (!input || !radios.length) {
        return;
    }

    const isYesSelected = Array.from(radios).some((radio) => radio.checked && radio.value === 'yes');
    input.disabled = !isYesSelected;
    input.placeholder = isYesSelected ? '' : 'Disabled';

    if (!isYesSelected) {
        input.value = '';
    }
};

const registerRadioToggle = (radios, input) => {
    if (!input || !radios.length) {
        return;
    }

    radios.forEach((radio) => {
        radio.addEventListener('change', () => toggleRadioInput(radios, input));
    });

    toggleRadioInput(radios, input);
};

registerRadioToggle(pantawidRadios, pantawidInput);
registerRadioToggle(indigenousRadios, indigenousInput);

const householdModal = document.getElementById('householdModal');
const openHouseholdModalButton = document.getElementById('openHouseholdModal');
const closeHouseholdModalButton = document.getElementById('closeHouseholdModal');
const submitHouseholdButton = document.getElementById('submitHousehold');
const householdTableBody = document.getElementById('householdTableBody');

const householdInputs = {
    name: document.getElementById('householdName'),
    sex: document.getElementById('householdSex'),
    relationship: document.getElementById('householdRelationship'),
    age: document.getElementById('householdAge'),
    birthdate: document.getElementById('householdBirthdate'),
    civil: document.getElementById('householdCivil'),
    educational: document.getElementById('householdEducational'),
    occupation: document.getElementById('householdOccupation'),
    monthly: document.getElementById('householdMonthly')
};

const toggleHouseholdModal = (isOpen) => {
    if (!householdModal) {
        return;
    }

    householdModal.style.display = isOpen ? 'flex' : 'none';
    householdModal.setAttribute('aria-hidden', String(!isOpen));
};

const resetHouseholdInputs = () => {
    Object.values(householdInputs).forEach((input) => {
        if (!input) {
            return;
        }
        input.value = '';
    });
};

if (openHouseholdModalButton) {
    openHouseholdModalButton.addEventListener('click', () => toggleHouseholdModal(true));
}

if (closeHouseholdModalButton) {
    closeHouseholdModalButton.addEventListener('click', () => toggleHouseholdModal(false));
}

if (householdModal) {
    householdModal.addEventListener('click', (event) => {
        if (event.target === householdModal) {
            toggleHouseholdModal(false);
        }
    });
}

if (submitHouseholdButton) {
    submitHouseholdButton.addEventListener('click', () => {
        if (!householdTableBody) {
            return;
        }

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${householdInputs.name?.value || ''}</td>
            <td>${householdInputs.sex?.value || ''}</td>
            <td>${householdInputs.relationship?.value || ''}</td>
            <td>${householdInputs.age?.value || ''}</td>
            <td>${householdInputs.birthdate?.value || ''}</td>
            <td>${householdInputs.civil?.value || ''}</td>
            <td>${householdInputs.educational?.value || ''}</td>
            <td>${householdInputs.occupation?.value || ''}</td>
            <td>${householdInputs.monthly?.value || ''}</td>
        `;

        const emptyRow = householdTableBody.querySelector('.household-empty-row');
        if (emptyRow) {
            emptyRow.remove();
        }

        householdTableBody.appendChild(row);
        resetHouseholdInputs();
        toggleHouseholdModal(false);
    });
}

const submitFormButton = document.getElementById('submitSoloParent');

const clearInvalidStates = (elements) => {
    elements.forEach((element) => {
        const wrapper = element.closest('.field');
        if (wrapper) {
            wrapper.classList.remove('field--invalid');
        }
    });
};

const markInvalid = (element) => {
    const wrapper = element.closest('.field');
    if (wrapper) {
        wrapper.classList.add('field--invalid');
    }
};

const validateSoloParentForm = () => {
    const formFields = Array.from(document.querySelectorAll('.form-page input, .form-page select, .form-page textarea'))
        .filter((element) => !element.disabled)
        .filter((element) => !element.closest('#householdModal'))
        .filter((element) => element.type !== 'button' && element.type !== 'submit');

    clearInvalidStates(formFields);

    const radioGroups = {};
    const nonRadioFields = [];

    formFields.forEach((field) => {
        if (field.type === 'radio') {
            const name = field.name || '__unnamed__';
            if (!radioGroups[name]) {
                radioGroups[name] = [];
            }
            radioGroups[name].push(field);
            return;
        }
        nonRadioFields.push(field);
    });

    let firstInvalidField = null;
    let isValid = true;

    nonRadioFields.forEach((field) => {
        if (!field.value.trim()) {
            isValid = false;
            markInvalid(field);
            if (!firstInvalidField) {
                firstInvalidField = field;
            }
        }
    });

    Object.values(radioGroups).forEach((group) => {
        const hasSelection = group.some((radio) => radio.checked);
        if (!hasSelection) {
            isValid = false;
            if (!firstInvalidField) {
                firstInvalidField = group[0];
            }
        }
    });

    if (!isValid) {
        alert('Please fill out all required fields before submitting.');
        if (firstInvalidField) {
            firstInvalidField.focus();
        }
    }

    return isValid;
};

if (submitFormButton) {
    submitFormButton.addEventListener('click', () => {
        validateSoloParentForm();
    });
}
