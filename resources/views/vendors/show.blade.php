@extends('vendors.view')

@section('page_content')
<x-entity-info-card title="Vendor Information" icon="ti ti-briefcase" :edit-url="route('vendors.edit', $vendors->id)" edit-title="Edit Vendor Details">
    @include('vendors.show_fields')
</x-entity-info-card>
@endsection
