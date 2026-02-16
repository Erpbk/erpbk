@extends($layout ?? 'layouts.app')

@section('title','Dropdowns')
@section('content')
    @php $dropdownRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.dropdowns' : 'dropdowns'; @endphp
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Dropdowns</h3>
                </div>
                <div class="col-sm-6">
                  @can('dropdown_create')
                    <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="lg" data-title="New Dropdown" data-action="{{ route($dropdownRoute . '.create') }}">
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
            @include('dropdowns.table')
        </div>
    </div>

@endsection
