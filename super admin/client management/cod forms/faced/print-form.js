(() => {
  const button = document.getElementById('printFormButton');
  const editSerialButton = document.getElementById('editSerialButton');
  const editSignatoriesButton = document.getElementById('editSignatoriesButton');
  const signatoriesModal = document.getElementById('signatoriesModal');
  const signatoriesForm = document.getElementById('signatoriesForm');
  const cancelSignatoriesButton = document.getElementById('cancelSignatoriesButton');
  const serialNumberValue = document.getElementById('serialNumberValue');
  const brgyCaptainName = document.getElementById('brgyCaptainName');
  const lswdoName = document.getElementById('lswdoName');
  const brgyCaptainNameInput = document.getElementById('brgyCaptainNameInput');
  const lswdoNameInput = document.getElementById('lswdoNameInput');
  const controlNumber = new URLSearchParams(window.location.search).get('control_number') || 'default';
  const storageKey = `faced_serial_number_${controlNumber}`;
  const signatoriesStorageKey = `faced_signatories_${controlNumber}`;

  function renderSerialNumber() {
    if (!serialNumberValue) return;
    const saved = window.localStorage.getItem(storageKey) || '';
    serialNumberValue.textContent = saved.trim();
  }

  button?.addEventListener('click', () => window.print());

  editSerialButton?.addEventListener('click', () => {
    const current = window.localStorage.getItem(storageKey) || '';
    const entered = window.prompt('Enter Serial Number:', current);
    if (entered === null) return;
    window.localStorage.setItem(storageKey, entered.trim());
    renderSerialNumber();
  });

  function renderSignatories() {
    const raw = window.localStorage.getItem(signatoriesStorageKey);
    let signatories = { brgyCaptain: '', lswdo: '' };
    if (raw) {
      try {
        const parsed = JSON.parse(raw);
        signatories = {
          brgyCaptain: typeof parsed.brgyCaptain === 'string' ? parsed.brgyCaptain : '',
          lswdo: typeof parsed.lswdo === 'string' ? parsed.lswdo : '',
        };
      } catch (error) {
        signatories = { brgyCaptain: '', lswdo: '' };
      }
    }

    if (brgyCaptainName) brgyCaptainName.textContent = signatories.brgyCaptain.trim();
    if (lswdoName) lswdoName.textContent = signatories.lswdo.trim();
  }

  function readStoredSignatories() {
    const currentRaw = window.localStorage.getItem(signatoriesStorageKey);
    if (!currentRaw) return { brgyCaptain: '', lswdo: '' };
    try {
      const parsed = JSON.parse(currentRaw);
      return {
        brgyCaptain: typeof parsed.brgyCaptain === 'string' ? parsed.brgyCaptain : '',
        lswdo: typeof parsed.lswdo === 'string' ? parsed.lswdo : '',
      };
    } catch (error) {
      return { brgyCaptain: '', lswdo: '' };
    }
  }

  function openSignatoriesModal() {
    if (!signatoriesModal) return;
    const current = readStoredSignatories();
    if (brgyCaptainNameInput) brgyCaptainNameInput.value = current.brgyCaptain;
    if (lswdoNameInput) lswdoNameInput.value = current.lswdo;
    signatoriesModal.classList.add('is-open');
    signatoriesModal.setAttribute('aria-hidden', 'false');
  }

  function closeSignatoriesModal() {
    if (!signatoriesModal) return;
    signatoriesModal.classList.remove('is-open');
    signatoriesModal.setAttribute('aria-hidden', 'true');
  }

  editSignatoriesButton?.addEventListener('click', openSignatoriesModal);
  cancelSignatoriesButton?.addEventListener('click', closeSignatoriesModal);
  signatoriesModal?.addEventListener('click', (event) => {
    if (event.target === signatoriesModal) closeSignatoriesModal();
  });

  signatoriesForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    window.localStorage.setItem(signatoriesStorageKey, JSON.stringify({
      brgyCaptain: brgyCaptainNameInput ? brgyCaptainNameInput.value.trim() : '',
      lswdo: lswdoNameInput ? lswdoNameInput.value.trim() : '',
    }));
    renderSignatories();
    closeSignatoriesModal();
  });

  renderSerialNumber();
  renderSignatories();
})();
