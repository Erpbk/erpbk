@php
  $topBarModuleKey = $topBarModuleKey ?? $moduleKey ?? ($module ?? '');
  $showModuleTopBarTab = $showModuleTopBarTab ?? \App\Support\ErpModuleRegistry::showTopBarTabInModuleSettings($topBarModuleKey);
  $topBarTabLabel = $topBarTabLabel ?? \App\Support\ErpModuleRegistry::topBarTabLabel($topBarModuleKey, $moduleLabel ?? null);
  $topBarRoutes = $topBarRoutes ?? \App\Support\ModuleTopBarRoutes::resolve($topBarModuleKey);
  $topBarColumnField = $topBarColumnField ?? \App\Support\ModuleTopBarRoutes::columnFieldForModule($topBarModuleKey);
  $topBarColumnLabel = $topBarColumnLabel ?? \App\Support\ModuleTopBarRoutes::columnLabelForModule($topBarModuleKey);
@endphp

@if($showModuleTopBarTab)
<div class="tab-pane fade" id="tab-module-top-bar" role="tabpanel">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <p class="text-muted small mb-0">Create a {{ $topBarTabLabel }} category first, then add multiple options under each category.</p>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRiderTopCategoryModal">
      <i class="ti ti-plus me-1"></i> Add Category
    </button>
  </div>
  <div id="riderTopAccordionContainer">
    @include('settings.partials.top_bar.accordion', [
      'topBarCategories' => $topBarCategories ?? collect(),
      'topBarEmptyMessage' => 'No ' . $topBarTabLabel . ' categories yet. Add your first category to begin.',
    ])
  </div>
</div>

@include('settings.partials.top_bar.modals', [
  'topBarTabLabel' => $topBarTabLabel,
  'topBarColumnField' => $topBarColumnField,
  'topBarColumnLabel' => $topBarColumnLabel,
  'topBarSelectableColumns' => $topBarSelectableColumns ?? [],
])

@include('settings.partials.top_bar.scripts', ['topBarRoutes' => $topBarRoutes])
@endif

