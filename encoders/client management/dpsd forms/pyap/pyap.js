document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const openBtn = document.getElementById('openSidebar');
    const closeBtn = document.getElementById('closeSidebar');

    function openSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

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

    const pyapForm = document.getElementById('pyapForm');
    const formMessage = document.getElementById('formMessage');
    const familyTableBody = document.getElementById('familyTableBody');
    const membershipTableBody = document.getElementById('membershipTableBody');
    const familyInput = document.getElementById('familyCompositionInput');
    const membershipInput = document.getElementById('membershipsInput');
    const saveFamilyBtn = document.getElementById('saveFamilyBtn');
    const saveMembershipBtn = document.getElementById('saveMembershipBtn');
    const familyToggle = document.getElementById('family-modal-toggle');
    const membershipToggle = document.getElementById('membership-modal-toggle');
    const submitButton = pyapForm.querySelector('.submit-btn-main');
    const recordId = window.clientRecordMode?.getRecordId() ?? null;

    if (!pyapForm || !familyTableBody || !membershipTableBody || !familyInput || !membershipInput) {
        return;
    }

    function setMessage(text, type = '') {
        if (!formMessage) return;
        formMessage.textContent = text;
        formMessage.className = type ? `form-message ${type}` : 'form-message';
    }

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
    let originalRecordSnapshot = null;
    let recordControls = null;

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
            if (!field) return;
            field.value = '';
            field.classList.remove('invalid-input');
        });
    }

    function updateJsonInputs() {
        familyInput.value = JSON.stringify(familyCompositions);
        membershipInput.value = JSON.stringify(memberships);
    }

    function setCheckboxGroupValues(name, values) {
        const selectedValues = new Set((values || []).map((value) => String(value).trim().toLowerCase()));
        pyapForm.querySelectorAll(`input[name="${name}"]`).forEach((checkbox) => {
            checkbox.checked = selectedValues.has(String(checkbox.value).trim().toLowerCase());
        });
    }

    function setRadioOrCheckboxValue(name, value) {
        pyapForm.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
            input.checked = String(input.value).trim().toLowerCase() === String(value ?? '').trim().toLowerCase();
        });
    }

    function populatePyapForm(record, related = {}) {
        pyapForm.querySelectorAll('input[name]:not([type="checkbox"]):not([type="radio"]), select[name], textarea[name]').forEach((field) => {
            const value = record?.[field.name] ?? '';
            field.value = value;
        });

        setCheckboxGroupValues('assistance_received[]', String(record?.assistance_received || '').split(','));
        setCheckboxGroupValues('intervention_needed[]', String(record?.intervention_needed || '').split(','));

        familyCompositions.length = 0;
        memberships.length = 0;

        (related.family_composition || []).forEach((row) => familyCompositions.push({ ...row }));
        (related.memberships || []).forEach((row) => memberships.push({ ...row }));

        updateJsonInputs();
        renderFamilyRows();
        renderMembershipRows();
        setMessage('');
    }

    function setEditMode(isEditable) {
        window.clientRecordMode?.toggleContainerEditState(pyapForm, isEditable, [
            '#saveFamilyBtn',
            '#saveMembershipBtn',
            '#family-modal-toggle',
            '#membership-modal-toggle'
        ]);

        if (saveFamilyBtn) {
            saveFamilyBtn.disabled = !isEditable;
        }

        if (saveMembershipBtn) {
            saveMembershipBtn.disabled = !isEditable;
        }
    }

    async function loadRecordForViewMode() {
        if (!recordId) {
            return;
        }

        setMessage('Loading record...');

        try {
            const response = await fetch(`../../get_client_record.php?program=pyap&id=${encodeURIComponent(String(recordId))}`);
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to load PYAP record.');
            }

            originalRecordSnapshot = payload;
            populatePyapForm(payload.record, payload.related || {});
            setEditMode(false);

            recordControls = window.clientRecordMode?.mountControls({
                anchor: document.querySelector('.form-page'),
                submitButton,
                onEnableEdit: () => setEditMode(true),
                onCancelEdit: () => {
                    populatePyapForm(originalRecordSnapshot.record, originalRecordSnapshot.related || {});
                    setEditMode(false);
                },
                onSaveStateChange: () => {}
            }) || null;
        } catch (error) {
            setMessage(error.message, 'error-message');
        }
    }

    function validateFieldGroup(fields, allowAllEmpty = false) {
        const entries = Object.entries(fields).filter(([, field]) => field);
        const values = entries.map(([, field]) => field.value.trim());

        if (allowAllEmpty && values.every((value) => value === '')) {
            clearFields(fields);
            return 'all-empty';
        }

        let valid = true;
        entries.forEach(([, field]) => {
            if (!field.value.trim()) {
                field.classList.add('invalid-input');
                valid = false;
            } else {
                field.classList.remove('invalid-input');
            }
        });

        return valid ? 'valid' : 'invalid';
    }

    if (saveFamilyBtn) {
        saveFamilyBtn.addEventListener('click', (event) => {
            event.preventDefault();
            const result = validateFieldGroup(familyFields);

            if (result !== 'valid') {
                setMessage('All family composition fields are required before saving.', 'error-message');
                alert('Please fill in all Family Composition fields before clicking Save.');
                return;
            }

            familyCompositions.push(Object.fromEntries(
                Object.entries(familyFields).map(([key, field]) => [key, field ? field.value.trim() : ''])
            ));

            updateJsonInputs();
            renderFamilyRows();
            clearFields(familyFields);
            if (familyToggle) familyToggle.checked = false;
            setMessage('');
        });
    }

    if (saveMembershipBtn) {
        saveMembershipBtn.addEventListener('click', (event) => {
            event.preventDefault();
            const result = validateFieldGroup(membershipFields, true);

            if (result === 'all-empty') {
                if (membershipToggle) membershipToggle.checked = false;
                return;
            }

            memberships.push(Object.fromEntries(
                Object.entries(membershipFields).map(([key, field]) => [key, field ? field.value.trim() : ''])
            ));

            updateJsonInputs();
            renderMembershipRows();
            clearFields(membershipFields);
            if (membershipToggle) membershipToggle.checked = false;
            setMessage('');
        });
    }

    function validateCheckboxGroup(name) {
        const checkboxes = pyapForm.querySelectorAll(`input[name="${name}"]`);
        return Array.from(checkboxes).some((checkbox) => checkbox.checked);
    }

    function validateMainForm() {
        const optionalFields = new Set(['middle_name', 'suffix', 'ips', 'assistance_specify', 'intervention_specify', 'family_name', 'family_relationship', 'family_gender', 'family_age', 'family_grade_year', 'family_isy', 'family_osy', 'family_work_labor_experience', 'family_income', 'membership_organization_name', 'membership_position_held', 'membership_year']);
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
            setMessage('Please add at least one complete family composition entry.', 'error-message');
            return false;
        }

        if (!validateCheckboxGroup('assistance_received[]')) {
            setMessage('Please select at least one Assistance & Intervention Received option.', 'error-message');
            return false;
        }

        if (!validateCheckboxGroup('intervention_needed[]')) {
            setMessage('Please select at least one Intervention needed option.', 'error-message');
            return false;
        }

        if (!valid) {
            setMessage('Please complete all required fields before submitting.', 'error-message');
        }

        return valid;
    }

    pyapForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        updateJsonInputs();

        if (!validateMainForm()) {
            return;
        }

        const confirmed = await window.clientFormSubmitConfirmation?.confirm();
        if (!confirmed) {
            return;
        }

        setMessage('Submitting...');

        const formData = new FormData(pyapForm);
        if (recordId) {
            formData.append('record_id', String(recordId));
        }

        try {
            const response = await fetch('pyap_submit.php', {
                method: 'POST',
                body: formData
            });

            const responseType = response.headers.get('content-type') || '';
            const responseBody = await response.text();
            const result = responseType.includes('application/json') && responseBody ? JSON.parse(responseBody) : null;

            if (!response.ok || !result || !result.success) {
                throw new Error((result && result.message) || 'Failed to save PYAP record.');
            }

            setMessage(recordId ? 'PYAP record updated successfully.' : 'PYAP record saved successfully.', 'success-message');

            if (recordId) {
                originalRecordSnapshot = {
                    record: Object.fromEntries(formData.entries()),
                    related: {
                        family_composition: familyCompositions.map((row) => ({ ...row })),
                        memberships: memberships.map((row) => ({ ...row }))
                    }
                };
                setEditMode(false);
                recordControls?.setEditing(false);
            } else {
                pyapForm.reset();
                familyCompositions.length = 0;
                memberships.length = 0;
                updateJsonInputs();
                renderFamilyRows();
                renderMembershipRows();
            }
        } catch (error) {
            setMessage(error.message, 'error-message');
        }
    });

    updateJsonInputs();
    renderFamilyRows();
    renderMembershipRows();
    loadRecordForViewMode();
});
