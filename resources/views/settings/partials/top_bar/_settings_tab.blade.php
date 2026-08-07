@php
  $topBarModuleKey = $topBarModuleKey ?? $moduleKey ?? ($module ?? '');
  $showModuleTopBarTab = $showModuleTopBarTab ?? \App\Support\ErpModuleRegistry::showTopBarTabInModuleSettings($topBarModuleKey);
  $topBarTabLabel = $topBarTabLabel ?? \App\Support\ErpModuleRegistry::topBarTabLabel($topBarModuleKey, $moduleLabel ?? null);
@endphp

@if($showModuleTopBarTab)
<li class="nav-item" role="presentation">
  <button class="nav-link {{ !empty($activateModuleTopBarTab) ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-module-top-bar" type="button" role="tab" id="tab-module-top-bar-btn">
    {{ $topBarTabLabel }}
  </button>
</li>
@endif

