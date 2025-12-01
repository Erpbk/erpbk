@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Edit COD</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-default float-right" href="{{ route('cod.index') }}">
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
            {!! Form::model($cod, ['route' => ['cod.update', $cod->id], 'method' => 'patch']) !!}

            <div class="row">
                @include('cod.fields')
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection