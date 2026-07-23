@extends('leasing_companies.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }

    .road-status-badge {
        display: inline-block;
        padding: 4px 16px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        text-align: center;
        min-width: 120px;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .road-onroad {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: 1px solid #218838;
    }

    .road-offroad {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        border: 1px solid #c82333;
    }

    .road-onroadRed {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: 2px solid #b02a37;
        color: #ffffff;
    }

    .road-returned {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        border: 1px solid #5c636a;
    }

    .road-absconded {
        background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
        border: 1px solid #842029;
    }

    .road-theft {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        border: 1px solid #4c2b8a;
    }

    .road-total-loss {
        background: linear-gradient(135deg, #343a40 0%, #212529 100%);
        border: 1px solid #1a1d20;
    }

    .road-impound {
        background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
        border: 1px solid #d9480f;
    }

    .road-accident {
        background: linear-gradient(135deg, #b02a37 0%, #922b21 100%);
        border: 1px solid #7b241c;
    }

    .bike-note-cell {
        min-width: 280px;
        max-width: 420px;
        white-space: pre-wrap;
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('leasing_companies_view')
        @php
        $hasLeasingReturn = \Illuminate\Support\Facades\Schema::hasColumn('bikes', 'leased_return_by');
        @endphp
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
            </div>
            <div class="card-body table-responsive px-2 py-0" id="table-data">
                <table class="table dataTable no-footer" id="dataTableBuilder">
                <thead class="text-center">
                    <tr role="row">
                        <th title="Leased Date">Leased Date</th>
                        <th title="Code">Code</th>
                        <th title="Plate">Plate</th>
                        <th title="Emirates">Emirates</th>
                        <th title="Model Type">Model Type</th>
                        <th title="Chassis">Chassis</th>
                        <th title="Engine">Engine</th>
                        <th title="Expiry Date">Expiry Date</th>
                        <th title="Assign Date">Assign Date</th>
                        <th title="Return Date">Return Date</th>
                        <th title="Status">Status</th>
                        <th title="Note">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bikes as $bike)
                    @php
                    $latestHistory = $bike->history->first();
                    $isReturned = $hasLeasingReturn && !empty($bike->leased_return_date);
                    $wKey = strtolower(trim((string) ($bike->warehouse ?? '')));
                    $specialStatuses = [
                        'absconded' => ['Absconded', 'road-absconded'],
                        'theft' => ['Theft', 'road-theft'],
                        'total loss' => ['Total Loss', 'road-total-loss'],
                        'impound' => ['Impound', 'road-impound'],
                        'accident' => ['Accident', 'road-accident'],
                    ];

                    if ($isReturned) {
                        $statusLabel = 'Returned';
                        $statusClass = 'road-returned';
                        $statusTitle = 'Returned to leasing company';
                    } elseif (isset($specialStatuses[$wKey])) {
                        [$statusLabel, $statusClass] = $specialStatuses[$wKey];
                        $statusTitle = 'Status: ' . $statusLabel;
                    } elseif ($wKey === 'active') {
                        $statusLabel = 'On Road';
                        $statusClass = 'road-onroad';
                        $statusTitle = 'Status: On Road';
                    } elseif (in_array($wKey, ['return', 'vacation', 'express garage', 'inactive'], true)) {
                        $statusLabel = 'Off Road';
                        $statusClass = 'road-offroad';
                        $statusTitle = 'Status: Off Road';
                    } else {
                        $statusLabel = 'On Road';
                        $statusClass = 'road-onroadRed';
                        $statusTitle = 'Status: On Road';
                    }

                    $latestNoteRaw = (string) ($latestHistory?->notes ?? '');
                    $latestNote = '';
                    if ($latestNoteRaw !== '' && preg_match('/(?:\*Note:\*|Note:)\s*(.+)$/is', $latestNoteRaw, $noteMatch)) {
                        $latestNote = trim(str_replace('*', '', $noteMatch[1]));
                    }
                    @endphp
                    <tr class="text-center">
                        <td>
                            @if($bike->leased_date)
                                {{ \Carbon\Carbon::parse($bike->leased_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $bike->bike_code ?? '-' }}</td>
                        <td><a href='{{ route('bikes.show', $bike->id) }}' target='_blank'>{{ $bike->plate }}</a></td>
                        <td>{{ $bike->emirates ?? '-' }}</td>
                        <td>{{ $bike->model_type ?? '-' }}</td>
                        <td>{{ $bike->chassis_number ?? '-' }}</td>
                        <td>{{ $bike->engine ?? '-'}}</td>
                        <td>
                            @if($bike->expiry_date)
                                {{ \Carbon\Carbon::parse($bike->expiry_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($latestHistory?->note_date)
                                {{ \Carbon\Carbon::parse($latestHistory->note_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($latestHistory?->return_date)
                                {{ \Carbon\Carbon::parse($latestHistory->return_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="road-status-badge {{ $statusClass }}" title="{{ $statusTitle }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="bike-note-cell">{{ $latestNote !== '' ? $latestNote : '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                </table>

                @if(method_exists($bikes, 'links'))
                    {!! $bikes->links('components.global-pagination') !!}
                @endif
            </div>
        </div>
        @else
            <div class="text-center mt-5">
                <h3>You do not have permission to view Bikes.</h3> 
            </div>
        @endcan
    </div>
@endsection
