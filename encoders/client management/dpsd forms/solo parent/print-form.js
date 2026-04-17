(function () {
  const printButton = document.getElementById('printFormButton');

  function triggerPrint() {
    window.print();
  }

  if (printButton) {
    printButton.addEventListener('click', triggerPrint);
  }
})();
