@extends('Suppliers.view')

@section('page_content')
@php $vf = static fn (string $f): bool => field_visible('supplier', $f); @endphp
<x-entity-info-card title="Supplier Information" icon="ti ti-truck" :edit-url="route('suppliers.edit', $supplier->id)" edit-title="Edit Supplier Details">
    @if($vf('name'))
    <x-entity-info-field label="Supplier Name" :value="$supplier->name" />
    @endif
    @if($vf('email'))
    <x-entity-info-field label="Email" :value="$supplier->email" />
    @endif
    @if($vf('phone') || $vf('contact_number'))
    <x-entity-info-field label="Phone" :value="$supplier->contact_number ?? $supplier->phone" />
    @endif
    @if($vf('address'))
    <x-entity-info-field label="Address" :value="$supplier->address" />
    @endif
    @if($vf('tax_number'))
    <x-entity-info-field label="TRN" :value="$supplier->tax_number" />
    @endif
</x-entity-info-card>
@endsection
