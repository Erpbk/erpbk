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
<li class="menu-item {{ \App\Support\SettingsPanelMenuRegistry::branchIsOpen($menuItem) ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti {{ $parentIcon }}"></i>
    <div>{{ $parentLabel }}</div>
  </a>
  <ul class="menu-sub">
    @foreach($children as $child)
    @php
    $childKey = (string) ($child['key'] ?? '');
    $childSettings = (string) ($child['settings'] ?? $childKey);
    $childUrl = \App\Support\SettingsPanelMenuRegistry::settingsUrl($childSettings, $spCompanySlug, $child['settings_query'] ?? []);
    if ($childUrl === null) {
    continue;
    }
    $childIcon = \App\Support\SettingsPanelMenuRegistry::icon($childKey);
    $childLabel = \App\Support\SettingsPanelMenuRegistry::label($childKey, $spMenuLabels, $child['label'] ?? null);
    @endphp
    <li class="menu-item {{ \App\Support\SettingsPanelMenuRegistry::isActive($childSettings) ? 'active' : '' }}">
      <a href="{{ $childUrl }}" class="menu-link">
        <i class="menu-icon tf-icons ti {{ $childIcon }}"></i>
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
$leafUrl = \App\Support\SettingsPanelMenuRegistry::settingsUrl($childSettings, $spCompanySlug, $onlyChild['settings_query'] ?? []);
@endphp
@if($leafUrl !== null)
<li class="menu-item {{ \App\Support\SettingsPanelMenuRegistry::isActive($childSettings) ? 'active' : '' }}">
  <a href="{{ $leafUrl }}" class="menu-link">
    <i class="menu-icon tf-icons ti {{ \App\Support\SettingsPanelMenuRegistry::icon($parentKey, $menuItem['icon'] ?? null) }}"></i>
    <div>{{ $parentLabel }}</div>
  </a>
</li>
@endif
@elseif($parentUrl !== null)
<li class="menu-item {{ \App\Support\SettingsPanelMenuRegistry::isActive($parentSettingsKey) ? 'active' : '' }}">
  <a href="{{ $parentUrl }}" class="menu-link">
    <i class="menu-icon tf-icons ti {{ $parentIcon }}"></i>
    <div>{{ $parentLabel }}</div>
  </a>
</li>
@elseif($parentKey === 'assets')
<li class="menu-item">
  <a href="javascript:void(0);" class="menu-link pe-none opacity-50">
    <i class="menu-icon tf-icons ti {{ $parentIcon }}"></i>
    <div>{{ $parentLabel }}</div>
  </a>
</li>
@endif
@endforeach
@endcan