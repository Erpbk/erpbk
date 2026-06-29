@php
$topBarModuleKey = $topBarModuleKey ?? ($moduleKey ?? \App\Support\ModuleRouteResolver::fromRequest() ?? '');
$topBarConfig = $topBarConfig ?? \App\Support\ErpModuleRegistry::topBarConfig($topBarModuleKey);
$topBarSliderCategories = $topBarSliderCategories ?? collect();
$topBarOptionStats = $topBarOptionStats ?? [];

if ($topBarModuleKey && $topBarSliderCategories->isEmpty()) {
$listingData = app(\App\Services\Module\TopBarListingService::class)->listingViewData($topBarModuleKey, request());
$topBarSliderCategories = $listingData['topBarSliderCategories'] ?? collect();
$topBarOptionStats = $listingData['topBarOptionStats'] ?? [];
$topBarConfig = $listingData['topBarConfig'] ?? $topBarConfig;
}

$topBarStatDefinitions = $topBarStatDefinitions ?? app(\App\Services\Module\TopBarListingService::class)->statDefinitions($topBarModuleKey);
$topBarOptionLabels = $topBarConfig['option_labels'] ?? [];
$hasTopBarCards = $topBarSliderCategories->sum(fn ($c) => $c->options->count()) > 0;
@endphp

@if($topBarConfig && $hasTopBarCards)
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">

<div class="fleet-supervisor-section mb-3" id="erpTopBarSlider_{{ preg_replace('/[^a-z0-9_]/', '_', $topBarModuleKey) }}">
  <div class="fleet-supervisor-accordion expanded">
    <div class="fleet-supervisor-slider-container">
      <div class="slider-controls">
        <button class="slider-btn prev-btn erp-top-bar-prev" type="button" aria-label="Previous">
          <i class="ti ti-chevron-left"></i>
        </button>
        <div class="slider-indicators erp-top-bar-indicators"></div>
        <button class="slider-btn next-btn erp-top-bar-next" type="button" aria-label="Next">
          <i class="ti ti-chevron-right"></i>
        </button>
      </div>
      @php
      $trackId = 'erpTopBarSlider_' . preg_replace('/[^a-z0-9_]/', '_', $topBarModuleKey) . '_track';
      $optionIdParam = (string) ($topBarConfig['request']['option_id'] ?? 'top_option_id');
      $statusParam = (string) ($topBarConfig['request']['status'] ?? '');
      $selectedOptionId = (int) request($optionIdParam, 0);
      $selectedStatuses = $statusParam !== '' ? request($statusParam, []) : [];
      if (!is_array($selectedStatuses)) {
      $selectedStatuses = $selectedStatuses !== '' && $selectedStatuses !== null ? [(string) $selectedStatuses] : [];
      }
      $slideIndex = 0;
      @endphp
      <div class="fleet-supervisor-cards slider-track erp-top-bar-track" id="{{ $trackId }}">
        @foreach($topBarSliderCategories as $category)
        @foreach($category->options as $option)
        @php
        $optionStats = $topBarOptionStats[$option->id] ?? [];
        $isCardActive = $selectedOptionId === (int) $option->id;
        $categoryColumn = trim((string) ($category->db_column ?? $category->rider_column ?? $category->bike_column ?? $category->employee_column ?? $category->cheque_column ?? ''));
        $sourceTable = (string) ($topBarConfig['source_table'] ?? '');
        $optionTitle = $topBarOptionLabels[$option->name] ?? $option->name;
        if ($sourceTable !== '' && $categoryColumn !== '' && \App\Support\TopBarNumericStatus::isNumericStatusColumn($sourceTable, $categoryColumn)) {
        $optionTitle = \App\Support\TopBarNumericStatus::labelForValue($option->name);
        }
        @endphp
        <div
          class="fleet-supervisor-card @if($isCardActive) active filtered @endif"
          data-slide="{{ $slideIndex++ }}"
          data-option-id="{{ $option->id }}"
          role="button">
          <h3 class="fleet-supervisor-name">{{ $optionTitle }}</h3>
          <div class="small text-muted mb-1">{{ $category->name }}</div>
          <div class="fleet-supervisor-stats">
            @foreach($topBarStatDefinitions as $statKey => $statMeta)
            @php
            $statLabel = is_array($statMeta) ? ($statMeta['label'] ?? ucfirst($statKey)) : ucfirst($statKey);
            $statIcon = is_array($statMeta) ? ($statMeta['icon'] ?? 'ti-filter') : 'ti-filter';
            $statValue = (int) ($optionStats[$statKey] ?? 0);
            $isStatSelected = $isCardActive && in_array($statKey, $selectedStatuses, true);
            if ($statKey === 'inactive' && $topBarModuleKey === 'employees') {
            $isStatSelected = $isCardActive && (
            in_array('inactive', $selectedStatuses, true)
            || in_array('on_leave', $selectedStatuses, true)
            );
            }
            $statClass = in_array($statKey, ['inactive', 'pending'], true) ? 'inactive' : 'active';
            @endphp
            <div
              class="fleet-stat {{ $statClass }} @if($isStatSelected) active-selected @endif"
              data-stat-key="{{ $statKey }}">
              <i class="fleet-stat-icon ti {{ $statIcon }}"></i>
              <span class="fleet-stat-label">{{ $statLabel }}</span>
              <span class="fleet-stat-value">{{ $statValue }}</span>
            </div>
            @endforeach
          </div>
        </div>
        @endforeach
        @endforeach
      </div>
    </div>
  </div>
</div>

@include('partials.module_top_bar_slider_script', [
'topBarModuleKey' => $topBarModuleKey,
'topBarConfig' => $topBarConfig,
'trackId' => $trackId,
])
@endif