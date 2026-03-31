const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');

function openSidebar() {
  sidebar?.classList.add('open');
  overlay?.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  sidebar?.classList.remove('open');
  overlay?.classList.remove('active');
  document.body.style.overflow = '';
}

openBtn?.addEventListener('click', openSidebar);
closeBtn?.addEventListener('click', closeSidebar);
overlay?.addEventListener('click', closeSidebar);

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
const openFamilyModal = document.getElementById('openFamilyModal');
const closeFamilyModal = document.getElementById('closeFamilyModal');
const familyForm = document.getElementById('familyForm');
const familyTableBody = document.getElementById('familyTableBody');
const submitBtn = document.getElementById('submitBtn');
const alertBox = document.getElementById('alertBox');
const modalAlert = document.getElementById('modalAlert');

const familyRows = [];
const controlNumber = new URLSearchParams(window.location.search).get('control_number') || '';
let originalRecordSnapshot = null;
let recordControls = null;

function showAlert(message, inModal = false, variant = 'error') {
  const node = inModal ? modalAlert : alertBox;
  if (!node) return;
  node.textContent = message;
  node.classList.toggle('is-success', variant === 'success');
  node.classList.toggle('is-error', variant !== 'success');
  node.hidden = false;
  window.setTimeout(() => {
    node.hidden = true;
    node.classList.remove('is-success', 'is-error');
  }, 2400);
}

function resetInvalid() {
  document.querySelectorAll('.invalid-input').forEach((el) => el.classList.remove('invalid-input'));
  document.querySelectorAll('.invalid-radio').forEach((el) => el.classList.remove('invalid-radio'));
}

function toggleModal(open) {
  familyModal.classList.toggle('active', open);
  familyModal.setAttribute('aria-hidden', String(!open));
}

openFamilyModal?.addEventListener('click', () => toggleModal(true));
closeFamilyModal?.addEventListener('click', () => toggleModal(false));
familyModal?.addEventListener('click', (e) => { if (e.target === familyModal) toggleModal(false); });

function renderFamilyRows() {
  if (!familyRows.length) {
    familyTableBody.innerHTML = '<tr><td colspan="8"></td></tr>';
    return;
  }
  familyTableBody.innerHTML = familyRows.map((row) => `<tr><td>${row.family_member}</td><td>${row.relation}</td><td>${row.birthdate}</td><td>${row.age}</td><td>${row.sex}</td><td>${row.education}</td><td>${row.occupation}</td><td>${row.remarks}</td></tr>`).join('');
}

familyForm?.addEventListener('submit', (e) => {
  e.preventDefault();
  resetInvalid();
  const formData = new FormData(familyForm);
  const row = Object.fromEntries(formData.entries());
  let valid = true;
  Object.entries(row).forEach(([k, v]) => {
    const field = familyForm.querySelector(`[name="${k}"]`);
    if (!String(v || '').trim()) {
      field?.classList.add('invalid-input');
      valid = false;
    }
  });
  if (!valid) {
    showAlert('Please complete all the required fields.', true);
    return;
  }
  familyRows.push(row);
  renderFamilyRows();
  familyForm.reset();
  toggleModal(false);
});

function checkedValue(name) {
  return document.querySelector(`input[name="${name}"]:checked`)?.value || '';
}

function setRadioValue(name, value) {
  document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
    input.checked = input.value === String(value ?? '');
  });
}

function populateRecord(record = {}, related = {}) {
  const fieldIdByRecordKey = {
    city_municipality: 'city'
  };

  Object.keys(record).forEach((key) => {
    const field = document.getElementById(fieldIdByRecordKey[key] || key);
    if (field) field.value = record[key] ?? '';
  });

  ['sex', 'is_4ps', 'is_ip', 'house_ownership', 'shelter_damage'].forEach((name) => {
    setRadioValue(name, record?.[name] ?? '');
  });

  familyRows.length = 0;
  (related.family_information || []).forEach((member) => familyRows.push({ ...member }));
  renderFamilyRows();
}

function setEditMode(isEditable) {
  window.clientRecordMode?.toggleContainerEditState(document.querySelector('.form-page'), isEditable, [
    '#openSidebar',
    '#closeSidebar',
    '#alertBox',
    '.menu-btn',
    '.client-record-controls__btn',
    '.topbar .menu-btn svg',
    '.topbar .menu-btn line'
  ]);

  if (openFamilyModal) {
    openFamilyModal.disabled = !isEditable;
  }

  if (submitBtn && controlNumber) {
    submitBtn.disabled = !isEditable;
  }

  if (!isEditable) {
    toggleModal(false);
  }
}

