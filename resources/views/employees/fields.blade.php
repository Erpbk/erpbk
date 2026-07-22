@php
$employeeCategories = $employeeCategories ?? \App\Models\EmployeeCategory::orderBy('display_order')->orderBy('id')->get();
$fieldsByCategory = $fieldsByCategory ?? \App\Models\EmployeeCustomField::fieldsByCategoryForForm(true);
$useDynamicFields = is_array($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

@if ($useDynamicFields)
@foreach($fieldsByCategory as $group)
@php
    $rfpGroupVisible = collect($group->fields)->contains(function ($item) {
        $fn = ($item->kind ?? '') === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
        return field_visible('employees', (string) $fn);
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
                @include('employees._form_field', ['item' => $item, 'employee' => $employee ?? null])
            @endforeach
        </div>
    </div>
</div>
@endif
@endforeach
@else
<div class="alert alert-warning mb-0">
    No fields assigned in Employee Settings. Configure categories and fields under Settings Panel → Employee Settings.
</div>
@endif
