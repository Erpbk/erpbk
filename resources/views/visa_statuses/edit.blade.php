@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1>Edit Visa Status</h1>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('visa-statuses.update', $visaStatus->id) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    @include('visa_statuses.fields')
                </div>
            </form>
        </div>
    </div>
</div>
@endsection