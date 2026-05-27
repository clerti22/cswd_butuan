(() => {
  const button = document.getElementById('printFormButton');
  const editSerialButton = document.getElementById('editSerialButton');
  const editSignatoriesButton = document.getElementById('editSignatoriesButton');
  const serialNumberValue = document.getElementById('serialNumberValue');
  const brgyCaptainName = document.getElementById('brgyCaptainName');
  const lswdoName = document.getElementById('lswdoName');
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

  editSignatoriesButton?.addEventListener('click', () => {
    const currentRaw = window.localStorage.getItem(signatoriesStorageKey);
    let current = { brgyCaptain: '', lswdo: '' };
    if (currentRaw) {
      try {
        const parsed = JSON.parse(currentRaw);
        current = {
          brgyCaptain: typeof parsed.brgyCaptain === 'string' ? parsed.brgyCaptain : '',
          lswdo: typeof parsed.lswdo === 'string' ? parsed.lswdo : '',
        };
      } catch (error) {
        current = { brgyCaptain: '', lswdo: '' };
      }
    }

    const brgyCaptain = window.prompt('Enter Name/Signature of Brgy. Captain:', current.brgyCaptain);
    if (brgyCaptain === null) return;
    const lswdo = window.prompt('Enter Name/Signature of LSWDO:', current.lswdo);
    if (lswdo === null) return;

    window.localStorage.setItem(signatoriesStorageKey, JSON.stringify({
      brgyCaptain: brgyCaptain.trim(),
      lswdo: lswdo.trim(),
    }));
    renderSignatories();
  });

  renderSerialNumber();
  renderSignatories();
})();
