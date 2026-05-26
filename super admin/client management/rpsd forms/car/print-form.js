(() => {
  const printButton = document.getElementById('printFormButton');
  const editButton = document.getElementById('editSignatoriesButton');
  const modal = document.getElementById('signatoriesModal');
  const cancelButton = document.getElementById('cancelSignatoriesButton');
  const form = document.getElementById('signatoriesForm');

  const clerkNameField = document.getElementById('clerkName');
  const socialWorkerNameField = document.getElementById('socialWorkerName');
  const clerkNameInput = document.getElementById('clerkNameInput');
  const socialWorkerNameInput = document.getElementById('socialWorkerNameInput');

  const STORAGE_KEY = 'car_signatories_v1';

  function triggerPrint() {
    window.print();
  }

  function readNames() {
    return {
      clerkName: clerkNameField ? clerkNameField.textContent.trim() : '',
      socialWorkerName: socialWorkerNameField ? socialWorkerNameField.textContent.trim() : ''
    };
  }

  function renderNames(values) {
    if (clerkNameField) clerkNameField.textContent = (values.clerkName || '').trim();
    if (socialWorkerNameField) socialWorkerNameField.textContent = (values.socialWorkerName || '').trim();
  }

  function openModal() {
    if (!modal) return;
    const current = readNames();
    if (clerkNameInput) clerkNameInput.value = current.clerkName;
    if (socialWorkerNameInput) socialWorkerNameInput.value = current.socialWorkerName;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  function saveNames(values) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(values));
    } catch (error) {
      // localStorage may be unavailable in strict privacy modes.
    }
  }

  function loadSavedNames() {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      return typeof parsed === 'object' && parsed !== null ? parsed : null;
    } catch (error) {
      return null;
    }
  }

  if (printButton) {
    printButton.addEventListener('click', triggerPrint);
  }

  if (editButton) {
    editButton.addEventListener('click', openModal);
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
      const nextValues = {
        clerkName: clerkNameInput ? clerkNameInput.value.trim() : '',
        socialWorkerName: socialWorkerNameInput ? socialWorkerNameInput.value.trim() : ''
      };
      renderNames(nextValues);
      saveNames(nextValues);
      closeModal();
    });
  }

  const savedNames = loadSavedNames();
  if (savedNames) {
    renderNames(savedNames);
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
