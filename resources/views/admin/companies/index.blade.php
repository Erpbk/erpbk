@extends('layouts.app')
@section('title', __('Companies'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Companies') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Approve or reject new company registrations') }}</p>
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
            <div class="d-flex gap-2 mb-3 justify-content-between">
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.companies.index') }}" class="btn btn-{{ !request('status') ? 'primary' : 'outline-primary' }} btn-sm">{{ __('All') }}</a>
                    <a href="{{ route('admin.companies.index', ['status' => 'pending']) }}" class="btn btn-{{ request('status') === 'pending' ? 'warning' : 'outline-warning' }} btn-sm">{{ __('Pending') }}</a>
                    <a href="{{ route('admin.companies.index', ['status' => 'approved']) }}" class="btn btn-{{ request('status') === 'approved' ? 'success' : 'outline-success' }} btn-sm">{{ __('Approved') }}</a>
                    <a href="{{ route('admin.companies.index', ['status' => 'rejected']) }}" class="btn btn-{{ request('status') === 'rejected' ? 'danger' : 'outline-danger' }} btn-sm">{{ __('Rejected') }}</a>
                </div>
                @if(auth('admin')->user()->hasPermission('companies_create'))
                    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">{{ __('Create Company') }}</a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Country') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Registered') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            <tr>
                                <td>{{ $company->id }}</td>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->email }}</td>
                                <td>{{ $company->country }}</td>
                                <td>
                                    @if($company->status === 'pending')
                                        <span class="badge bg-warning">{{ __('Pending') }}</span>
                                    @elseif($company->status === 'approved')
                                        <span class="badge bg-success">{{ __('Approved') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                    @endif
                                </td>
                                <td>{{ $company->created_at->format('M j, Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                    @if(auth('admin')->user()->hasPermission('companies_approve'))
                                        <a href="{{ route('admin.companies.modules.edit', $company) }}" class="btn btn-sm btn-outline-secondary">{{ __('Modules') }}</a>
                                    @endif
                                    @if($company->status === 'pending')
                                        <form action="{{ route('admin.companies.approve', $company) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Approve this company and create their database?') }}');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">{{ __('Approve') }}</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $company->id }}">{{ __('Reject') }}</button>
                                        <div class="modal fade" id="rejectModal{{ $company->id }}" tabindex="-1">
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
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">{{ __('No companies found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $companies->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
