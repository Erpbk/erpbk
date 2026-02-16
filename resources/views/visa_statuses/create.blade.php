@extends($layout ?? 'layouts.app')

@section('content')
@php $visaRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses'; @endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1>Create Visa Status</h1>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route($visaRoute . '.store') }}">
                @csrf
                <div class="row">
                    @include('visa_statuses.fields')
                </div>
            </form>
        </div>
    </div>
</div>
@endsection