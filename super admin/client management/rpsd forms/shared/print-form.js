(() => {
  const printButton = document.getElementById('printFormButton');

  function triggerPrint() {
    window.print();
  }

  if (printButton) {
    printButton.addEventListener('click', triggerPrint);
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
