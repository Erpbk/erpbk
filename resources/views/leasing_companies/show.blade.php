@extends('leasing_companies.view')

@section('page_content')
@php
    $leasingCompanies = $leasingCompanies ?? $leasingCompany ?? null;
@endphp
<x-entity-info-card title="Company Information" icon="ti ti-building" :edit-url="isset($leasingCompanies) ? route('leasingCompanies.edit', $leasingCompanies->id) : null" edit-title="Edit Leasing Company Details">
    @include('leasing_companies.show_fields')
</x-entity-info-card>
@endsection
