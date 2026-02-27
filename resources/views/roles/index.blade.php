@extends($layout ?? 'layouts.app')

@section('title','Roles')
@section('content')
@php $rolesRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.roles' : 'roles'; @endphp
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Roles</h3>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right show-modal" style="float:right;" href="javascript:void(0);"
                       data-action="{{ route($rolesRoute . '.create') }}" data-title="Create New Role" data-size="lg">
                        Add New
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('flash::message')

        <div class="clearfix"></div>

        <div class="card">
            @include('roles.table')
        </div>
    </div>

@endsection
