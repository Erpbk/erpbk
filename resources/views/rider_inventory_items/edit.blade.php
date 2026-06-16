@php
$itemRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.rider-inventory-items' : 'rider-inventory-items';
@endphp
<script>window.location.replace(@json(route($itemRoute . '.index', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) . '?open_edit=' . $item->id));</script>
