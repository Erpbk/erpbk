@extends('garages.view')

@section('page_content')
@php
    $garages = $garages ?? $garage ?? null;
@endphp
<x-entity-info-card title="Garage Information" icon="ti ti-building-warehouse" :edit-url="isset($garages) ? route('garages.edit', $garages->id) : null" edit-title="Edit Garage Details">
    @include('garages.show_fields')
</x-entity-info-card>
@endsection
