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

const toggleRadioInput = (radios, input) => {
    if (!input || !radios.length) {
        return;
    }

    const isYesSelected = Array.from(radios).some((radio) => radio.checked && String(radio.value).toLowerCase() === 'yes');
    input.disabled = !isYesSelected;

    if (isYesSelected) {
        input.removeAttribute('disabled');
    } else {
        input.setAttribute('disabled', 'disabled');
    }

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
        radio.addEventListener('click', () => toggleRadioInput(radios, input));
    });

    toggleRadioInput(radios, input);
};

const pantawidInput = document.getElementById('pantawid-household');
const pantawidRadios = document.querySelectorAll('input[name="pantawid_beneficiary"], input[name="pantawid-beneficiary"]');
const indigenousInput = document.getElementById('indigenous-affiliation');
const indigenousRadios = document.querySelectorAll('input[name="indigenous_person"], input[name="indigenous-person"]');

const refreshEligibilityToggleStates = () => {
    toggleRadioInput(pantawidRadios, pantawidInput);
    toggleRadioInput(indigenousRadios, indigenousInput);
};

const initializeEligibilityToggles = () => {
    registerRadioToggle(pantawidRadios, pantawidInput);
    registerRadioToggle(indigenousRadios, indigenousInput);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEligibilityToggles);
} else {
    initializeEligibilityToggles();
}

const householdModal = document.getElementById('householdModal');
const openHouseholdModalButton = document.getElementById('openHouseholdModal');
const closeHouseholdModalButton = document.getElementById('closeHouseholdModal');
const submitHouseholdButton = document.getElementById('submitHousehold');
const householdTableBody = document.getElementById('householdTableBody');
const soloParentForm = document.getElementById('soloParentForm');
const householdPayloadInput = document.getElementById('household-members-payload');
const submitFormButton = document.getElementById('submitSoloParent');
const recordId = window.clientRecordMode?.getRecordId() ?? null;

const householdMembers = [];
let originalRecordSnapshot = null;
let recordControls = null;

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

const renderHouseholdTable = () => {
    if (!householdTableBody) {
        return;
    }

    if (householdMembers.length === 0) {
        householdTableBody.innerHTML = '<tr class="household-empty-row"><td colspan="9">No household members added yet.</td></tr>';
        return;
    }

    householdTableBody.innerHTML = householdMembers.map((entry) => `
        <tr>
            <td>${entry.household_name}</td>
            <td>${entry.household_sex}</td>
            <td>${entry.household_relationship}</td>
            <td>${entry.household_age}</td>
            <td>${entry.household_birthdate}</td>
            <td>${entry.household_civil_status}</td>
            <td>${entry.household_educational}</td>
            <td>${entry.household_occupation}</td>
            <td>${entry.household_monthly_income}</td>
        </tr>
    `).join('');
};

const assignFieldValue = (name, value) => {
    const fields = soloParentForm?.querySelectorAll(`[name="${name}"]`) || [];
    if (!fields.length) {
        return;
    }

    if (fields[0].type === 'radio') {
        fields.forEach((field) => {
            field.checked = String(field.value).toLowerCase() === String(value ?? '').toLowerCase();
        });
        return;
    }

    fields.forEach((field) => {
        field.value = value ?? '';
    });
};

const populateSoloParentForm = (record, related = {}) => {
    Object.entries(record || {}).forEach(([name, value]) => {
        assignFieldValue(name, value);
    });

    householdMembers.length = 0;
    (related.household_members || []).forEach((member) => {
        householdMembers.push({ ...member });
    });

    syncHouseholdPayload();
    renderHouseholdTable();
    refreshEligibilityToggleStates();
};

const setEditMode = (isEditable) => {
    window.clientRecordMode?.toggleContainerEditState(soloParentForm, isEditable, ['#openHouseholdModal', '#submitHousehold', '#closeHouseholdModal']);
    if (openHouseholdModalButton) {
        openHouseholdModalButton.disabled = !isEditable;
    }
    if (submitHouseholdButton) {
        submitHouseholdButton.disabled = !isEditable;
    }
    refreshEligibilityToggleStates();
};

const loadRecordForViewMode = async () => {
    if (!recordId) {
        renderHouseholdTable();
        return;
    }

    try {
        const response = await fetch(`../../get_client_record.php?program=solo-parent&id=${encodeURIComponent(String(recordId))}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load Solo Parent record.');
        }

        originalRecordSnapshot = payload;
        populateSoloParentForm(payload.record, payload.related || {});
        setEditMode(false);

        recordControls = window.clientRecordMode?.mountControls({
            anchor: document.querySelector('.form-page'),
            submitButton: submitFormButton,
            onEnableEdit: () => setEditMode(true),
            onCancelEdit: () => {
                populateSoloParentForm(originalRecordSnapshot.record, originalRecordSnapshot.related || {});
                setEditMode(false);
            },
            onSaveStateChange: () => {}
        }) || null;
    } catch (error) {
        alert(error.message);
    }
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

        renderHouseholdTable();
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

        const confirmed = await window.clientFormSubmitConfirmation?.confirm();
        if (!confirmed) {
            return;
        }

        syncHouseholdPayload();

        const formData = new FormData(soloParentForm);
        if (recordId) {
            formData.append('record_id', String(recordId));
        }

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

            alert(result.message || (recordId ? 'Solo parent information updated successfully.' : 'Solo parent information submitted successfully.'));

            if (recordId) {
                originalRecordSnapshot = {
                    record: Object.fromEntries(formData.entries()),
                    related: { household_members: householdMembers.map((member) => ({ ...member })) }
                };
                setEditMode(false);
                recordControls?.setEditing(false);
            } else {
                soloParentForm.reset();
                householdMembers.length = 0;
                syncHouseholdPayload();
                renderHouseholdTable();
                refreshEligibilityToggleStates();
            }
        } catch (error) {
            alert(error.message || 'An error occurred while submitting the form.');
        } finally {
            submitFormButton.disabled = false;
        }
    });
}

loadRecordForViewMode();
