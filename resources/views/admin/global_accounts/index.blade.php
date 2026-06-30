@extends('layouts.app')
@section('title', __('Global Accounts'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Global Accounts') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Manage system-wide chart accounts mapped by unique codes used across all companies.') }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                <form method="GET" action="{{ route('admin.global-accounts.index') }}" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search by code, label, or type') }}" value="{{ $search }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                    </div>
                </form>
                <a href="{{ route('admin.global-accounts.create') }}" class="btn btn-success">{{ __('Add Global Account') }}</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Label') }}</th>
                            <th>{{ __('Linked Account') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($globalAccounts as $globalAccount)
                            <tr>
                                <td><code>{{ $globalAccount->code }}</code></td>
                                <td>{{ $globalAccount->label }}</td>
                                <td>
                                    @if($globalAccount->account)
                                        {{ $globalAccount->account->account_code }} — {{ $globalAccount->account->name }}
                                        <span class="text-muted">(#{{ $globalAccount->account_id }})</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('Not configured') }}</span>
                                    @endif
                                </td>
                                <td>{{ $globalAccount->account_type ?? '—' }}</td>
                                <td>
                                    @if($globalAccount->is_active)
                                        <span class="badge bg-success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.global-accounts.edit', $globalAccount) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.global-accounts.destroy', $globalAccount) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this global account mapping?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No global accounts found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $globalAccounts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
