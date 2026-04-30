@extends('layouts.app')
@section('title', __('Account Fixing'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Account Fixing') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Mark chart accounts as fixed so they are available to all companies.') }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.accounts.fixed.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, code, or type" value="{{ $search }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Company ID') }}</th>
                            <th>{{ __('Fixed') }}</th>
                            <th class="text-end">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            <tr>
                                <td>{{ $account->id }}</td>
                                <td>{{ $account->account_code ?? '—' }}</td>
                                <td>{{ $account->name }}</td>
                                <td>{{ $account->account_type ?? '—' }}</td>
                                <td>{{ $account->company_id ?? '—' }}</td>
                                <td>
                                    @if($account->is_fixed)
                                        <span class="badge bg-success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.accounts.fixed.toggle', ['account' => $account->id]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $account->is_fixed ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                            {{ $account->is_fixed ? __('Unmark Fixed') : __('Mark Fixed') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">{{ __('No accounts found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
