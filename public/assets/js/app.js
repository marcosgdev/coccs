(function () {
  const root = document.documentElement;
  const storedTheme = localStorage.getItem('gc-theme') || 'default';

  function applyPrefs() {
    root.dataset.theme = storedTheme === 'pastel' ? 'pastel' : 'default';
  }

  applyPrefs();

  document.addEventListener('click', function (event) {
    const action = event.target.closest('[data-theme-toggle]');
    if (!action) return;

    if (action.dataset.themeToggle !== undefined) {
      const next = root.dataset.theme === 'pastel' ? 'default' : 'pastel';
      localStorage.setItem('gc-theme', next);
      root.dataset.theme = next;
    }
  });

  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
      new bootstrap.Tooltip(element);
    });
  }

  if (window.jQuery && $.fn.DataTable) {
    $('.datatable').DataTable({
      responsive: true,
      pageLength: 25,
      dom: 'Bfrtip',
      buttons: ['copy', 'csv', 'excel', 'print', 'colvis'],
      language: {
        url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json'
      }
    });
  }
})();
