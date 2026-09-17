@php
  $showImportMappingsTab = request()->query('tab') === 'import-mappings';
@endphp
<div class="tab-pane fade {{ $showImportMappingsTab ? 'show active' : '' }}" id="tab-sim-invoice-import-mappings" role="tabpanel">
  @if(empty($excelImportMappingProfile))
    <div class="alert alert-warning mb-0">Import mapping profile is not available.</div>
  @else
    @include('settings.excel_import_mappings._form_panel', [
      'embedded' => true,
      'moduleKey' => $moduleKey ?? 'sim_invoices',
      'profile' => $excelImportMappingProfile,
      'scopes' => $excelImportMappingScopes ?? collect(),
      'selectedScopeId' => $excelImportMappingSelectedScopeId ?? 0,
      'resolved' => $excelImportMappingResolved ?? [],
      'configuredScopeIds' => $excelImportMappingConfiguredScopeIds ?? [],
      'dynamicItems' => $excelImportMappingDynamicItems ?? [],
      'excelColumnChoices' => $excelImportMappingColumnChoices ?? [],
      'companySlug' => request()->route('company_slug') ?? session('company_slug'),
      'formId' => 'simInvoiceExcelImportMappingForm',
    ])
  @endif
</div>
<script>
(function () {
  var btn = document.getElementById('tab-sim-invoice-import-mappings-btn');
  if (!btn) return;
  btn.addEventListener('shown.bs.tab', function () {
    try {
      var url = new URL(window.location.href);
      url.searchParams.set('tab', 'import-mappings');
      window.history.replaceState({}, '', url.toString());
    } catch (e) {}
  });
})();
</script>
