@extends($layout ?? 'layouts.app')

@section('content')
@php $bikeRegistrationRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.bike-registration-statuses' : 'bike-registration-statuses'; @endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1>Edit Registration Status</h1>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route($bikeRegistrationRoute . '.update', $bikeRegistrationStatus->id) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    @include('bike_registration_statuses.fields', ['bikeRegistrationStatus' => $bikeRegistrationStatus])
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
