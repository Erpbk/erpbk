@php $vf = static fn (string $f): bool => field_visible('customer', $f); @endphp
@if($vf('name'))
<x-entity-info-field label="Project Name" :value="$customers->name" />
@endif
@if($vf('company_name'))
<x-entity-info-field label="Company Name" :value="$customers->company_name" />
@endif
@if($vf('company_email'))
<x-entity-info-field label="Company Email" :value="$customers->company_email" />
@endif
@if($vf('contact_number'))
<x-entity-info-field label="Contact Number" :value="$customers->contact_number" />
@endif
@if($vf('address'))
<x-entity-info-field label="Address" :value="$customers->address" />
@endif
@if($vf('tax_number'))
<x-entity-info-field label="TRN" :value="$customers->tax_number" />
@endif
@if($vf('tax_percentage'))
<x-entity-info-field label="Tax %" :value="$customers->tax_percentage" />
@endif
@if($vf('status'))
<x-entity-info-field label="Status" :value="((int) $customers->status === 1) ? 'Active' : 'Inactive'" />
@endif
