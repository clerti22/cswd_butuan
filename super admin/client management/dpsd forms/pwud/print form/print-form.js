(function () {
  const printButton = document.getElementById('printFormButton');
  const editSocialWorkerButton = document.getElementById('editSocialWorkerButton');
  const socialWorkerNameField = document.getElementById('socialWorkerName');
  const STORAGE_KEY = 'pwud_social_worker_name';

  function triggerPrint() {
    window.print();
  }

  function setSocialWorkerName(name) {
    if (!socialWorkerNameField) {
      return;
    }
    socialWorkerNameField.textContent = (name || '').trim();
  }

  function getStoredSocialWorkerName() {
    try {
      return window.localStorage.getItem(STORAGE_KEY) || '';
    } catch (error) {
      return '';
    }
  }

  function saveSocialWorkerName(name) {
    try {
      window.localStorage.setItem(STORAGE_KEY, name);
    } catch (error) {
      // localStorage can fail in strict browser privacy modes.
    }
  }

  function editSocialWorkerName() {
    const currentName = socialWorkerNameField ? socialWorkerNameField.textContent.trim() : '';
    const nextName = window.prompt('Enter social worker name:', currentName);
    if (nextName === null) {
      return;
    }

    const cleanedName = nextName.trim();
    setSocialWorkerName(cleanedName);
    saveSocialWorkerName(cleanedName);
  }

  if (printButton) {
    printButton.addEventListener('click', triggerPrint);
  }

  if (editSocialWorkerButton) {
    editSocialWorkerButton.addEventListener('click', editSocialWorkerName);
  }

  setSocialWorkerName(getStoredSocialWorkerName());

  document.addEventListener('keydown', function (event) {
    const keyPressed = String(event.key || '').toLowerCase();
    const isPrintKey = keyPressed === 'p';
    const hasPrintModifier = event.ctrlKey || event.metaKey;

    if (isPrintKey && hasPrintModifier) {
      event.preventDefault();
      triggerPrint();
    }
  });
})();
