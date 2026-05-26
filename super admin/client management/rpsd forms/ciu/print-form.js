(() => {
  const printButton = document.getElementById('printFormButton');
  const editClerkButton = document.getElementById('editClerkButton');
  const clerkNameField = document.getElementById('clerkName');
  const STORAGE_KEY = 'ciu_clerk_signatory_name';

  function triggerPrint() {
    window.print();
  }

  function setClerkName(name) {
    if (!clerkNameField) {
      return;
    }
    clerkNameField.textContent = (name || '').trim();
  }

  function getStoredClerkName() {
    try {
      return window.localStorage.getItem(STORAGE_KEY) || '';
    } catch (error) {
      return '';
    }
  }

  function saveClerkName(name) {
    try {
      window.localStorage.setItem(STORAGE_KEY, name);
    } catch (error) {
      // localStorage may fail in strict privacy modes.
    }
  }

  function editClerkName() {
    const currentName = clerkNameField ? clerkNameField.textContent.trim() : '';
    const nextName = window.prompt('Enter clerk name:', currentName);

    if (nextName === null) {
      return;
    }

    const cleanedName = nextName.trim();
    setClerkName(cleanedName);
    saveClerkName(cleanedName);
  }

  if (printButton) {
    printButton.addEventListener('click', triggerPrint);
  }

  if (editClerkButton) {
    editClerkButton.addEventListener('click', editClerkName);
  }

  const savedName = getStoredClerkName();
  if (savedName !== '') {
    setClerkName(savedName);
  }

  document.addEventListener('keydown', (event) => {
    const keyPressed = (event.key || '').toLowerCase();
    const isPrintKey = keyPressed === 'p';
    const hasPrintModifier = event.ctrlKey || event.metaKey;

    if (isPrintKey && hasPrintModifier) {
      event.preventDefault();
      triggerPrint();
    }
  });
})();
