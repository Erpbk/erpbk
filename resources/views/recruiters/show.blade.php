@extends('recruiters.view')

@section('page_content')
@php
    $recruiters = $recruiters ?? $recruiter ?? null;
@endphp
<x-entity-info-card title="Recruiter Information" icon="ti ti-users" :edit-url="isset($recruiters) ? route('recruiters.edit', $recruiters->id) : null" edit-title="Edit Recruiter Details">
    @include('recruiters.show_fields')
</x-entity-info-card>
@endsection
