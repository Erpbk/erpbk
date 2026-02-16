@extends($layout ?? 'layouts.app')
@section('title','Departments')
@section('content')
    @php $deptRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.departments' : 'departments'; @endphp
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Departments</h3>
                </div>
                <div class="col-sm-6">
                    @can('department_create')
                    <a class="btn btn-primary float-right show-modal" style="float:right;"
                    href="javascript:void(0);" data-title="Add New" data-size="sm" data-action="{{ route($deptRoute . '.create') }}">
                        Add New
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('flash::message')

        <div class="clearfix"></div>

        <div class="card">
            @include('departments.table')
        </div>
    </div>

@endsection
