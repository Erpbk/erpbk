@php
  $panelId = $panelId ?? ($category->id ?? 0);
  $companySlug = request()->route('company_slug');
  $templateId = $template->id;
  $styleLabel = \App\Models\AgreementTemplate::TYPES[$template->template_type] ?? $template->template_type;
@endphp

<div class="contract-document-panel"
  id="agreement-generate-panel-{{ $panelId }}"
  data-agreement-bound="0"
  data-company-slug="{{ $companySlug }}"
  data-rider-id="{{ $rider->id }}"
  data-template-id="{{ $templateId }}">

  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <div class="text-muted small">Assigned template</div>
      <div class="fw-semibold">{{ $template->template_name }}</div>
      <span class="badge bg-label-primary">{{ $styleLabel }}</span>
    </div>
    @canany(['agreement_edit', 'agreement_manage_templates', 'gn_settings'])
    <a href="{{ route('documents.agreements.manage-category', ['company_slug' => $companySlug, 'category' => $category->id, 'template' => $template->id]) }}#template-editor-panel"
      class="btn btn-outline-primary btn-sm" target="_blank" title="Edit template content in Documents → Agreements">
      <i class="ti ti-edit me-1"></i> Edit template
    </a>
    @endcanany
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label small text-muted mb-1">Contract date</label>
      <input type="date"
        id="agreement_date_input_{{ $panelId }}"
        class="form-control form-control-sm agreement-date-input"
        value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-6">
      <label class="form-label small text-muted mb-1">Rider</label>
      <div class="form-control form-control-sm bg-light">{{ $rider->name }} <span class="text-muted">({{ $rider->rider_id }})</span></div>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3">
    @canany(['agreement_generate', 'agreement_view', 'rider_view', 'gn_settings'])
    <button type="button" class="btn btn-outline-info btn-sm btn-agreement-preview" data-panel="{{ $panelId }}">
      <i class="ti ti-eye me-1"></i> Preview
    </button>
    <button type="button" class="btn btn-primary btn-sm btn-agreement-pdf" data-panel="{{ $panelId }}">
      <i class="ti ti-download me-1"></i> Download PDF
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm btn-agreement-print" data-panel="{{ $panelId }}">
      <i class="ti ti-printer me-1"></i> Print
    </button>
    @endcanany
  </div>

  <div class="border-top pt-3">
    <h6 class="small text-uppercase text-muted mb-2">Email contract</h6>
    <form method="POST"
      class="agreement-email-form"
      data-panel="{{ $panelId }}"
      action="{{ route('agreements.email', ['company_slug' => $companySlug, 'riderId' => $rider->id]) }}">
      @csrf
      <input type="hidden" name="template_id" value="{{ $templateId }}">
      <input type="hidden" name="agreement_date" class="agreement-email-date" value="{{ date('Y-m-d') }}">
      <div class="row g-2">
        <div class="col-md-6">
          <input type="email" name="email_to" class="form-control form-control-sm" placeholder="Recipient email"
            value="{{ $rider->email }}">
        </div>
        <div class="col-md-6">
          <input type="text" name="email_subject" class="form-control form-control-sm"
            value="{{ $category->name }} — {{ $rider->name }}">
        </div>
        <div class="col-12">
          <textarea name="email_message" class="form-control form-control-sm" rows="2"
            placeholder="Optional message">Please find attached your contract document.</textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success btn-sm">
            <i class="ti ti-mail me-1"></i> Send email with PDF
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
