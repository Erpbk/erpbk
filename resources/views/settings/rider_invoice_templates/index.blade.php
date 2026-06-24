@extends('layouts.settingsPanelLayout')

@section('title', 'Rider Invoice Templates')

@section('content')
@include('flash::message')
@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">Rider Invoice Templates</h4>
          <p class="text-muted small mb-0 mt-1">Create and manage invoice layouts used when generating rider invoices.</p>
        </div>
        @canany(['gn_settings', 'riderinvoice_edit'])
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
          <i class="ti ti-plus me-1"></i> Add Template
        </button>
        @endcanany
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Template Name</th>
                <th>Layout Style</th>
                <th>Description</th>
                <th>Default</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($templates as $template)
              <tr>
                <td>{{ $template->template_name }}</td>
                <td><span class="badge bg-label-primary">{{ $layouts[$template->layout_key] ?? $template->layout_key }}</span></td>
                <td class="text-muted small">{{ $template->description ?: '—' }}</td>
                <td>
                  @if($template->is_default)
                  <span class="badge bg-label-success">Default</span>
                  @else
                  @canany(['gn_settings', 'riderinvoice_edit'])
                  <form method="POST" action="{{ route('settings-panel.rider-invoice-templates.set-default', ['company_slug' => $companySlug, 'id' => $template->id]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-xs">Set default</button>
                  </form>
                  @endcanany
                  @endif
                </td>
                <td>
                  @if($template->status)
                  <span class="badge bg-label-success">Active</span>
                  @else
                  <span class="badge bg-label-secondary">Disabled</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    @canany(['gn_settings', 'riderinvoice_edit'])
                    <button type="button" class="btn btn-outline-primary btn-edit-template"
                      data-id="{{ $template->id }}"
                      data-name="{{ $template->template_name }}"
                      data-layout="{{ $template->layout_key }}"
                      data-description="{{ $template->description }}"
                      data-default="{{ $template->is_default ? '1' : '0' }}"
                      data-status="{{ $template->status ? '1' : '0' }}">
                      Edit
                    </button>
                    <form method="POST" action="{{ route('settings-panel.rider-invoice-templates.toggle-status', ['company_slug' => $companySlug, 'id' => $template->id]) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-outline-secondary">{{ $template->status ? 'Disable' : 'Enable' }}</button>
                    </form>
                    @if(! $template->is_default)
                    <form method="POST" action="{{ route('settings-panel.rider-invoice-templates.destroy', ['company_slug' => $companySlug, 'id' => $template->id]) }}" class="d-inline" onsubmit="return confirm('Delete this template?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger">Delete</button>
                    </form>
                    @endif
                    @endcanany
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No templates yet. Add your first rider invoice template.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@canany(['gn_settings', 'riderinvoice_edit'])
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('settings-panel.rider-invoice-templates.store', ['company_slug' => $companySlug]) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Rider Invoice Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @include('settings.rider_invoice_templates._form_fields', ['template' => null, 'layouts' => $layouts])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Template</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editTemplateForm">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Rider Invoice Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="editTemplateBody">
          @include('settings.rider_invoice_templates._form_fields', ['template' => null, 'prefix' => 'edit_', 'layouts' => $layouts])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Template</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcanany
@endsection

@push('third_party_scripts')
<script>
document.querySelectorAll('.btn-edit-template').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.dataset.id;
    var form = document.getElementById('editTemplateForm');
    form.action = @json(url('app/'.$companySlug.'/settings-panel/rider-invoice-templates')) + '/' + id;
    form.querySelector('[name="template_name"]').value = this.dataset.name || '';
    form.querySelector('[name="layout_key"]').value = this.dataset.layout || 'modern';
    form.querySelector('[name="description"]').value = this.dataset.description || '';
    form.querySelector('[name="is_default"]').checked = this.dataset.default === '1';
    form.querySelector('[name="status"]').checked = this.dataset.status === '1';
    new bootstrap.Modal(document.getElementById('editTemplateModal')).show();
  });
});
</script>
@endpush
