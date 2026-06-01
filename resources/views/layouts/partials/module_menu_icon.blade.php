@php
$moduleMenuIconKey = $key ?? $module ?? '';
@endphp
@if($moduleMenuIconKey !== '')
<span class="menu-icon-slot d-inline-flex align-items-center" data-menu-icon-key="{{ $moduleMenuIconKey }}">{!! \App\Support\ModuleMenuIcon::render($moduleMenuIconKey) !!}</span>
@endif
