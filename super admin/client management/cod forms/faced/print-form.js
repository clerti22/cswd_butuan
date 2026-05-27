(() => {
  const button = document.getElementById('printFormButton');
  const editSerialButton = document.getElementById('editSerialButton');
  const serialNumberValue = document.getElementById('serialNumberValue');
  const controlNumber = new URLSearchParams(window.location.search).get('control_number') || 'default';
  const storageKey = `faced_serial_number_${controlNumber}`;

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

  renderSerialNumber();
})();
