@extends($layout ?? 'layouts.app')

@section('title', 'Bike Registration Statuses')

@section('content')
@include('flash::message')

@php
$bikeRegistrationRoute = $bikeRegistrationRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.bike-registration-statuses' : 'bike-registration-statuses');
@endphp

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Bike Registration Statuses</h1>
            </div>
            <div class="col-sm-6 text-end">
                @can('bike_registration_create')
                <a class="btn btn-primary" href="{{ route($bikeRegistrationRoute . '.create') }}">
                    Add New Status
                </a>
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('bike_registration_statuses.table', [
        'bikeRegistrationStatuses' => $bikeRegistrationStatuses,
        'bikeRegistrationRoute' => $bikeRegistrationRoute,
    ])
</div>
@endsection
