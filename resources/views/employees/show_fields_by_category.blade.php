@foreach($fieldsByCategory as $group)
@php
    $rfpGroupVisible = collect($group->fields)->contains(function ($item) {
        $fn = ($item->kind ?? '') === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
        return field_visible('employees', (string) $fn);
    });
@endphp
@if($rfpGroupVisible)
<div class="mb-4 rider-info-group" data-rfp-entity="employee">
    <x-entity-info-card :title="$group->category->label" icon="ti ti-folder">
        @foreach($group->fields as $item)
        @include('employees._show_field', ['item' => $item, 'employee' => $employee])
        @endforeach
    </x-entity-info-card>
</div>
@endif
@endforeach
