@extends('layouts.app')
@section('title', __('Company modules') . ': ' . $company->name)

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Companies') }}</a>
                <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-outline-secondary btn-sm">{{ __('Company details') }}</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ __('ERP modules for') }} {{ $company->name }}</h5>
            <p class="text-muted small mb-0">{{ __('Uncheck a module to hide it from this company’s sidebar. Custom titles override the default menu name for that module.') }}</p>
        </div>
        <div class="card-body">
            @php
                $moduleTree = [
                    ['key' => 'dashboard'],
                    ['key' => 'cash_banks', 'children' => ['cash_banks', 'cheques']],
                    ['key' => 'employees'],
                    ['key' => 'attendance', 'children' => ['attendance_records', 'attendance_summary']],
                    ['key' => 'items', 'children' => ['items_list', 'garage_items']],
                    ['key' => 'leads'],
                    ['key' => 'customers'],
                    ['key' => 'vendors'],
                    ['key' => 'recruiters'],
                    ['key' => 'riders', 'children' => ['riders_list', 'invoices', 'activities', 'live_activities', 'rider_report']],
                    ['key' => 'bikes', 'children' => ['bike_list', 'maintenance_overview']],
                    ['key' => 'sims'],
                    ['key' => 'fuel_cards'],
                    ['key' => 'rta_fines'],
                    ['key' => 'rta_saliks'],
                    ['key' => 'inventory'],
                    ['key' => 'visa_expense'],
                    ['key' => 'expenses'],
                    ['key' => 'vat', 'children' => ['vat_ledger', 'vat_return_file']],
                    ['key' => 'leasing_companies', 'children' => ['leasing_companies_list', 'leasing_invoices']],
                    ['key' => 'garages'],
                    ['key' => 'supplier', 'children' => ['suppliers', 'supplier_invoices']],
                    ['key' => 'assets'],
                    ['key' => 'documents'],
                    ['key' => 'vouchers'],
                    ['key' => 'accounts', 'children' => ['chart_of_accounts', 'ledger']],
                ];
                $defaultLabels = config('menu_labels.defaults', []);
            @endphp
            <form action="{{ route('admin.companies.modules.update', $company) }}" method="post">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width:3rem">{{ __('On') }}</th>
                                <th>{{ __('Module') }}</th>
                                <th>{{ __('Custom menu title') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleTree as $node)
                                @php
                                    $moduleKey = $node['key'];
                                    $meta = $moduleDefinitions[$moduleKey] ?? ['label' => ($defaultLabels[$moduleKey] ?? $moduleKey)];
                                    $isOn = !in_array($moduleKey, $disabled, true);
                                    $currentLabel = $labelOverrides[$moduleKey] ?? '';
                                    $children = $node['children'] ?? [];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="enabled[]" value="{{ $moduleKey }}" id="mod_{{ $moduleKey }}" @checked($isOn)>
                                        </div>
                                    </td>
                                    <td>
                                        <label class="mb-0 fw-medium" for="mod_{{ $moduleKey }}">{{ __($meta['label'] ?? $moduleKey) }}</label>
                                        <div class="text-muted small"><code>{{ $moduleKey }}</code> <span class="ms-1">({{ __('parent') }})</span></div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="labels[{{ $moduleKey }}]" value="{{ old('labels.'.$moduleKey, $currentLabel) }}" placeholder="{{ $meta['label'] ?? $moduleKey }}">
                                    </td>
                                </tr>
                                @foreach($children as $childKey)
                                    @php
                                        $childDefaultLabel = $defaultLabels[$childKey] ?? ucwords(str_replace('_', ' ', $childKey));
                                        $childLabel = $labelOverrides[$childKey] ?? '';
                                        $isChildOn = !in_array($childKey, $disabled, true);
                                        $inheritsParent = $childKey === $moduleKey;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="form-check mb-0">
                                                @if($inheritsParent)
                                                    <input class="form-check-input" type="checkbox" disabled @checked($isOn)>
                                                @else
                                                    <input class="form-check-input" type="checkbox" name="enabled[]" value="{{ $childKey }}" id="mod_{{ $moduleKey }}_{{ $childKey }}" @checked($isChildOn)>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="ps-4">
                                            @if($inheritsParent)
                                                <div class="mb-0">↳ {{ __($childDefaultLabel) }}</div>
                                            @else
                                                <label class="mb-0" for="mod_{{ $moduleKey }}_{{ $childKey }}">↳ {{ __($childDefaultLabel) }}</label>
                                            @endif
                                            <div class="text-muted small"><code>{{ $childKey }}</code> <span class="ms-1">({{ __('submenu') }})</span></div>
                                        </td>
                                        <td>
                                            @if($inheritsParent)
                                                <input type="text" class="form-control form-control-sm" value="{{ old('labels.'.$moduleKey, $currentLabel) }}" placeholder="{{ $childDefaultLabel }}" disabled>
                                                <div class="text-muted small mt-1">{{ __('Uses parent module settings.') }}</div>
                                            @else
                                                <input type="text" class="form-control form-control-sm" name="labels[{{ $childKey }}]" value="{{ old('labels.'.$childKey, $childLabel) }}" placeholder="{{ $childDefaultLabel }}">
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
