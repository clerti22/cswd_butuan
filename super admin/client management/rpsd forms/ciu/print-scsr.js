(() => {
  const printButton = document.getElementById('printFormButton');
  const editPreparedByButton = document.getElementById('editPreparedByButton');
  const modal = document.getElementById('preparedByModal');
  const cancelButton = document.getElementById('cancelPreparedByButton');
  const form = document.getElementById('preparedByForm');

  const preparedByNameField = document.getElementById('preparedByName');
  const preparedByPositionField = document.getElementById('preparedByPosition');
  const preparedByLicenseField = document.getElementById('preparedByLicense');

  const preparedByNameInput = document.getElementById('preparedByNameInput');
  const preparedByPositionInput = document.getElementById('preparedByPositionInput');
  const preparedByLicenseNumberInput = document.getElementById('preparedByLicenseNumberInput');
  const preparedByValidityInput = document.getElementById('preparedByValidityInput');

  const STORAGE_KEY = 'ciu_scsr_prepared_by_v1';

  function triggerPrint() {
    window.print();
  }

  function formatValidityDate(isoDate) {
    const value = String(isoDate || '').trim();
    if (!value) {
      return '';
    }

    const parts = value.split('-');
    if (parts.length !== 3) {
      return '';
    }

    const [year, month, day] = parts;
    if (!year || !month || !day) {
      return '';
    }

    return `${month}/${day}/${year.slice(-2)}`;
  }

  function renderPreparedBy(values) {
    if (preparedByNameField) {
      preparedByNameField.textContent = (values.name || '').trim();
    }

    if (preparedByPositionField) {
      preparedByPositionField.textContent = (values.position || '').trim();
    }

    const licenseNumber = (values.licenseNumber || '').trim();
    const validity = formatValidityDate(values.validityDate);
    const licenseText = [licenseNumber, validity].filter(Boolean).join(' - ');

    if (preparedByLicenseField) {
      preparedByLicenseField.textContent = licenseText || 'License Number and Validity';
    }
  }

  function readCurrentValues() {
    return {
      name: preparedByNameField ? preparedByNameField.textContent.trim() : '',
      position: preparedByPositionField ? preparedByPositionField.textContent.trim() : '',
      licenseNumber: '',
      validityDate: ''
    };
  }

  function openModal() {
    if (!modal) {
      return;
    }

    const saved = loadPreparedByValues();
    const current = saved || readCurrentValues();

    if (preparedByNameInput) preparedByNameInput.value = current.name || '';
    if (preparedByPositionInput) preparedByPositionInput.value = current.position || '';
    if (preparedByLicenseNumberInput) preparedByLicenseNumberInput.value = current.licenseNumber || '';
    if (preparedByValidityInput) preparedByValidityInput.value = current.validityDate || '';

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (!modal) {
      return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  function savePreparedByValues(values) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(values));
    } catch (error) {
      // localStorage may not be available in strict browser privacy modes.
    }
  }

  function loadPreparedByValues() {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return null;
      }

      const parsed = JSON.parse(raw);
      return typeof parsed === 'object' && parsed !== null ? parsed : null;
    } catch (error) {
      return null;
    }
  }

  if (printButton) {
    printButton.addEventListener('click', triggerPrint);
  }

  if (editPreparedByButton) {
    editPreparedByButton.addEventListener('click', openModal);
  }

  if (cancelButton) {
    cancelButton.addEventListener('click', closeModal);
  }

  if (modal) {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  if (form) {
    form.addEventListener('submit', (event) => {
      event.preventDefault();

      const values = {
        name: preparedByNameInput ? preparedByNameInput.value.trim() : '',
        position: preparedByPositionInput ? preparedByPositionInput.value.trim() : '',
        licenseNumber: preparedByLicenseNumberInput ? preparedByLicenseNumberInput.value.trim() : '',
        validityDate: preparedByValidityInput ? preparedByValidityInput.value : ''
      };

      renderPreparedBy(values);
      savePreparedByValues(values);
      closeModal();
    });
  }

  const savedValues = loadPreparedByValues();
  if (savedValues) {
    renderPreparedBy(savedValues);
  }

  document.addEventListener('keydown', (event) => {
    const keyPressed = (event.key || '').toLowerCase();
    const isPrintKey = keyPressed === 'p';
    const hasPrintModifier = event.ctrlKey || event.metaKey;

    if (isPrintKey && hasPrintModifier) {
      event.preventDefault();
      triggerPrint();
    }

    if (keyPressed === 'escape') {
      closeModal();
    }
  });
})();
