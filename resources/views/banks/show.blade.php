@extends('banks.view')

@section('page_content')
<x-entity-info-card title="Bank Information" icon="ti ti-building-bank" :edit-url="route('banks.edit', $banks->id)" edit-title="Edit Bank Details">
    @include('banks.show_fields')
</x-entity-info-card>
@endsection
