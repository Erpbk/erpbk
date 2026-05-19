@php
$chequeTopCategories = $chequeTopCategories ?? collect();
$chequeTopSelectableColumns = $chequeTopSelectableColumns ?? [];
$topBarRoutes = \App\Support\ModuleTopBarRoutes::resolve('cheques');
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <p class="text-muted small mb-0">Create a Cheque Top category first, then add multiple options under each category.</p>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRiderTopCategoryModal">
    <i class="ti ti-plus me-1"></i> Add Category
  </button>
</div>
<div id="riderTopAccordionContainer">
  @include('settings.partials.top_bar.accordion', [
    'topBarCategories' => $chequeTopCategories,
    'topBarEmptyMessage' => 'No Cheque Top categories yet. Add your first category to begin.',
  ])
</div>

@include('settings.partials.top_bar.modals', [
  'topBarTabLabel' => 'Cheque Top',
  'topBarColumnField' => 'cheque_column',
  'topBarColumnLabel' => 'Cheque Column',
  'topBarSelectableColumns' => $chequeTopSelectableColumns,
])
@include('settings.partials.top_bar.scripts', ['topBarRoutes' => $topBarRoutes])

