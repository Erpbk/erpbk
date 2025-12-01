@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Create Penalty</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-default float-right" href="{{ route('penalties.index') }}">
                    Back
                </a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <div class="card">
        <div class="card-body">
            {!! Form::open(['route' => 'penalties.store']) !!}

            <div class="row">
                @include('penalties.fields')
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection