@extends('layouts.app')

@section('title', 'Fuel Company')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $fuelCompany->name }}</h3>
            </div>
            <div class="col-sm-6 text-end">
                @can('fuel_edit')
                <a href="javascript:void(0);" class="btn btn-primary show-modal" data-action="{{ route('fuelCompanies.edit', $fuelCompany->id) }}" data-title="Edit fuel company" data-size="lg">Edit</a>
                @endcan
                <a href="{{ route('fuelCompanies.index') }}" class="btn btn-secondary">Back to list</a>
            </div>
        </div>
    </div>
</section>
<div class="content px-3">
    @include('flash::message')
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $fuelCompany->name }}</dd>
                <dt class="col-sm-3">Company contact</dt>
                <dd class="col-sm-9">{{ $fuelCompany->company_contact ?: '—' }}</dd>
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $fuelCompany->email ?: '—' }}</dd>
                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $fuelCompany->address ?: '—' }}</dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    @if($fuelCompany->status == 1)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </dd>
                <dt class="col-sm-3">Chart of accounts</dt>
                <dd class="col-sm-9">
                    @if($fuelCompany->account)
                        <strong>{{ $fuelCompany->account->account_code }}</strong> — {{ $fuelCompany->account->name }}
                        <span class="text-muted">({{ $fuelCompany->account->account_type }}, ID {{ $fuelCompany->account_id }})</span>
                    @else
                        —
                    @endif
                </dd>
            </dl>
        </div>
    </div>
</div>
@endsection
