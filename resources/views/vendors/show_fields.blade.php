@php $vf = static fn (string $f): bool => field_visible('vendor', $f); @endphp
@if($vf('name'))
<x-entity-info-field label="Name" :value="$vendors->name" />
@endif
@if($vf('email'))
<x-entity-info-field label="Email" :value="$vendors->email" />
@endif
@if($vf('contact_number'))
<x-entity-info-field label="Contact Number" :value="$vendors->contact_number" />
@endif
@if($vf('address'))
<x-entity-info-field label="Address" :value="$vendors->address" />
@endif
@if($vf('tax_number'))
<x-entity-info-field label="TRN" :value="$vendors->tax_number" />
@endif
@if($vf('status'))
<x-entity-info-field label="Status" :value="((int) $vendors->status === 1) ? 'Active' : 'Inactive'" />
@endif
