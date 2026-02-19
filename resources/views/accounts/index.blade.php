@extends('layouts.app')

@section('title', 'Chart of Accounts')
@section('content')
@push('third_party_stylesheets')
<style>
.chart-of-accounts .table th { font-weight: 600; white-space: nowrap; }
.chart-of-accounts .table td { vertical-align: middle; }
.chart-of-accounts .account-name-cell { min-width: 280px; }
.chart-of-accounts .account-name-wrap { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.chart-of-accounts .account-name-wrap .indent { display: inline-block; width: 20px; flex-shrink: 0; }
.chart-of-accounts .account-name-wrap .indent-lines { border-left: 1px solid #dee2e6; margin-right: 8px; min-width: 0; }
.chart-of-accounts .account-name-wrap a { font-weight: 500; }
.chart-of-accounts .table .text-muted { color: #6c757d !important; }
.chart-of-accounts .btn-actions { padding: 4px 8px; }
.chart-of-accounts .search-wrap { max-width: 280px; }
</style>
@endpush

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
                    <i class="fa fa-plus me-1"></i> Add New
                </a>
                @endcan
                @can('trash_view')
                <a class="btn btn-outline-secondary float-right me-2" href="{{ route('accounts.trash') }}">
                    <i class="fa fa-trash-o"></i> View Trash
                </a>
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card chart-of-accounts">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="mb-0 d-flex align-items-center">
                    <span class="me-2">All Accounts</span>
                    <i class="fa fa-caret-down text-muted small" aria-hidden="true"></i>
                </h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form action="{{ request()->url() }}" method="GET" class="d-flex search-wrap">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search accounts..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-secondary d-none d-md-inline">Search</button>
                    </div>
                </form>
                @can('account_create')
                <a class="btn btn-primary btn-sm action-btn show-modal"
                   href="javascript:void(0);" data-action="{{ route('accounts.create') }}" data-size="lg" data-title="New Account">
                    <i class="fa fa-plus me-1"></i> New
                </a>
                @endcan
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="moreOptions" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="moreOptions">
                        @can('trash_view')
                        <li><a class="dropdown-item" href="{{ route('accounts.trash') }}"><i class="fa fa-trash-o me-2"></i>View Trash</a></li>
                        @endcan
                        <li><a class="dropdown-item" href="{{ route('accounts.ledger') }}"><i class="fa fa-book me-2"></i>Ledger</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                @include('accounts.table', ['accounts' => $accounts])
            </div>
        </div>
    </div>
</div>
@endsection
