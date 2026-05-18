@php
$chequeTopCategories = $chequeTopCategories ?? collect();
$chequeTopSelectableColumns = $chequeTopSelectableColumns ?? [];
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <p class="text-muted small mb-0">Create a Cheque Top category first, then add multiple options under each category. Use Top Bar and View Cards toggles to control where options appear.</p>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addChequeTopCategoryModal">
    <i class="ti ti-plus me-1"></i> Add Category
  </button>
</div>
<div id="chequeTopAccordionContainer">
  @include('settings.cheques_settings._cheque_top_accordion', ['chequeTopCategories' => $chequeTopCategories])
</div>

@include('settings.cheques_settings._cheque_top_modals')
