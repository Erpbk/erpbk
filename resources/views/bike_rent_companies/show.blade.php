@extends('layouts.app')

@section('title', 'Bike on rent customer')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $bikeRentCompany->name }}</h3>
            </div>
            <div class="col-sm-6 text-end">
                @canany(['bike_on_rent_customers_edit', 'garages_customers_edit'])
                <a href="javascript:void(0);" class="btn btn-primary show-modal" data-action="{{ route('bikeRentCompanies.edit', $bikeRentCompany->id) }}" data-title="Edit customer" data-size="lg">Edit</a>
                @endcanany
                <a href="{{ route('bikeRentCompanies.index') }}" class="btn btn-secondary">Back to list</a>
            </div>
        </div>
    </div>
</section>
<div class="content px-3">
    @include('flash::message')
    <div class="card">
        <div class="card-body">
            @php $vf = static fn (string $f): bool => field_visible('bike_rent_company', $f); @endphp
            <dl class="row mb-0">
                @if($vf('name'))<dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->name }}</dd>@endif
                @if($bikeRentCompany->customer_type === 'bike_rental')
                <dt class="col-sm-3">Type</dt>
                <dd class="col-sm-9">
                    @if($bikeRentCompany->party_type === 'individual')
                        <span class="badge bg-info">Individual</span>
                    @else
                        <span class="badge bg-secondary">Company</span>
                    @endif
                </dd>
                @endif
                @if($vf('company_contact'))<dt class="col-sm-3">Contact</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->company_contact ?: '—' }}</dd>@endif
                @if($vf('email'))<dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->email ?: '—' }}</dd>@endif
                @if($vf('address'))<dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->address ?: '—' }}</dd>@endif
                @if($bikeRentCompany->party_type === 'individual')
                <dt class="col-sm-3">Emirates ID</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->emirates_id ?: '—' }}</dd>
                <dt class="col-sm-3">Emirates ID expiry</dt>
                <dd class="col-sm-9">{{ optional($bikeRentCompany->emirates_expiry)->format('d-m-Y') ?: '—' }}</dd>
                <dt class="col-sm-3">Passport no</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->passport_no ?: '—' }}</dd>
                <dt class="col-sm-3">Passport expiry</dt>
                <dd class="col-sm-9">{{ optional($bikeRentCompany->passport_expiry)->format('d-m-Y') ?: '—' }}</dd>
                <dt class="col-sm-3">Date of birth</dt>
                <dd class="col-sm-9">{{ optional($bikeRentCompany->dob)->format('d-m-Y') ?: '—' }}</dd>
                <dt class="col-sm-3">Nationality</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->nationality ?: '—' }}</dd>
                <dt class="col-sm-3">License no</dt>
                <dd class="col-sm-9">{{ $bikeRentCompany->license_no ?: '—' }}</dd>
                <dt class="col-sm-3">License expiry</dt>
                <dd class="col-sm-9">{{ optional($bikeRentCompany->license_expiry)->format('d-m-Y') ?: '—' }}</dd>
                @endif
                @if($vf('status'))<dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    @if($bikeRentCompany->status == 1)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </dd>@endif
                <dt class="col-sm-3">Chart of accounts</dt>
                <dd class="col-sm-9">
                    @if($bikeRentCompany->account)
                        <strong>{{ $bikeRentCompany->account->account_code }}</strong> — {{ $bikeRentCompany->account->name }}
                        <span class="text-muted">(ID {{ $bikeRentCompany->account_id }})</span>
                    @else
                        —
                    @endif
                </dd>
            </dl>
        </div>
    </div>
</div>
@endsection
