@php $vf = static fn (string $f): bool => field_visible('garage', $f); @endphp
@if($vf('garage_type'))
<x-entity-info-field label="Type" :value="(($garages->garage_type ?? 'external') === 'internal') ? 'Internal' : 'External'" />
@endif
@if($vf('name'))
<x-entity-info-field label="Name" :value="$garages->name" />
@endif
@if($vf('contact_person'))
<x-entity-info-field label="Contact Person" :value="$garages->contact_person" />
@endif
@if($vf('contact_number'))
<x-entity-info-field label="Contact Number" :value="$garages->contact_number" />
@endif
@if($vf('address'))
<x-entity-info-field label="Address" :value="$garages->address" />
@endif
@if($vf('detail'))
<x-entity-info-field class="col-md-12" label="Detail" :value="$garages->detail" />
@endif
@if($vf('status'))
<x-entity-info-field label="Status" :value="((int) ($garages->status ?? 1) === 1) ? 'Active' : 'Inactive'" />
@endif
