@extends('bikes.view')

@section('page_content')

@php
    $entityType = trim((string) request('type', ''));
    $entityPermissionMap = [
        'bike' => ['view' => 'bikes_documents_view', 'create' => 'bikes_documents_create'],
        'rider' => ['view' => 'riders_documents_view', 'create' => 'riders_documents_create'],
        'employee' => ['view' => 'employees_document_view', 'create' => 'employees_document_create'],
        'customer' => ['view' => 'customers_documents_view', 'create' => 'customers_documents_create'],
        'supplier' => ['view' => 'suppliers_documents_view', 'create' => 'suppliers_documents_create'],
        '3' => ['view' => 'suppliers_documents_view', 'create' => 'suppliers_documents_create'],
        'leasing_company' => ['view' => 'leasing_companies_documents_view', 'create' => 'leasing_companies_documents_create'],
        'rentCompany' => ['view' => 'bike_on_rent_documents_view', 'create' => 'bike_on_rent_documents_create'],
        'garage' => ['view' => 'garages_documents_view', 'create' => 'garages_documents_create'],
        'bank' => ['view' => 'cash_&_banks_banks_view', 'create' => 'cash_&_banks_banks_create'],
    ];
    $entityPerms = $entityPermissionMap[$entityType] ?? null;
    $viewPermissions = array_values(array_filter([
        $entityPerms['view'] ?? null,
        'documents_view',
        // rentCompany files are also reachable from garages
        $entityType === 'rentCompany' ? 'garages_documents_view' : null,
        $entityType === 'garage' ? 'bike_on_rent_documents_view' : null,
    ]));
    $createPermissions = array_values(array_filter([
        $entityPerms['create'] ?? null,
        'documents_create',
        $entityType === 'rentCompany' ? 'garages_documents_create' : null,
        $entityType === 'garage' ? 'bike_on_rent_documents_create' : null,
    ]));
    $authorized = auth()->user()?->canany($viewPermissions) ?? false;
@endphp
@if($authorized)
@include('flash::message')
<div class="card">
    @canany($createPermissions)
    <div class="card-header">
        <a class="btn btn-primary show-modal action-btn"
          href="javascript:void(0);" data-action="{{ route('files.create',['type_id'=>request('type_id')??1,'type'=>request('type')??1]) }}" data-size="sm" data-title="Upload File">
           Upload File
        </a>
    </div>
    @endcanany
    @include('files.table')
</div>
@else
<div class="alert alert-warning  text-center m-3"><i class="fa fa-warning"></i> You don't have permission. &nbsp;<a href="{{url()->previous() }}"> Go Back</a></div>
@endif
@endsection
