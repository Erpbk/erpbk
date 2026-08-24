@extends('bike_rent_companies.view')

@section('page_content')
@php
    $bikeRentCompany = $bikeRentCompany ?? $customer ?? null;
    $vf = static fn (string $f): bool => field_visible('bike_rent_company', $f);
@endphp
<x-entity-info-card
    title="Customer Information"
    icon="ti ti-building"
    :edit-url="isset($bikeRentCompany) ? route('bikeRentCompanies.edit', $bikeRentCompany->id) : null"
    edit-title="Edit customer"
>
    @if($vf('name'))
    <x-entity-info-field label="Name" :value="$bikeRentCompany->name" />
    @endif
    @if($bikeRentCompany->customer_type === 'bike_rental')
    <x-entity-info-field label="Type" :value="$bikeRentCompany->party_type === 'individual' ? 'Individual' : 'Company'" />
    @endif
    @if($vf('company_contact'))
    <x-entity-info-field label="Contact" :value="$bikeRentCompany->company_contact" />
    @endif
    @if($vf('email'))
    <x-entity-info-field label="Email" :value="$bikeRentCompany->email" />
    @endif
    @if($vf('address'))
    <x-entity-info-field label="Address" :value="$bikeRentCompany->address" />
    @endif
    @if($bikeRentCompany->party_type === 'individual')
    <x-entity-info-field label="Emirates ID" :value="$bikeRentCompany->emirates_id" />
    <x-entity-info-field label="Emirates ID expiry" :value="$bikeRentCompany->emirates_expiry" expiry expiry-name="Emirates ID" />
    <x-entity-info-field label="Passport no" :value="$bikeRentCompany->passport_no" />
    <x-entity-info-field label="Passport expiry" :value="$bikeRentCompany->passport_expiry" expiry expiry-name="Passport" />
    <x-entity-info-field label="Date of birth" :value="optional($bikeRentCompany->dob)->format('d-m-Y')" />
    <x-entity-info-field label="Nationality" :value="$bikeRentCompany->nationality" />
    <x-entity-info-field label="License no" :value="$bikeRentCompany->license_no" />
    <x-entity-info-field label="License expiry" :value="$bikeRentCompany->license_expiry" expiry expiry-name="License" />
    @endif
    @if($vf('status'))
    <x-entity-info-field label="Status" :value="((int) $bikeRentCompany->status === 1) ? 'Active' : 'Inactive'" />
    @endif
    <x-entity-info-field
        label="Chart of accounts"
        :value="$bikeRentCompany->account ? ($bikeRentCompany->account->account_code . ' — ' . $bikeRentCompany->account->name) : null"
    />
</x-entity-info-card>
@endsection
