@isset($rider)
<div class="agreement-generate-panel" id="agreement-generate-panel"
  data-company-slug="{{ request()->route('company_slug') }}"
  data-rider-id="{{ $rider->id }}">
  <h5 class="mb-3">Generate {{ $category->name }}</h5>
  <form id="agreement-generate-form" method="GET" target="_blank">
    <div class="mb-3">
      <label class="form-label">Template</label>
      <select name="template_id_select" id="agreement_template_select" class="form-select">
        @forelse($templates as $t)
        <option value="{{ $t->id }}" data-edit-url="{{ route('agreements.templates.edit', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id, 'template' => $t->id]) }}" @if($defaultTemplate && $defaultTemplate->id === $t->id) selected @endif>
          {{ $t->template_name }}{{ $t->is_default ? ' (Default)' : '' }}
        </option>
        @empty
        <option value="">No templates — create one in Settings → Agreements</option>
        @endforelse
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Agreement date</label>
      <input type="date" name="agreement_date" id="agreement_date_input" class="form-control" value="{{ date('Y-m-d') }}">
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
      <button type="button" class="btn btn-outline-info btn-sm" id="btn-agreement-preview">Preview</button>
      <button type="button" class="btn btn-primary btn-sm" id="btn-agreement-pdf">Download PDF</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-agreement-print">Print</button>
      @canany(['agreement_edit', 'gn_settings'])
      <a id="agreement-edit-content-link"
        href="{{ $defaultTemplate ? route('agreements.templates.edit', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id, 'template' => $defaultTemplate->id]) : '#' }}"
        class="btn btn-outline-primary btn-sm"
        target="_blank">
        Edit Content
      </a>
      @endcanany
    </div>
  </form>

  <hr>
  <h6 class="mb-2">Email agreement</h6>
  <form method="POST" id="agreement-email-form" action="{{ route('agreements.email', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id]) }}">
    @csrf
    <input type="hidden" name="template_id" class="agreement-email-template-id" value="{{ $defaultTemplate ? $defaultTemplate->id : '' }}">
    <input type="hidden" name="agreement_date" class="agreement-email-date" value="{{ date('Y-m-d') }}">
    <div class="mb-2">
      <input type="email" name="email_to" class="form-control form-control-sm" placeholder="Email to"
        value="{{ $rider->email }}">
    </div>
    <div class="mb-2">
      <input type="text" name="email_subject" class="form-control form-control-sm"
        value="{{ $category->name }} — {{ $rider->name }}">
    </div>
    <div class="mb-2">
      <textarea name="email_message" class="form-control form-control-sm" rows="2"
        placeholder="Optional message"></textarea>
    </div>
    <button type="submit" class="btn btn-success btn-sm">Send email with PDF</button>
  </form>

  <hr class="my-3">
  <p class="text-muted small mb-2">Legacy contract view (static):</p>
  <a href="{{ route('rider.contract', ['company_slug' => request()->route('company_slug'), 'id' => $rider->id]) }}"
    class="btn btn-warning btn-sm" target="_blank">
    <i class="fas fa-file"></i> View / Print Legacy Contract
  </a>
</div>
@endisset