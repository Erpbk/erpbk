@php $vf = static fn (string $f): bool => field_visible('leasing', $f); @endphp
@if($vf('name'))
<x-entity-info-field label="Name" :value="$leasingCompanies->name" />
@endif
@if($vf('contact_person'))
<x-entity-info-field label="Contact Person" :value="$leasingCompanies->contact_person" />
@endif
@if($vf('contact_number'))
<x-entity-info-field label="Contact Number" :value="$leasingCompanies->contact_number" />
@endif
@if($vf('trn_number'))
<x-entity-info-field label="TRN" :value="$leasingCompanies->trn_number ?? null" />
@endif
@if($vf('detail'))
<x-entity-info-field class="col-md-12" label="Detail" :value="$leasingCompanies->detail" />
@endif
@if($vf('status'))
<x-entity-info-field label="Status" :value="((int) $leasingCompanies->status === 1) ? 'Active' : 'Inactive'" />
@endif
