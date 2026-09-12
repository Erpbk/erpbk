@extends('layouts.app')
@section('title', 'Import SIM Invoice')
@push('third_party_stylesheets')
<style>
    #filePreviewTable td, #filePreviewTable th { white-space: nowrap; font-size: 12px; padding: 4px 8px; vertical-align: middle; }
    #filePreviewTable thead th { background-color: #f8f9fa; position: sticky; top: 0; z-index: 5; }
    #filePreviewTable .col-head.mapped { background-color: #eaf3ff; }
    #filePreviewTable .col-head.dup-col { background-color: #fdeaea; }
    .map-badges { min-height: 18px; margin-top: 2px; }
    .map-badges .badge { font-size: 10px; font-weight: 500; margin: 0 2px 2px 0; display: inline-block; }
    .map-field select.map-select { font-size: 13px; }
    .map-field.is-unmapped select.map-select { border-color: #dc3545; }
    #mappingStatus.complete { color: #28a745; font-weight: 600; }
    #mappingStatus.incomplete { color: #d39e00; font-weight: 600; }
    .item-map-row { border: 1px solid #e9ecef; border-radius: 4px; padding: 10px; margin-bottom: 10px; background: #fafbfc; }
    .item-map-row.is-invalid-row { border-color: #dc3545; }
    #previewEmpty { min-height: 280px; display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 1.05rem; font-weight: 500; }
    .position-relative > .select2-container { width: 100% !important; }
</style>
@endpush
@section('content')
<section class="content-header">
    @include('flash::message')
    <div class="row mb-2"><div class="col-sm-12">@include('sims.partials.actions_dropdown')</div></div>
</section>
@include('sims.partials.nav_tabs')
<div class="content px-3">
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Import SIM Invoice</h5></div>
        <div class="card-body">
            <form id="simInvoiceImportForm" action="{{ route('simInvoices.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Company <span class="text-danger">*</span></label>
                        <select name="company_id" id="sim_invoice_import_company_id" class="form-select" required>
                            @foreach($companies as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Billing Month <span class="text-danger">*</span></label>
                        <input type="month" name="billing_month" class="form-control" value="{{ date('Y-m') }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="inv_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Reference <span class="text-danger">*</span></label>
                        <input type="text" name="reference_number" class="form-control" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>VAT % <span class="text-danger">*</span></label>
                        <input type="number" name="vat_percent" id="vat_percent" class="form-control" step="any" min="0" value="{{ $defaultVat }}" required>
                        <small class="text-muted">Applied to all imported item lines.</small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Import File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.csv,.xls" required>
                        <small class="text-muted">One row = one SIM. Item columns are quantity.</small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Description</label>
                        <textarea name="descriptions" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-5 form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mb-3"><a href="javascript:void(0);" id="toggleMappingHelp" class="small"><i class="fas fa-question-circle"></i> How does column mapping work?</a></div>
                <div id="mappingHelpBox" class="alert alert-info mb-4" style="display:none;">
                    <ul class="mb-0 pl-3">
                        <li>Company, billing month, invoice date, reference, VAT %, description, notes, and attachment come from this form.</li>
                        <li>Map <strong>SIM Number</strong>, then add item columns from the SIM catalog. Sheet cells are <strong>quantity</strong>.</li>
                        <li>The form VAT % is applied to every imported line when calculating tax and totals.</li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-lg-7 mb-3">
                        <div id="fileReadError" class="alert alert-danger py-2 mb-3" style="display:none;"><i class="fas fa-exclamation-triangle"></i> Could not read the selected file.</div>
                        <div id="previewCard" class="card border mb-0">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>File Preview <small class="text-muted" id="previewFileName"></small></strong>
                                <span id="mappingStatus"></span>
                            </div>
                            <div class="card-body p-2">
                                <div id="previewEmpty">Select a File to Preview</div>
                                <div id="previewTableWrap" class="table-responsive" style="max-height: 480px; display:none;">
                                    <table class="table table-sm table-bordered mb-0" id="filePreviewTable"></table>
                                </div>
                                <div id="mappingWarning" class="text-danger small mt-2" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 mb-3">
                        <h5 class="mb-3">Required Columns</h5>
                        <div class="form-group map-field" data-field="sim_number" data-required="1">
                            <label for="col_sim_number">SIM Number</label>
                            <select name="col_sim_number" id="col_sim_number" class="form-control map-select" required disabled>
                                <option value="">Select a file first…</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                            <h5 class="mb-0">Item Columns</h5>
                            <button type="button" class="btn btn-sm btn-success" id="addItemMapRow" disabled><i class="fas fa-plus"></i> Add Item Column</button>
                        </div>
                        <div id="itemMapRows"></div>
                        <small class="text-muted d-block mb-2">Sheet value = quantity. Rate applies to every imported row for that item. VAT % comes from the form field above.</small>
                    </div>
                </div>
                <div class="form-group text-center mt-3">
                    <button type="submit" class="btn btn-success" id="importBtn" disabled><i class="fas fa-upload"></i> Import SIM Invoice</button>
                    <a href="{{ route('simInvoices.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
                <div id="importProgressWrap" class="mt-3" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong id="importProgressLabel">Uploading file...</strong>
                        <span id="importProgressPct">0%</span>
                    </div>
                    <div class="progress" style="height: 22px;">
                        <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <small class="text-muted" id="importProgressHint">Please keep this page open until import finishes.</small>
                </div>
                <div id="importResult" class="mt-3" style="display:none;"></div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script src="{{ asset('assets/vendor/libs/xlsx/xlsx.full.min.js') }}"></script>
<script>
window.SIM_INVOICE_IMPORT = {
    items: @json($items ?? []),
    defaultVat: @json((float) ($defaultVat ?? 0)),
    indexUrl: @json(route('simInvoices.index'))
};
</script>
<script src="{{ asset('js/sim_invoice_import.js') }}"></script>
@endsection
