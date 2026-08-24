@php $vf = static fn (string $f): bool => field_visible('recruiter', $f); @endphp
@if($vf('name'))
<x-entity-info-field label="Name" :value="$recruiters->name" />
@endif
@if($vf('email'))
<x-entity-info-field label="Email" :value="$recruiters->email" />
@endif
@if($vf('contact_number'))
<x-entity-info-field label="Contact Number" :value="$recruiters->contact_number" />
@endif
@if($vf('address'))
<x-entity-info-field label="Address" :value="$recruiters->address" />
@endif
@if($vf('tax_number'))
<x-entity-info-field label="TRN" :value="$recruiters->tax_number ?? null" />
@endif
@if($vf('status'))
<x-entity-info-field label="Status" :value="((int) $recruiters->status === 1) ? 'Active' : 'Inactive'" />
@endif
