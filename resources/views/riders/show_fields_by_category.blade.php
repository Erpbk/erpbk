@foreach($fieldsByCategory as $group)
  <div class="col-12">
  <div class="card border mb-4">
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
@endforeach
