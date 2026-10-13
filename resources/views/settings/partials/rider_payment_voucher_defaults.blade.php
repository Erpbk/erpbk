{{-- Rider Payment Voucher document defaults (title, notes, signatures). --}}
@php
  $pvDefaults = \App\Support\RiderPaymentVoucherDefaults::all();
@endphp
<hr class="my-4">
<div class="mb-2">
  <h5 class="mb-1">Payment Voucher</h5>
  <p class="text-muted small mb-3">
    Labels and visibility for the rider payment voucher document (view / print / PDF).
  </p>
</div>
<form
  method="POST"
  id="rider-payment-voucher-defaults-form"
  class="js-rider-payment-voucher-defaults-form"
  action="{{ route('settings-panel.module-settings.update-rider-payment-voucher-defaults', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => $defaultsModule ?? ($moduleKey ?? 'rider_invoices')]) }}"
>
  @csrf
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label" for="rider_payment_voucher_title">Document Title</label>
      <input
        type="text"
        name="title"
        id="rider_payment_voucher_title"
        class="form-control"
        maxlength="100"
        placeholder="PAYMENT VOUCHER"
        value="{{ old('title', $pvDefaults['title']) }}"
      >
    </div>
  </div>

  <hr class="my-3">

  <div class="row g-3 align-items-center mb-2">
    <div class="col-md-6">
      <div class="form-check">
        <input
          type="checkbox"
          class="form-check-input"
          name="show_internal_notes"
          id="rider_payment_show_internal_notes"
          value="1"
          @checked(old('show_internal_notes', $pvDefaults['show_internal_notes']))
        >
        <label class="form-check-label" for="rider_payment_show_internal_notes">Internal Notes</label>
      </div>
    </div>
    <div class="col-md-6">
      <input
        type="text"
        name="internal_notes_label"
        id="rider_payment_internal_notes_label"
        class="form-control"
        maxlength="100"
        placeholder="Internal Notes"
        value="{{ old('internal_notes_label', $pvDefaults['internal_notes_label']) }}"
      >
    </div>
  </div>

  <div class="row g-3 align-items-start mb-3">
    <div class="col-md-6">
      <div class="form-check mt-2">
        <input
          type="checkbox"
          class="form-check-input"
          name="show_signature"
          id="rider_payment_show_signature"
          value="1"
          @checked(old('show_signature', $pvDefaults['show_signature']))
        >
        <label class="form-check-label" for="rider_payment_show_signature">Signature</label>
      </div>
      <div class="form-text">When enabled, shows rider and payer signature lines on the voucher.</div>
    </div>
    <div class="col-md-6">
      <div class="mb-2">
        <label class="form-label small mb-1" for="rider_payment_rider_signature_label">Rider signature label</label>
        <input
          type="text"
          name="rider_signature_label"
          id="rider_payment_rider_signature_label"
          class="form-control"
          maxlength="100"
          placeholder="Received By"
          value="{{ old('rider_signature_label', $pvDefaults['rider_signature_label']) }}"
        >
      </div>
      <div>
        <label class="form-label small mb-1" for="rider_payment_payer_signature_label">Payer signature label</label>
        <input
          type="text"
          name="payer_signature_label"
          id="rider_payment_payer_signature_label"
          class="form-control"
          maxlength="100"
          placeholder="Paid By"
          value="{{ old('payer_signature_label', $pvDefaults['payer_signature_label']) }}"
        >
      </div>
    </div>
  </div>

  <div class="text-start">
    <button type="submit" class="btn btn-warning" id="rider-payment-voucher-defaults-save-btn">Save</button>
  </div>
</form>
<script>
(function () {
  var form = document.getElementById('rider-payment-voucher-defaults-form');
  if (!form || form.dataset.bound === '1') return;
  form.dataset.bound = '1';

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = document.getElementById('rider-payment-voucher-defaults-save-btn');
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
          var msg = (result.data && result.data.message) ? result.data.message : 'Could not save payment voucher settings.';
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
          } else if (typeof toastr !== 'undefined') {
            toastr.error(msg);
          } else {
            alert(msg);
          }
          return;
        }
        var okMsg = (result.data && result.data.message) ? result.data.message : 'Payment voucher settings saved.';
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'success', title: 'Saved', text: okMsg, timer: 1600, showConfirmButton: false });
        } else if (typeof toastr !== 'undefined') {
          toastr.success(okMsg);
        }
      })
      .catch(function () {
        if (typeof toastr !== 'undefined') {
          toastr.error('Could not save payment voucher settings.');
        } else {
          alert('Could not save payment voucher settings.');
        }
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Save';
        }
      });
  });
})();
</script>
