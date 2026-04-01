@extends('layouts.app')
@section('title', __('Company') . ': ' . $company->name)

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Back to list') }}</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ $company->name }}</h5>
            <span class="badge bg-{{ $company->status === 'approved' ? 'success' : ($company->status === 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($company->status) }}</span>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr><th width="180">{{ __('Email') }}</th><td>{{ $company->email }}</td></tr>
                <tr><th>{{ __('Country') }}</th><td>{{ $company->country }}</td></tr>
                <tr><th>{{ __('Phone') }}</th><td>{{ $company->phone }}</td></tr>
                <tr><th>{{ __('City') }}</th><td>{{ $company->city }}</td></tr>
                <tr><th>{{ __('Address') }}</th><td>{{ $company->address }}</td></tr>
                <tr><th>{{ __('Taxpayer') }}</th><td>{{ $company->is_taxpayer ? __('Yes') : __('No') }}</td></tr>
                @if($company->is_taxpayer)
                    <tr><th>{{ __('NTN') }}</th><td>{{ $company->ntn_number }}</td></tr>
                    <tr><th>{{ __('Tax registration date') }}</th><td>
                        {{ $company->tax_registration_date ? \Carbon\Carbon::parse($company->tax_registration_date)->format('Y-m-d') : '-' }}
                    </td></tr>
                @endif
                <tr><th>{{ __('Registered') }}</th><td>{{ $company->created_at->format('M j, Y H:i') }}</td></tr>
                @if($company->database_name)
                    <tr><th>{{ __('Database') }}</th><td><code>{{ $company->database_name }}</code></td></tr>
                    <tr>
                        <th>{{ __('Tenant DB') }}</th>
                        <td>
                            @if($tenantDbReady)
                                <span class="badge bg-success">{{ __('Ready') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Missing') }}</span>
                            @endif
                        </td>
                    </tr>
                @elseif($company->status === 'approved')
                    <tr><th>{{ __('Tenant DB') }}</th><td><span class="badge bg-danger">{{ __('Missing') }}</span></td></tr>
                @endif
            </table>

            @if(auth('admin')->user()->hasPermission('companies_approve'))
                <div class="mt-3">
                    <a href="{{ route('admin.companies.modules.edit', $company) }}" class="btn btn-outline-primary">{{ __('ERP modules & menu titles') }}</a>
                </div>
            @endif

            @if($company->status === 'pending')
                <form action="{{ route('admin.companies.approve', $company) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Approve and create company database?') }}');">
                    @csrf
                    <button type="submit" class="btn btn-success">{{ __('Approve') }}</button>
                </form>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">{{ __('Reject') }}</button>
                <div class="modal fade" id="rejectModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.companies.reject', $company) }}" method="post">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Reject company') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">{{ __('Reason (optional)') }}</label>
                                    <textarea name="rejection_reason" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-danger">{{ __('Reject') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @elseif($company->status === 'approved' && ! $tenantDbReady && auth('admin')->user()->hasPermission('companies_approve'))
                <div class="alert alert-warning mb-3">{{ __('This company is marked approved but the tenant database is missing or could not be verified. You can create it now.') }}</div>
                <form action="{{ route('admin.companies.approve', $company) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Create the tenant database now?') }}');">
                    @csrf
                    <button type="submit" class="btn btn-warning">{{ __('Create tenant database') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
