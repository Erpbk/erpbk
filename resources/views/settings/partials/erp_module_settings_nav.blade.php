@php
$spMenuLabels = $settingsPanelLabels ?? \App\Models\Settings::getMenuLabels();
$spCompanySlug = $settingsCompanySlug ?? request()->route('company_slug') ?? session('company_slug');
@endphp
@can('gn_settings')
@foreach(\App\Support\SettingsPanelMenuRegistry::items() as $menuItem)
@php
if (!\App\Support\SettingsPanelMenuRegistry::isVisible($menuItem) || !\App\Support\SettingsPanelMenuRegistry::hasPermission($menuItem)) {
continue;
}
$parentKey = (string) ($menuItem['key'] ?? '');
$children = \App\Support\SettingsPanelMenuRegistry::visibleChildren($menuItem['children'] ?? []);
$parentSettingsKey = (string) ($menuItem['settings'] ?? $parentKey);
$parentUrl = \App\Support\SettingsPanelMenuRegistry::settingsUrl($parentSettingsKey, $spCompanySlug);
$parentIcon = \App\Support\SettingsPanelMenuRegistry::icon($parentKey, $menuItem['icon'] ?? null);
$parentLabel = \App\Support\SettingsPanelMenuRegistry::label($parentKey, $spMenuLabels);
@endphp
@if(count($children) > 1)
@php
  $branchOpen = \App\Support\SettingsPanelMenuRegistry::branchIsOpen($menuItem);
  $branchActive = \App\Support\SettingsPanelMenuRegistry::branchHasActiveChild($menuItem);
@endphp
<li class="menu-item {{ $branchOpen ? 'open' : '' }} {{ $branchActive ? 'active' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => $parentKey])
    <div>{{ $parentLabel }}</div>
  </a>
  <ul class="menu-sub">
    @foreach($children as $child)
    @php
    $childKey = (string) ($child['key'] ?? '');
    $childSettings = (string) ($child['settings'] ?? $childKey);
    $childQuery = $child['settings_query'] ?? [];
    $childUrl = \App\Support\SettingsPanelMenuRegistry::settingsUrl($childSettings, $spCompanySlug, $childQuery);
    if ($childUrl === null) {
    continue;
    }
    $childIcon = \App\Support\SettingsPanelMenuRegistry::icon($childKey);
    $childLabel = \App\Support\SettingsPanelMenuRegistry::label($childKey, $spMenuLabels, $child['label'] ?? null);
    $childActive = \App\Support\SettingsPanelMenuRegistry::isActive($childSettings, $childQuery);
    @endphp
    <li class="menu-item {{ $childActive ? 'active' : '' }}">
      <a href="{{ $childUrl }}" class="menu-link {{ $childActive ? 'active' : '' }}">
        @include('layouts.partials.module_menu_icon', ['key' => $childKey])
        <div>{{ $childLabel }}</div>
      </a>
    </li>
    @endforeach
  </ul>
</li>
@elseif(count($children) === 1)
@php
$onlyChild = $children[0];
$childSettings = (string) ($onlyChild['settings'] ?? $onlyChild['key'] ?? '');
$childQuery = $onlyChild['settings_query'] ?? [];
$leafUrl = \App\Support\SettingsPanelMenuRegistry::settingsUrl($childSettings, $spCompanySlug, $childQuery);
$leafActive = \App\Support\SettingsPanelMenuRegistry::isActive($childSettings, $childQuery);
@endphp
@if($leafUrl !== null)
<li class="menu-item {{ $leafActive ? 'active' : '' }}">
  <a href="{{ $leafUrl }}" class="menu-link {{ $leafActive ? 'active' : '' }}">
    @include('layouts.partials.module_menu_icon', ['key' => $parentKey])
    <div>{{ $parentLabel }}</div>
  </a>
</li>
@endif
@elseif($parentUrl !== null)
<li class="menu-item {{ \App\Support\SettingsPanelMenuRegistry::isActive($parentSettingsKey) ? 'active' : '' }}">
  <a href="{{ $parentUrl }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => $parentKey])
    <div>{{ $parentLabel }}</div>
  </a>
</li>
@elseif($parentKey === 'assets')
<li class="menu-item">
  <a href="javascript:void(0);" class="menu-link pe-none opacity-50">
    @include('layouts.partials.module_menu_icon', ['key' => $parentKey])
    <div>{{ $parentLabel }}</div>
  </a>
</li>
@endif
@endforeach
@endcan