async function loadRecordForViewMode() {
  if (!controlNumber) {
    return;
  }

  try {
    const res = await fetch(`../../get_client_record.php?program=faced&control_number=${encodeURIComponent(controlNumber)}`);
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Unable to load FACED record.');
    }

    originalRecordSnapshot = {
      record: { ...(data.record || {}) },
      related: {
        family_information: (data.related?.family_information || []).map((member) => ({ ...member }))
      }
    };

    populateRecord(originalRecordSnapshot.record, originalRecordSnapshot.related);
    setEditMode(false);

    recordControls = window.clientRecordMode?.mountControls({
      anchor: document.querySelector('.form-page'),
      submitButton: submitBtn,
      onEnableEdit: () => setEditMode(true),
      onCancelEdit: () => {
        populateRecord(originalRecordSnapshot?.record || {}, originalRecordSnapshot?.related || {});
        setEditMode(false);
      },
      onSaveStateChange: () => {}
    }) || null;
  } catch (error) {
    showAlert(error.message || 'Unable to load FACED record.');
  }
}

function collectPayload() {
  return {
    control_number: controlNumber,
    region: document.getElementById('region').value.trim(),
    province: document.getElementById('province').value.trim(),
    district: document.getElementById('district').value.trim(),
    city_municipality: document.getElementById('city').value.trim(),
    barangay: document.getElementById('barangay').value.trim(),
    evacuation_site: document.getElementById('evacuation_site').value.trim(),
    first_name: document.getElementById('first_name').value.trim(),
    middle_name: document.getElementById('middle_name').value.trim(),
    last_name: document.getElementById('last_name').value.trim(),
    suffix: document.getElementById('suffix').value.trim(),
    birth_date: document.getElementById('birth_date').value,
    birth_place: document.getElementById('birth_place').value.trim(),
    age: document.getElementById('age').value.trim(),
    sex: checkedValue('sex'),
    civil_status: document.getElementById('civil_status').value.trim(),
    contact_number: document.getElementById('contact_number').value.trim(),
    mother_maiden_name: document.getElementById('mother_maiden_name').value.trim(),
    religion: document.getElementById('religion').value.trim(),
    occupation: document.getElementById('occupation').value.trim(),
    monthly_income: document.getElementById('monthly_income').value.trim(),
    id_card_presented: document.getElementById('id_card_presented').value.trim(),
    id_card_number: document.getElementById('id_card_number').value.trim(),
    is_4ps: checkedValue('is_4ps'),
    is_ip: checkedValue('is_ip'),
    ethnicity_type: document.getElementById('ethnicity_type').value.trim(),
    full_address: document.getElementById('full_address').value.trim(),
    vulnerable_count: document.getElementById('vulnerable_count').value.trim(),
    older_persons: document.getElementById('older_persons').value.trim(),
    pregnant_women: document.getElementById('pregnant_women').value.trim(),
    lactating_women: document.getElementById('lactating_women').value.trim(),
    pwds_medical: document.getElementById('pwds_medical').value.trim(),
    house_ownership: checkedValue('house_ownership'),
    shelter_damage: checkedValue('shelter_damage'),
    family_information: JSON.stringify(familyRows)
  };
}

function validateMain(payload) {
  resetInvalid();
  const optional = new Set(['middle_name', 'suffix', 'control_number']);
  let valid = true;
  Object.entries(payload).forEach(([k, v]) => {
    if (optional.has(k)) return;
    if (k === 'family_information') {
      if (!familyRows.length) valid = false;
      return;
    }
    if (!String(v || '').trim()) {
      const el = document.getElementById(k) || document.querySelector(`[name="${k}"]`)?.closest('fieldset');
      if (el) {
        if (el.tagName === 'FIELDSET') el.classList.add('invalid-radio');
        else el.classList.add('invalid-input');
      }
      valid = false;
    }
  });

  if (!/^\d{11}$/.test(payload.contact_number)) {
    document.getElementById('contact_number')?.classList.add('invalid-input');
    showAlert('Please make it 11 digits.');
    return false;
  }

  if (!valid) {
    showAlert('Please complete all the required fields.');
  }
  return valid;
}

async function loadRecord() {
  await loadRecordForViewMode();
}

submitBtn?.addEventListener('click', async () => {
  const payload = collectPayload();
  if (!validateMain(payload)) return;
  const confirmed = await window.clientFormSubmitConfirmation?.confirm();
  if (!confirmed) return;

  const formData = new FormData();
  Object.entries(payload).forEach(([k, v]) => formData.append(k, String(v)));

  const response = await fetch('faced_submit.php', { method: 'POST', body: formData });
  const result = await response.json();
  if (!response.ok || !result.success) {
    showAlert(result.message || 'Unable to save FACED record.');
    return;
  }

  showAlert(controlNumber ? 'FACED record updated successfully.' : 'FACED record saved successfully.', false, 'success');
  if (controlNumber) {
    originalRecordSnapshot = {
      record: { ...payload },
      related: {
        family_information: familyRows.map((member) => ({ ...member }))
      }
    };
    setEditMode(false);
    recordControls?.setEditing(false);
  } else if (result.control_number) {
    window.location.href = `${window.location.pathname}?control_number=${encodeURIComponent(result.control_number)}`;
  }
});

renderFamilyRows();
loadRecord();
