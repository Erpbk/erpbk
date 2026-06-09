@extends($layout ?? 'layouts.app')

@section('content')
@php $legalCaseRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.legal-case-statuses' : 'legal-case-statuses'; @endphp
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
            <form method="POST" action="{{ route($legalCaseRoute . '.store') }}">
                @csrf
                <div class="row">
                    @include('legal_case_statuses.fields')
                </div>
            </form>
        </div>
    </div>
</div>
@endsection