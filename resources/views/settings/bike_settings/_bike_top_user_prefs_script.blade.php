<script>
(function() {
  var csrf = '{{ csrf_token() }}';
  document.addEventListener('DOMContentLoaded', function() {
    var bikeTopUserPrefsForm = document.getElementById('bikeTopUserPrefsForm');
    if (bikeTopUserPrefsForm) {
      bikeTopUserPrefsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var boxes = bikeTopUserPrefsForm.querySelectorAll('.bike-top-user-pref-option');
        var total = boxes.length;
        var checked = Array.prototype.filter.call(boxes, function(b) { return b.checked; });
        var fd = new FormData();
        fd.append('_token', csrf);
        if (checked.length !== total) {
          checked.forEach(function(b) { fd.append('visible_option_ids[]', b.value); });
        }
        fetch("{{ route('settings-panel.bike-settings.save-bike-top-user-preferences') }}", {
          method: 'POST', body: fd,
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json(); }).then(function(data) {
          if (data.success && typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: 'Saved', text: data.message || '', timer: 1400, showConfirmButton: false });
          }
        });
      });
    }
    var bikeTopUserPrefsResetBtn = document.getElementById('bikeTopUserPrefsResetBtn');
    if (bikeTopUserPrefsResetBtn) {
      bikeTopUserPrefsResetBtn.addEventListener('click', function() {
        var fd = new FormData();
        fd.append('_token', csrf);
        fetch("{{ route('settings-panel.bike-settings.save-bike-top-user-preferences') }}", {
          method: 'POST', body: fd,
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json(); }).then(function(data) {
          if (data.success) window.location.reload();
        });
      });
    }
  });
})();
</script>
