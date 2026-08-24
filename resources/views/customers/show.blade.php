@extends('customers.view')

@section('page_content')
@php
    $customers = $customers ?? $customer ?? null;
@endphp
<x-entity-info-card title="Customer Information" icon="ti ti-building" :edit-url="isset($customers) ? route('customers.edit', $customers->id) : null" edit-title="Edit Customer Details">
    @include('customers.show_fields')
</x-entity-info-card>
@endsection
