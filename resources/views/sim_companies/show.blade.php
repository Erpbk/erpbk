@extends('layouts.app')

@section('title', 'SIM Company')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $simCompany->name }}</h3>
            </div>
            <div class="col-sm-6 text-end">
                @can('sims_companies_edit')
                <a href="javascript:void(0);" class="btn btn-primary show-modal" data-action="{{ route('simCompanies.edit', $simCompany->id) }}" data-title="Edit SIM company" data-size="lg">Edit</a>
                @endcan
                <a href="{{ route('simCompanies.index') }}" class="btn btn-secondary">Back to list</a>
            </div>
        </div>
    </div>
</section>
<div class="content px-3">
    @include('flash::message')
    <div class="card">
        <div class="card-body">
            @php $vf = static fn (string $f): bool => field_visible('sim', $f); @endphp
            <dl class="row mb-0">
                @if($vf('name'))<dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $simCompany->name }}</dd>@endif
                @if($vf('company_contact'))<dt class="col-sm-3">Company contact</dt>
                <dd class="col-sm-9">{{ $simCompany->company_contact ?: '—' }}</dd>@endif
                @if($vf('email'))<dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $simCompany->email ?: '—' }}</dd>@endif
                @if($vf('address'))<dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $simCompany->address ?: '—' }}</dd>@endif
                @if($vf('status'))<dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    @if($simCompany->status == 1)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </dd>@endif
                <dt class="col-sm-3">Chart of accounts (Current Assets)</dt>
                <dd class="col-sm-9">
                    @if($simCompany->account)
                        <strong>{{ $simCompany->account->account_code }}</strong> — {{ $simCompany->account->name }}
                        <span class="text-muted">(ID {{ $simCompany->account_id }})</span>
                    @else
                        —
                    @endif
                </dd>
            </dl>
        </div>
    </div>
</div>
@endsection
