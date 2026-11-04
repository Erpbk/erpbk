@extends('layouts.settingsPanelLayout')

@section('title', $profile->label . ' Mappings')

@section('content')
@include('flash::message')
<div class="container-fluid py-3">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title mb-0">{{ $profile->label }} Mappings</h4>
    </div>
    <div class="card-body">
      @include('settings.excel_import_mappings._form_panel', [
        'embedded' => false,
        'moduleKey' => $moduleKey,
        'profile' => $profile,
        'scopes' => $scopes,
        'selectedScopeId' => $selectedScopeId,
        'resolved' => $resolved,
        'configuredScopeIds' => $configuredScopeIds,
        'dynamicItems' => $dynamicItems,
        'excelColumnChoices' => $excelColumnChoices,
        'companySlug' => $companySlug,
      ])
    </div>
  </div>
</div>
@endsection
