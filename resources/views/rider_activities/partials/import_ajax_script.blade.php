{{--
  Shared AJAX submit handler for rider/live activity import forms.
  @param string $formId
  @param string $errorsRoute
  @param bool $reloadOnSuccess  when true, reload/redirect after success (standalone pages)
--}}
@php
  $formId = $formId ?? 'rider-activities-import-form';
  $errorsRoute = $errorsRoute ?? '';
  $reloadOnSuccess = $reloadOnSuccess ?? true;
@endphp
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function() {
  const form = document.getElementById(@json($formId));
  if (!form || form.dataset.ajaxBound === '1') {
    return;
  }
  form.dataset.ajaxBound = '1';

  const errorsRoute = @json($errorsRoute);
  const reloadOnSuccess = @json((bool) $reloadOnSuccess);

  const escapeHtml = (value) => {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || form.querySelector('input[name="_token"]')?.value
    || '';

  const showError = (message, summary) => {
    const parts = String(message || 'Import failed.').split(' | ').filter(Boolean);
    const summaryErrors = Array.isArray(summary?.errors) ? summary.errors : [];

    if (summaryErrors.length) {
      let html = '<div style="text-align:left"><div class="alert alert-danger" style="max-height:360px;overflow:auto;margin-bottom:0">';
      html += '<ul style="margin:0;padding-left:18px">';
      summaryErrors.slice(0, 40).forEach((err) => {
        html += '<li style="margin-bottom:6px"><strong>Row ' + escapeHtml(err.row ?? 'N/A') + '</strong>: '
          + escapeHtml(err.error_type ?? 'Error') + ' — ' + escapeHtml(err.message ?? '')
          + (err.rider_id ? ' (' + escapeHtml(err.rider_id) + ')' : '')
          + '</li>';
      });
      if (summaryErrors.length > 40) {
        html += '<li>…and ' + (summaryErrors.length - 40) + ' more</li>';
      }
      html += '</ul></div></div>';

      Swal.fire({
        icon: 'error',
        title: 'Import Failed',
        html,
        width: '720px',
        showCancelButton: !!errorsRoute,
        confirmButtonText: errorsRoute ? 'View Error Report' : 'OK',
        cancelButtonText: 'Close',
        confirmButtonColor: '#dc3545'
      }).then((result) => {
        if (result.isConfirmed && errorsRoute) {
          window.open(errorsRoute, '_blank');
        }
      });
      return;
    }

    if (parts.length > 1) {
      Swal.fire({
        icon: 'error',
        title: 'Import Failed',
        html: '<ul style="text-align:left;margin:0;padding-left:20px;max-height:360px;overflow:auto">'
          + parts.map((p) => '<li style="margin-bottom:6px">' + escapeHtml(p) + '</li>').join('')
          + '</ul>',
        width: '700px',
        confirmButtonColor: '#dc3545'
      });
      return;
    }

    Swal.fire({
      icon: 'error',
      title: 'Import Failed',
      text: message || 'Import failed.',
      confirmButtonColor: '#dc3545'
    });
  };

  form.addEventListener('submit', function(event) {
    event.preventDefault();
    event.stopImmediatePropagation();

    const fileInput = form.querySelector('input[type="file"]');
    if (!fileInput || !fileInput.files || !fileInput.files.length) {
      Swal.fire({
        icon: 'warning',
        title: 'File required',
        text: 'Please select a file to upload.'
      });
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
    }

    Swal.fire({
      title: 'Importing…',
      html: 'Please wait while we process your file using this project’s import settings.',
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading()
    });

    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
      },
      credentials: 'same-origin'
    })
      .then(async (response) => {
        let data = {};
        try {
          data = await response.json();
        } catch (e) {
          data = {};
        }

        if (!response.ok || data.success === false) {
          let message = data.message || 'Import failed.';
          if (!data.message && data.errors) {
            message = Object.values(data.errors).flat().filter(Boolean).join(' | ') || message;
          }
          throw { message, summary: data.summary || null };
        }

        return data;
      })
      .then((data) => {
        const text = data.message || 'Import completed successfully.';

        Swal.fire({
          icon: 'success',
          title: 'Import Successful',
          text: text,
          confirmButtonColor: '#28a745'
        }).then(() => {
          if (!reloadOnSuccess) {
            return;
          }
          if (data.redirect) {
            window.location.href = data.redirect;
          } else {
            window.location.reload();
          }
        });
      })
      .catch((error) => {
        showError(error?.message || 'Import failed.', error?.summary || null);
      })
      .finally(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
        }
      });
  }, true);
})();
</script>
