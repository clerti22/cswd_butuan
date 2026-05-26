(() => {
  const printButton = document.getElementById('printFormButton');
  const editButton = document.getElementById('editSignatoriesButton');
  const modal = document.getElementById('signatoriesModal');
  const cancelButton = document.getElementById('cancelSignatoriesButton');
  const form = document.getElementById('signatoriesForm');

  const preparedByNameField = document.getElementById('preparedByName');
  const notedByNameField = document.getElementById('notedByName');
  const preparedByNameInput = document.getElementById('preparedByNameInput');
  const notedByNameInput = document.getElementById('notedByNameInput');

  const STORAGE_KEY = 'csae_signatories_v1';

  function triggerPrint() {
    window.print();
  }

  function readNames() {
    return {
      preparedByName: preparedByNameField ? preparedByNameField.textContent.trim() : '',
      notedByName: notedByNameField ? notedByNameField.textContent.trim() : ''
    };
  }

  function renderNames(values) {
    if (preparedByNameField) preparedByNameField.textContent = (values.preparedByName || '').trim();
    if (notedByNameField) notedByNameField.textContent = (values.notedByName || '').trim();
  }

  function openModal() {
    if (!modal) return;
    const current = loadSavedNames() || readNames();
    if (preparedByNameInput) preparedByNameInput.value = current.preparedByName || '';
    if (notedByNameInput) notedByNameInput.value = current.notedByName || '';
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
      // localStorage may be unavailable in strict browser privacy modes.
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

  if (printButton) printButton.addEventListener('click', triggerPrint);
  if (editButton) editButton.addEventListener('click', openModal);
  if (cancelButton) cancelButton.addEventListener('click', closeModal);

  if (modal) {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) closeModal();
    });
  }

  if (form) {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const values = {
        preparedByName: preparedByNameInput ? preparedByNameInput.value.trim() : '',
        notedByName: notedByNameInput ? notedByNameInput.value.trim() : ''
      };
      renderNames(values);
      saveNames(values);
      closeModal();
    });
  }

  const saved = loadSavedNames();
  if (saved) renderNames(saved);

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
