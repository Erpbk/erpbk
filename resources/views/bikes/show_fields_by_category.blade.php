@foreach($fieldsByCategory as $group)
<div class="col-12">
    <div class="card border mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <b>{{ $group->category->label }}</b>
            <div class="d-flex align-items-center ms-auto">
                @php
                $mulkiyaAuthorized = auth()->user()->can('bike_document');
                @endphp
                @if($mulkiyaFile)
                <a href="{{ url('storage2/' . $mulkiyaFile->type . '/' . $mulkiyaFile->type_id . '/' . $mulkiyaFile->file_name) }}" target="_blank" class="btn btn-light btn-sm">
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
                @foreach($group->fields as $item)
                @include('bikes._show_field', ['item' => $item])
                @endforeach
            </div>
        </div>
    </div>
</div>
@endforeach