(function () {
  const printButton = document.getElementById('printFormButton');
  const editButton = document.getElementById('editSignatoriesButton');
  const modal = document.getElementById('signatoriesModal');
  const cancelButton = document.getElementById('cancelSignatoriesButton');
  const signatoriesForm = document.getElementById('signatoriesForm');

  const signatoryOneName = document.getElementById('signatoryOneName');
  const signatoryOneTitle = document.getElementById('signatoryOneTitle');
  const signatoryTwoName = document.getElementById('signatoryTwoName');
  const signatoryTwoTitle = document.getElementById('signatoryTwoTitle');

  const signatoryOneNameInput = document.getElementById('signatoryOneNameInput');
  const signatoryOneTitleInput = document.getElementById('signatoryOneTitleInput');
  const signatoryTwoNameInput = document.getElementById('signatoryTwoNameInput');
  const signatoryTwoTitleInput = document.getElementById('signatoryTwoTitleInput');

  const STORAGE_KEY = 'pyap_signatories_v1';

  function triggerPrint() {
    window.print();
  }

  function readCurrentSignatories() {
    return {
      oneName: signatoryOneName ? signatoryOneName.textContent.trim() : '',
      oneTitle: signatoryOneTitle ? signatoryOneTitle.textContent.trim() : '',
      twoName: signatoryTwoName ? signatoryTwoName.textContent.trim() : '',
      twoTitle: signatoryTwoTitle ? signatoryTwoTitle.textContent.trim() : ''
    };
  }

  function renderSignatories(data) {
    if (signatoryOneName) signatoryOneName.textContent = (data.oneName || '').trim();
    if (signatoryOneTitle) signatoryOneTitle.textContent = (data.oneTitle || '').trim();
    if (signatoryTwoName) signatoryTwoName.textContent = (data.twoName || '').trim();
    if (signatoryTwoTitle) signatoryTwoTitle.textContent = (data.twoTitle || '').trim();
  }

  function openModal() {
    if (!modal) return;
    const current = readCurrentSignatories();
    if (signatoryOneNameInput) signatoryOneNameInput.value = current.oneName;
    if (signatoryOneTitleInput) signatoryOneTitleInput.value = current.oneTitle;
    if (signatoryTwoNameInput) signatoryTwoNameInput.value = current.twoName;
    if (signatoryTwoTitleInput) signatoryTwoTitleInput.value = current.twoTitle;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  function saveSignatories(data) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    } catch (error) {
      // noop: local persistence can fail in private browsing settings.
    }
  }

  function loadSavedSignatories() {
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
    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });
  }
  if (signatoriesForm) {
    signatoriesForm.addEventListener('submit', function (event) {
      event.preventDefault();

      const nextValues = {
        oneName: signatoryOneNameInput ? signatoryOneNameInput.value.trim() : '',
        oneTitle: signatoryOneTitleInput ? signatoryOneTitleInput.value.trim() : '',
        twoName: signatoryTwoNameInput ? signatoryTwoNameInput.value.trim() : '',
        twoTitle: signatoryTwoTitleInput ? signatoryTwoTitleInput.value.trim() : ''
      };

      renderSignatories(nextValues);
      saveSignatories(nextValues);
      closeModal();
    });
  }

  const savedValues = loadSavedSignatories();
  if (savedValues) {
    renderSignatories(savedValues);
  }

  document.addEventListener('keydown', function (event) {
    const keyPressed = String(event.key || '').toLowerCase();
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
