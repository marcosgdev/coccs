(function () {
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
