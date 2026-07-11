@php
$fieldsByCategory = $fieldsByCategory ?? \App\Models\RiderCustomField::fieldsByCategoryForForm(true);
$useDynamicFields = is_array($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

@if ($useDynamicFields)
{{-- One card per category, stacked (no tabs) --}}
@foreach($fieldsByCategory as $group)
<div class="card mb-4">
  <div class="card-header">
    <b>{{ $group->category->label }}</b>
  </div>
  <div class="card-body">
    <div class="row">
      @foreach($group->fields as $item)
      @include('riders._form_field', ['item' => $item])
      @endforeach
    </div>
  </div>
</div>
@endforeach
@else
<div class="alert alert-warning mb-0">
    No fields assigned in Rider Settings. Configure categories and fields under Settings Panel → Rider Settings.
</div>
@endif
