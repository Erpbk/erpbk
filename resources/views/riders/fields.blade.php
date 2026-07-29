@php
$fieldsByCategory = $fieldsByCategory ?? \App\Models\RiderCustomField::fieldsByCategoryForForm();
$useDynamicFields = is_array($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

@if ($useDynamicFields)
{{-- One card per category, stacked (no tabs) --}}
@foreach($fieldsByCategory as $group)
@php
  $rfpGroupVisible = collect($group->fields)->contains(function ($item) {
    $fn = $item->kind === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
    return field_visible('rider', (string) $fn);
  });
@endphp
@if($rfpGroupVisible)
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
@endif
@endforeach
@else
<div class="alert alert-warning mb-0">
    No fields assigned in Rider Settings. Configure categories and fields under Settings Panel → Rider Settings.
</div>
@endif
