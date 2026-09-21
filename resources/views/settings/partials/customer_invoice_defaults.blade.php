{{-- Company defaults for Customer Invoice notes / terms (shown on create + printed invoice). --}}
@php
  $invoiceDefaults = \App\Support\CustomerInvoiceDefaults::all();
@endphp
<hr class="my-4">
<div class="mb-2">
  <h5 class="mb-1">Invoice defaults</h5>
  <p class="text-muted small mb-3">
    Default text for new customer invoices. You can still edit them per customer or per invoice.
  </p>
</div>
<form method="POST" action="{{ route('settings-panel.module-settings.update-invoice-defaults', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => 'customer_invoices']) }}">
  @csrf
  <div class="mb-3">
    <label class="form-label" for="customer_invoice_terms_and_conditions">Terms &amp; Conditions</label>
    <textarea
      name="terms_and_conditions"
      id="customer_invoice_terms_and_conditions"
      class="form-control"
      rows="4"
      placeholder="Enter default terms &amp; conditions..."
    >{{ old('terms_and_conditions', $invoiceDefaults['terms_and_conditions']) }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" for="customer_invoice_customer_notes">Customer Notes</label>
    <textarea
      name="customer_notes"
      id="customer_invoice_customer_notes"
      class="form-control"
      rows="4"
      placeholder="Thanks for your business."
    >{{ old('customer_notes', $invoiceDefaults['customer_notes']) }}</textarea>
    <div class="form-text">Will be displayed on the invoice</div>
  </div>
  <div class="text-end">
    <button type="submit" class="btn btn-primary">Save invoice defaults</button>
  </div>
</form>
