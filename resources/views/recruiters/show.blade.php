@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Recruiter Details</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-right">
                    <a class="btn btn-primary mr-2"
                        href="{{ route('recruiters.riders', $recruiters->id) }}">
                        View Riders
                    </a>
                    <a class="btn btn-success"
                        href="{{ route('recruiters.assign-riders', $recruiters->id) }}">
                        Assign Riders
                    </a>
                    <a class="btn btn-default"
                        href="{{ route('recruiters.index') }}">
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="card">
        <div class="card-body">
            <div class="row">
                @include('recruiters.show_fields')
            </div>
        </div>
    </div>
</div>
@endsection