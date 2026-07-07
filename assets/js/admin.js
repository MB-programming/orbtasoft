document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  var flash = document.querySelector('.admin-flash');
  if (flash) {
    setTimeout(function () {
      flash.style.transition = 'opacity .4s ease';
      flash.style.opacity = '0';
    }, 4000);
  }

  /* ---------- Site content search filter ---------- */
  var contentSearch = document.getElementById('contentSearch');
  if (contentSearch) {
    var rows = document.querySelectorAll('.content-row');
    var groups = document.querySelectorAll('.content-group');
    contentSearch.addEventListener('input', function () {
      var query = contentSearch.value.trim().toLowerCase();
      groups.forEach(function (group) {
        var groupRows = group.querySelectorAll('.content-row');
        var visibleCount = 0;
        groupRows.forEach(function (row) {
          var match = !query || row.getAttribute('data-search').indexOf(query) !== -1;
          row.classList.toggle('is-hidden', !match);
          if (match) visibleCount++;
        });
        group.classList.toggle('is-hidden', visibleCount === 0);
        if (query) group.setAttribute('open', '');
      });
    });
  }
});
