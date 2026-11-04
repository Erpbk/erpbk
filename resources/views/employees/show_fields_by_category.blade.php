{{-- HTML demo Information sections (sec + 4-col fields) --}}
@php
  // Invoice/terms labels belong on invoices; Status is already the Active badge.
  $hideOnProfile = [
    'invoice_note',
    'invoice_note_label',
    'terms_and_conditions',
    'terms_and_conditions_label',
    'status',
  ];
  $notesItem = null;
  foreach ($fieldsByCategory as $group) {
    foreach ($group->fields as $item) {
      if (($item->kind ?? '') === 'fixed' && ($item->field_key ?? '') === 'notes') {
        $notesItem = $item;
        break 2;
      }
    }
  }
@endphp
@foreach($fieldsByCategory as $group)
@php
  $visibleFields = collect($group->fields)->filter(function ($item) use ($hideOnProfile) {
    $key = ($item->kind ?? '') === 'fixed' ? (string) ($item->field_key ?? '') : ('cf_' . ($item->field->id ?? ''));
    if (($item->kind ?? '') === 'fixed' && in_array($key, $hideOnProfile, true)) {
      return false;
    }
    // Notes is rendered once, full-width, at the end of Employee Information
    if (($item->kind ?? '') === 'fixed' && $key === 'notes') {
      return false;
    }
    return field_visible('employees', $key);
  })->values();
  $isEmployeeInfo = str_contains(strtolower((string) ($group->category->label ?? '')), 'employee information');
  $showNotesHere = $isEmployeeInfo && $notesItem && field_visible('employees', 'notes');
  $rfpGroupVisible = $visibleFields->isNotEmpty() || $showNotesHere;
@endphp
@if($rfpGroupVisible)
<section class="emp-sec" data-rfp-entity="employee">
  <header>{{ $group->category->label }}</header>
  <div class="emp-fields">
    @foreach($visibleFields as $item)
      @include('employees._show_field', ['item' => $item, 'employee' => $employee])
    @endforeach
    @if($showNotesHere)
      @include('employees._show_field', [
        'item' => $notesItem,
        'employee' => $employee,
        'fullWidth' => true,
      ])
    @endif
  </div>
</section>
@endif
@endforeach

{{-- If no "Employee Information" category existed, still show Notes once at the end --}}
@php
  $hadEmployeeInfo = collect($fieldsByCategory)->contains(function ($group) {
    return str_contains(strtolower((string) ($group->category->label ?? '')), 'employee information');
  });
@endphp
@if(!$hadEmployeeInfo && $notesItem && field_visible('employees', 'notes'))
<section class="emp-sec" data-rfp-entity="employee">
  <header>Employee Information</header>
  <div class="emp-fields">
    @include('employees._show_field', [
      'item' => $notesItem,
      'employee' => $employee,
      'fullWidth' => true,
    ])
  </div>
</section>
@endif
