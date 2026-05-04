@php
  $bankFormLayout = \App\Support\BankFormLayout::formGroups();
@endphp

@if($bankFormLayout['useCategories'])
  <div class="col-12">
    @foreach($bankFormLayout['groups'] as $group)
      <div class="card mb-3">
        @if(!empty($group['title']))
          <div class="card-header">
            <b>{{ $group['title'] }}</b>
          </div>
        @endif
        <div class="card-body">
          <div class="row">
            @foreach($group['fields'] as $field)
              @include('banks._field_input', ['fieldKey' => $field['key'], 'label' => $field['label']])
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>
@else
  @foreach(($bankFormLayout['groups'][0]['fields'] ?? []) as $field)
    @include('banks._field_input', ['fieldKey' => $field['key'], 'label' => $field['label']])
  @endforeach
@endif
