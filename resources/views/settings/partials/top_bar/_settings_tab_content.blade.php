@php
  $topBarModuleKey = $topBarModuleKey ?? $moduleKey ?? ($module ?? '');
  $showModuleTopBarTab = $showModuleTopBarTab ?? \App\Support\ErpModuleRegistry::showTopBarTabInModuleSettings($topBarModuleKey);
  $topBarTabLabel = $topBarTabLabel ?? \App\Support\ErpModuleRegistry::topBarTabLabel($topBarModuleKey, $moduleLabel ?? null);
  $topBarRoutes = $topBarRoutes ?? \App\Support\ModuleTopBarRoutes::resolve($topBarModuleKey);
  $topBarColumnField = $topBarColumnField ?? \App\Support\ModuleTopBarRoutes::columnFieldForModule($topBarModuleKey);
  $topBarColumnLabel = $topBarColumnLabel ?? \App\Support\ModuleTopBarRoutes::columnLabelForModule($topBarModuleKey);
@endphp

@if($showModuleTopBarTab)
<div class="tab-pane fade {{ !empty($activateModuleTopBarTab) ? 'show active' : '' }}" id="tab-module-top-bar" role="tabpanel">
  @if(!empty($showRtaFinesTopBarTabs))
  <div class="d-flex gap-2 mb-3">
    <a href="{{ $rtaFinesTopBarTabUrls['unpaid'] ?? '#' }}"
       class="btn btn-pill {{ ($rtaFinesActiveTopScope ?? 'unpaid') === 'unpaid' ? 'btn-primary' : 'btn-outline-secondary' }}">
      Unpaid Top
    </a>
    <a href="{{ $rtaFinesTopBarTabUrls['paid'] ?? '#' }}"
       class="btn btn-pill {{ ($rtaFinesActiveTopScope ?? 'unpaid') === 'paid' ? 'btn-primary' : 'btn-outline-secondary' }}">
      Paid Top
    </a>
  </div>
  @endif
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <p class="text-muted small mb-0">
      @if(!empty($showRtaFinesTopBarTabs))
        Configure {{ ($rtaFinesActiveTopScope ?? 'unpaid') === 'paid' ? 'Paid' : 'Unpaid' }} Fines top bar categories and options.
      @else
        Create a {{ $topBarTabLabel }} category first, then add multiple options under each category.
      @endif
    </p>
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

