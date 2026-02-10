const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');

function openSidebar() {
    if (!sidebar || !overlay) {
        return;
    }

    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    if (!sidebar || !overlay) {
        return;
    }

    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

if (openBtn) {
    openBtn.addEventListener('click', openSidebar);
}

if (closeBtn) {
    closeBtn.addEventListener('click', closeSidebar);
}

if (overlay) {
    overlay.addEventListener('click', closeSidebar);
}

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

const pantawidInput = document.getElementById('pantawid-household');
const pantawidRadios = document.querySelectorAll('input[name="pantawid_beneficiary"], input[name="pantawid-beneficiary"]');
const indigenousInput = document.getElementById('indigenous-affiliation');
const indigenousRadios = document.querySelectorAll('input[name="indigenous_person"], input[name="indigenous-person"]');

const toggleRadioInput = (radios, input) => {
    if (!input || !radios.length) {
        return;
    }

    const isYesSelected = Array.from(radios).some((radio) => radio.checked && String(radio.value).toLowerCase() === 'yes');
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

document.addEventListener('change', (event) => {
    const target = event.target;

    if (!(target instanceof HTMLInputElement) || target.type !== 'radio') {
        return;
    }

    if (target.name === 'pantawid_beneficiary' || target.name === 'pantawid-beneficiary') {
        toggleRadioInput(
            document.querySelectorAll('input[name="pantawid_beneficiary"], input[name="pantawid-beneficiary"]'),
            pantawidInput
        );
    }

    if (target.name === 'indigenous_person' || target.name === 'indigenous-person') {
        toggleRadioInput(
            document.querySelectorAll('input[name="indigenous_person"], input[name="indigenous-person"]'),
            indigenousInput
        );
    }
});

const householdModal = document.getElementById('householdModal');
const openHouseholdModalButton = document.getElementById('openHouseholdModal');
const closeHouseholdModalButton = document.getElementById('closeHouseholdModal');
const submitHouseholdButton = document.getElementById('submitHousehold');
const householdTableBody = document.getElementById('householdTableBody');
const soloParentForm = document.getElementById('soloParentForm');
const householdPayloadInput = document.getElementById('household-members-payload');
const submitFormButton = document.getElementById('submitSoloParent');

const householdMembers = [];

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

const syncHouseholdPayload = () => {
    if (!householdPayloadInput) {
        return;
    }

    householdPayloadInput.value = JSON.stringify(householdMembers);
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

        const householdEntry = {
            household_name: householdInputs.name?.value.trim() || '',
            household_sex: householdInputs.sex?.value.trim() || '',
            household_relationship: householdInputs.relationship?.value.trim() || '',
            household_age: householdInputs.age?.value.trim() || '',
            household_birthdate: householdInputs.birthdate?.value.trim() || '',
            household_civil_status: householdInputs.civil?.value.trim() || '',
            household_educational: householdInputs.educational?.value.trim() || '',
            household_occupation: householdInputs.occupation?.value.trim() || '',
            household_monthly_income: householdInputs.monthly?.value.trim() || ''
        };

        const missingHouseholdField = Object.values(householdEntry).some((value) => !value);

        if (missingHouseholdField) {
            alert('Please fill out all household member fields before adding.');
            return;
        }

        householdMembers.push(householdEntry);
        syncHouseholdPayload();

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${householdEntry.household_name}</td>
            <td>${householdEntry.household_sex}</td>
            <td>${householdEntry.household_relationship}</td>
            <td>${householdEntry.household_age}</td>
            <td>${householdEntry.household_birthdate}</td>
            <td>${householdEntry.household_civil_status}</td>
            <td>${householdEntry.household_educational}</td>
            <td>${householdEntry.household_occupation}</td>
            <td>${householdEntry.household_monthly_income}</td>
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
    if (!soloParentForm) {
        return false;
    }

    const optionalFieldNames = ['middle_name', 'suffix', 'email_address'];
    const formFields = Array.from(soloParentForm.querySelectorAll('input, select, textarea'))
        .filter((element) => !element.disabled)
        .filter((element) => element.type !== 'button' && element.type !== 'submit' && element.type !== 'hidden')
        .filter((element) => !optionalFieldNames.includes(element.name || ''));

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

    if (householdMembers.length === 0) {
        isValid = false;
        if (!firstInvalidField && openHouseholdModalButton) {
            firstInvalidField = openHouseholdModalButton;
        }
    }

    if (!isValid) {
        alert('Please fill out all required fields, including at least one household member, before submitting.');
        if (firstInvalidField) {
            firstInvalidField.focus();
        }
    }

    return isValid;
};

if (soloParentForm) {
    soloParentForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!validateSoloParentForm()) {
            return;
        }

        syncHouseholdPayload();

        const formData = new FormData(soloParentForm);

        try {
            submitFormButton.disabled = true;

            const response = await fetch(soloParentForm.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to submit solo parent form.');
            }

            alert(result.message || 'Solo parent information submitted successfully.');
            soloParentForm.reset();
            householdMembers.length = 0;
            syncHouseholdPayload();
            if (householdTableBody) {
                householdTableBody.innerHTML = '<tr class="household-empty-row"><td colspan="9">No household members added yet.</td></tr>';
            }
            toggleRadioInput(pantawidRadios, pantawidInput);
            toggleRadioInput(indigenousRadios, indigenousInput);
        } catch (error) {
            alert(error.message || 'An error occurred while submitting the form.');
        } finally {
            submitFormButton.disabled = false;
        }
    });
}
