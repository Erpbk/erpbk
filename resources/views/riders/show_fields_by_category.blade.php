@foreach($fieldsByCategory as $group)
  @php
    $rfpGroupVisible = collect($group->fields)->contains(function ($item) {
      $fn = $item->kind === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
      return field_visible('rider', (string) $fn);
    });
    $orderedFields = collect($group->fields)->sortBy(function ($item) {
      $key = strtolower((string) (($item->kind ?? '') === 'fixed' ? ($item->field_key ?? '') : ''));
      $label = strtolower((string) (($item->kind ?? '') === 'fixed' ? ($item->label ?? '') : ($item->field->label ?? '')));
      $isReference = $key === 'rider_reference'
        || str_contains($key, 'rider_reference')
        || str_contains($label, 'reference of rider')
        || $label === 'reference rider'
        || $label === 'rider reference';
      return $isReference ? 1 : 0;
    })->values();
  @endphp
  @if($rfpGroupVisible)
  <div class="col-12 rider-info-group" data-rfp-entity="rider">
  <div class="card mb-4">
    <div class="card-header">
      <b>{{ $group->category->label }}</b>
    </div>
    <div class="card-body">
      <div class="rider-info-fields">
        @foreach($orderedFields as $item)
          @include('riders._show_field', ['item' => $item, 'rider' => $rider])
        @endforeach
      </div>
    </div>
  </div>
  </div>
  @endif
@endforeach
