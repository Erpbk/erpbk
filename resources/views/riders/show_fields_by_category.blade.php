@foreach($fieldsByCategory as $group)
  @php
    $rfpGroupVisible = collect($group->fields)->contains(function ($item) {
      $fn = $item->kind === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
      return field_visible('rider', (string) $fn);
    });
  @endphp
  @if($rfpGroupVisible)
  <div class="col-12 rider-info-group" data-rfp-entity="rider">
  <div class="card mb-4">
    <div class="card-header">
      <b>{{ $group->category->label }}</b>
    </div>
    <div class="card-body">
      <div class="row">
        @foreach($group->fields as $item)
          @include('riders._show_field', ['item' => $item, 'rider' => $rider])
        @endforeach
      </div>
    </div>
  </div>
  </div>
  @endif
@endforeach
