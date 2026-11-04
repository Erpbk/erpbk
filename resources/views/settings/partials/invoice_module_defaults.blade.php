{{-- Company defaults for any invoice module title / notes / terms. --}}
@php
  $defaultsModule = $defaultsModule ?? ($moduleKey ?? 'customer_invoices');
  $invoiceDefaults = \App\Support\InvoiceModuleDefaults::all($defaultsModule);
  $cfg = \App\Support\InvoiceModuleDefaults::config($defaultsModule);
  $notesFieldName = ($cfg['notes_alias'] ?? 'notes') === 'customer_notes' ? 'customer_notes' : 'invoice_notes';
  $notesValue = $invoiceDefaults['customer_notes'] ?? ($invoiceDefaults['notes'] ?? '');
  $notesLabel = ($notesFieldName === 'customer_notes') ? 'Customer Notes' : 'Invoice Notes';
@endphp
<hr class="my-4">
<div class="mb-2">
  <h5 class="mb-1">Invoice defaults</h5>
  <p class="text-muted small mb-3">
    Default text for invoices. Title appears on the printed invoice header stamp.
  </p>
</div>
<form
  method="POST"
  id="invoice-module-defaults-form"
  class="js-invoice-defaults-form"
  action="{{ route('settings-panel.module-settings.update-invoice-defaults', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => $defaultsModule]) }}"
>
  @csrf
  <div class="mb-3">
    <label class="form-label" for="module_invoice_title">Invoice Title</label>
    <input
      type="text"
      name="invoice_title"
      id="module_invoice_title"
      class="form-control"
      maxlength="100"
      placeholder="{{ $cfg['default_title'] }}"
      value="{{ old('invoice_title', $invoiceDefaults['title']) }}"
    >
    <div class="form-text">Shown as the heading on the invoice (e.g. {{ $cfg['default_title'] }}).</div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="module_invoice_terms">Terms &amp; Conditions</label>
    <textarea
      name="terms_and_conditions"
      id="module_invoice_terms"
      class="form-control"
      rows="4"
      placeholder="Enter default terms &amp; conditions..."
    >{{ old('terms_and_conditions', $invoiceDefaults['terms_and_conditions']) }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" for="module_invoice_notes">{{ $notesLabel }}</label>
    <textarea
      name="{{ $notesFieldName }}"
      id="module_invoice_notes"
      class="form-control"
      rows="4"
      placeholder="Thanks for your business."
    >{{ old($notesFieldName, $notesValue) }}</textarea>
    <div class="form-text">Shown beside totals on the invoice</div>
  </div>
  <div class="text-end">
    <button type="submit" class="btn btn-primary" id="invoice-defaults-save-btn">Save invoice defaults</button>
  </div>
</form>
<script>
(function () {
  var form = document.getElementById('invoice-module-defaults-form');
  if (!form || form.dataset.bound === '1') return;
  form.dataset.bound = '1';

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = document.getElementById('invoice-defaults-save-btn');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Saving...';
    }

    var csrf = form.querySelector('input[name="_token"]');
    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf.value } : {})
      },
      credentials: 'same-origin'
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        }).catch(function () {
          return { ok: res.ok, data: null };
        });
      })
      .then(function (result) {
        if (!result.ok || (result.data && result.data.success === false)) {
          var msg = (result.data && result.data.message) ? result.data.message : 'Could not save invoice defaults.';
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
          } else {
            alert(msg);
          }
          if (btn) {
            btn.disabled = false;
            btn.textContent = 'Save invoice defaults';
          }
          return;
        }
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            text: (result.data && result.data.message) ? result.data.message : 'Invoice defaults saved.',
            timer: 900,
            showConfirmButton: false
          }).then(function () {
            window.location.reload();
          });
        } else {
          window.location.reload();
        }
      })
      .catch(function () {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save invoice defaults.' });
        } else {
          alert('Could not save invoice defaults.');
        }
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Save invoice defaults';
        }
      });
  });
})();
</script>
