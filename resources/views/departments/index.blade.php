@extends($layout ?? 'layouts.app')
@section('title','Departments')
@section('content')
    @php
      $deptRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.departments' : 'departments';
      $inSettingsPanel = (View::shared('settings_panel') ?? false) && isset($moduleKey);
    @endphp
    @include('flash::message')
    @if($inSettingsPanel)
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-departments-btn" data-bs-toggle="tab" data-bs-target="#tab-departments" type="button" role="tab">Departments</button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                    @include('partials.settings_module_general', ['moduleKey' => $moduleKey, 'moduleLabel' => $moduleLabel ?? 'Departments', 'defaultLabel' => 'Departments'])
                </div>
                <div class="tab-pane fade" id="tab-departments" role="tabpanel">
    @endif
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
        <div class="clearfix"></div>
        <div class="card">
            @include('departments.table')
        </div>
    </div>
    @if($inSettingsPanel)
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection
