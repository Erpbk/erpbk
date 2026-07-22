@foreach($fieldsByCategory as $group)
@php
    $rfpGroupVisible = collect($group->fields)->contains(function ($item) {
        $fn = $item->kind === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
        return field_visible('bike', (string) $fn);
    });
@endphp
@if($rfpGroupVisible)
<div class="col-12">
    <div class="card border mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap ps-2">
            <b>{{ $group->category->label }}</b>
            <div class="d-flex align-items-center ms-auto">
                @php
                $mulkiyaAuthorized = auth()->user()->can('bikes_documents_create');
                @endphp
                @if($mulkiyaFile)
                <a href="{{ storage_url($mulkiyaFile->type . '/' . $mulkiyaFile->type_id . '/' . $mulkiyaFile->file_name) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="ti ti-download"></i> Mulkiya
                </a>
                @elseif($mulkiyaAuthorized)
                <a class="btn btn-light btn-sm show-modal"
                    href="javascript:void(0);"
                    data-action="{{ route('files.create', ['type_id' => $bikes->id, 'type' => 'bike', 'suggested_name' => 'Mulkiya']) }}"
                    data-size="sm"
                    data-title="Upload File">
                    <i class="ti ti-upload"></i> Upload Mulkiya
                </a>
                @else
                <span class="small text-white-50">No Mulkiya</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                @php
                $isNoteOrDetailField = function ($item) {
                    $key = strtolower((string) ($item->field_key ?? ''));
                    $label = strtolower((string) ($item->kind === 'fixed' ? ($item->label ?? '') : ($item->field->label ?? '')));
                    return str_contains($key, 'note')
                        || str_contains($key, 'detail')
                        || str_contains($label, 'note')
                        || str_contains($label, 'detail');
                };

                $regularFields = collect($group->fields)->filter(fn ($item) => !$isNoteOrDetailField($item));
                $noteFields = collect($group->fields)->filter(fn ($item) => $isNoteOrDetailField($item));
                @endphp

                @foreach($regularFields as $item)
                @include('bikes._show_field', ['item' => $item, 'fullWidth' => false])
                @endforeach

                @foreach($noteFields as $item)
                @include('bikes._show_field', ['item' => $item, 'fullWidth' => true])
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
@endforeach