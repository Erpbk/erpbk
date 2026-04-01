@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>{{ __('Permissions') }}</h3>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right show-modal"
                       style="float:right;"
                       href="javascript:void(0);"
                       data-action="{{ route('admin.permissions.create') }}"
                       data-title="Create New">
                        Add New
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('flash::message')

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="clearfix"></div>

        <div class="card">
            @include('admin.permissions.table')
        </div>
    </div>
@endsection

