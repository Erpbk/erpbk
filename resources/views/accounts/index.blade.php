@extends('layouts.app')

@section('title','Accounts')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Accounts</h3>
                </div>
                <div class="col-sm-6">
                  @can('account_create')
                    <a class="btn btn-primary float-right action-btn show-modal"
                       href="javascript:void(0);" data-action="{{ route('accounts.create') }}" data-size="lg" data-title="New Account">
                        Add New
                    </a>
                    @endcan
                  @can('trash_view')
                    <a class="btn btn-warning float-right me-2" href="{{ route('accounts.trash') }}">
                        <i class="fa fa-trash-o"></i> View Trash
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

{{-- {{ $settings['company_name'] ?? 'Default Site Name' }} --}}

    <div class="content px-3">

        @include('flash::message')

        <div class="clearfix"></div>

        <div class="card">
            @include('accounts.table')
        </div>
    </div>

@endsection
